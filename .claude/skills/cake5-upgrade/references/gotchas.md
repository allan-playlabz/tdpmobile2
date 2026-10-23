# CakePHP 3.x → 5.x gotchas

Problems that real 3.x → 5.x upgrades hit, with the fix. Check each against the project being upgraded.

## Framework removals that need a replacement

### `Cake\Filesystem\File` / `Folder` removed
5.0 dropped the Filesystem package. Two options:
- Rewrite call sites with native PHP (`file_get_contents()`, `file_put_contents()`, `SplFileInfo`, `RecursiveDirectoryIterator`) — best for a handful of uses.
- Many uses → add small drop-in shims (`App\Filesystem\File`, `App\Filesystem\Folder`) implementing only the methods the project calls (`grep -rhoE "->(read|write|append|delete|exists|find|pwd|name|size|mime)\(" src`), backed by native PHP, then `use Cake\Filesystem\File;` → `use App\Filesystem\File;`.

### `Cake\Mailer\Email` removed
Use `Cake\Mailer\Mailer`:
```php
// before (3.x)
$email = new Email('default');
$email->template('welcome')->emailFormat('html')->viewVars(['user' => $user])
      ->to($to)->subject($subject)->send();
// after (5.x)
$mailer = new Mailer('default');
$mailer->viewBuilder()->setTemplate('welcome');
$mailer->setEmailFormat('html')->setViewVars(['user' => $user])
       ->setTo($to)->setSubject($subject)->deliver();
```
Email templates move to `templates/email/html|text/`, layouts to `templates/layout/email/`. Watch for `Email` in rarely-run paths (error-notification mails, cron reports): they compile fine and only fatal when reached.

### Global functions are no longer auto-loaded
`h()`, `__()`, `env()`, `debug()` in templates/config fail with "Call to undefined function". The 5.x skeleton's `config/bootstrap.php` loads them all with one line:
```php
require CAKE . 'functions.php';   // Core, I18n, Collection, Error, Routing global helpers
use function Cake\Core\env;
```
(Requiring individual `src/*/functions_global.php` files also works but is easy to leave incomplete.) In namespaced PHP files prefer `use function Cake\Core\env;` / `use function Cake\I18n\__;`.

## Plugins
- Plugins are registered in `config/plugins.php` (5.4 skeleton) or with `$this->addPlugin()` in `Application::bootstrap()` — `Plugin::load()` in `bootstrap.php` is gone. CLI-only plugins: `'onlyCli' => true`; DebugKit: `'onlyDebug' => true`.
- Third-party plugins need their 5.x major (see `cake5-deps/references/versions.md`). A plugin's custom log engine/behaviour/table must match the new parent signatures, e.g. log engines: `log($level, Stringable|string $message, array $context = []): void`.
- Plugins can themselves call `env()` / `__()` unqualified and fatal in 5.x — fix with a composer patch until upstream does.

## Composer patches for not-yet-fixed upstream packages
`cweagans/composer-patches ^2`, in `composer.json`:
```json
"extra": {
  "patches": {
    "vendor/some-plugin": {
      "Fix missing env() function import": "patches/some-plugin-env-fix.patch"
    }
  }
}
```
Make the patch with `git diff` inside a copy of the package (paths relative to the package root). **Commit `patches/`** (check `.gitignore`), and re-check on every plugin upgrade whether the patch is still needed.

## composer.json pitfalls
- `cakephp/cakephp` and runtime plugins must be in **`require`**, not `require-dev` — `composer install --no-dev` on production would otherwise install no framework. This happens easily when packages are bumped with `composer require --dev`.
- Pin `php` to what production actually runs and keep docs/Docker consistent.

## Routing & middleware
- **CSRF and webhooks.** The 5.4 skeleton adds `CsrfProtectionMiddleware` globally; many apps instead apply it as scoped route middleware (`registerMiddleware('csrf', ...)` + `applyMiddleware('csrf')` in the `/` scope). Inbound webhooks/API callbacks can't send a CSRF token:
  - with scoped CSRF, put them in their own scope connected **before** `/`:
    ```php
    $routes->scope('/webhooks', function (RouteBuilder $builder): void {
        $builder->connect('/payment', ['controller' => 'PaymentWebhooks', 'action' => 'receive']);
    });
    ```
  - with global CSRF, exempt them via `$csrf->skipCheckCallback(fn($request) => str_starts_with($request->getPath(), '/webhooks/'))`.
  - A URL that doesn't exactly match the webhook route (e.g. a trailing path segment) falls through to `fallbacks()` in the CSRF scope and fails with `Missing CSRF token cookie` *before* any missing-controller error — misleading when debugging.
- Route placeholders are `{controller}`/`{action}`/`{id}`; the `:id` syntax is removed. `routes.php` returns `static function (RouteBuilder $routes): void`.
- `BodyParserMiddleware` must be in the queue for JSON bodies to appear in `getData()`.
- Post-response work: listen to `Server.terminate`; `ResponseEmitter` calls `fastcgi_finish_request()` under PHP-FPM (under Apache mod_php the client waits).

## Auth
- AuthComponent is gone → `cakephp/authentication ^3` (`Application::getAuthenticationService()`, `AuthenticationMiddleware`, `$this->loadComponent('Authentication.Authentication')`). See `cake5-auth`.
- Keep a compatibility helper so old `$this->Auth->user('x')` call sites have a one-line replacement (`AppController::getAuthUser(?string $key)`). It must handle an **`ArrayObject`** identity (what a session array becomes, e.g. `$this->session(['Auth' => [...]])` in tests) as well as an entity — a helper that calls `->toArray()` directly fatals in every authenticated test.
- Public actions: `$this->Authentication->addUnauthenticatedActions([...])` in the controller's `beforeFilter`.

## ORM & types
- `FrozenTime`/`FrozenDate` still exist as deprecated aliases of `DateTime`/`Date`, which are **immutable** — `$d->addDays(1);` or `$d->setTime(23, 59, 59);` without reassignment silently does nothing. These bugs typically surface weeks after go-live (wrong expiry/validity times). The scan's `immutable-result-discarded` pattern finds candidates; fix as `$d = $d->addDays(1);`.
- Singular Chronos helpers are gone: `subDay()` → `subDays(1)`, `addMonth()` → `addMonths(1)`.
- **`Cake\I18n\Time` now means time-of-day** (extends `ChronosTime`). `$x instanceof \Cake\I18n\Time` on a datetime is silently `false`. Use `instanceof \DateTimeInterface`.
- `DateTime::setToStringFormat()` is static and global (often set in `AppController::initialize()`).
- Binary(16) UUID primary keys use the core `binaryuuid` type. Test `find()->where(['id' => $uuid])`, `get($uuid)`, any raw SQL with `UNHEX(REPLACE(...))`, and saving associations.
- `where(['col' => $value])` throws when `$value` is `null` — guard it or use `'col IS' => null`.
- `beforeFind` must not return the query any more; just modify it.
- `epilog('FOR UPDATE')` still works for row locks inside `transactional()`.
- Multiple datasources: tables pin theirs with `public static function defaultConnectionName(): string`. A `belongsTo` across connections must use `'strategy' => 'select'` (no SQL join across databases).

## Config & environment
- Datasource `url` DSNs: special characters in passwords (`@ : / ? # % & +`) must be percent-encoded, otherwise "Access denied for user" even with correct credentials. Or use `host/username/password/database` keys.
- Know the load order: `config/app.php`, then the local override (`app_local.php`, or whatever the project's bootstrap loads) — check the *effective* datasource when a connection fails.
- After deploy, stale code is served if OPcache doesn't revalidate (`opcache.validate_timestamps=0`) or a Docker image wasn't rebuilt. Symptom: new routes/controllers "not found" or the wrong middleware.

## Template folder case (Linux)
CakePHP 5 renders error pages from **`templates/Error/`** (it's `ErrorController`'s template path) while `element/`, `layout/`, `email/` and `cell/` are lowercase. Windows and macOS don't care, so a lowercased `templates/error/` passes every local test and then the custom 400/500 pages can't be found on a Linux server. `cake5-structure/scripts/move_templates.sh` keeps `Error/` and lowercases `Cell/`; fix an existing `templates/error/` with two `git mv` steps.

## Untouched skeleton files call removed APIs
Projects carry 3.x skeleton files nobody edited — and they break: `templates/Pages/home.php` calls `Connection::connect()` (removed — `/pages/home` returns 500), the skeleton tests extend `IntegrationTestCase` (removed), `ConsoleShell` and `AjaxView` have no 5.x equivalent. Replace untouched skeleton files with the 5.x skeleton's (`cake5-structure` step 2). The 5.4 `Application.php` needs `src/Middleware/HostHeaderMiddleware.php` from the same skeleton.

## Toolchain
- Use the **5.x branch of `cakephp/upgrade`** for every Rector set (`cakephp40`…`cakephp54`, `chronos3`, `phpunit80`) — it runs on current PHP. The old 4.x branch can't install on PHP ≥ 8.4 and its Rector 0.16 fails on 8.5.
- Latest skeleton = CakePHP 5.4: PHP ≥ 8.2, PHPUnit ≥ 11.5, Migrations 5, plugins in `config/plugins.php`, global CSRF middleware, no `requirements.php`.
- Scripting the web checks: send the `csrfToken` cookie **URL-decoded** in `X-CSRF-Token`, and take form tokens from POST forms (GET forms have none).

## Process lessons
- **Hidden deprecations let old APIs survive.** With `'errorLevel' => E_ALL & ~E_DEPRECATED`, deprecated `->order()`, `FrozenTime` and `Cake\ORM\Query` uses stay in the code indefinitely. Convert with `E_ALL`; suppress only after the scan is clean.
- **Removed classes compile and only fail when the line runs.** Grep for every removed FQCN (`Email`, `Cake\Filesystem`, `Zend\Diactoros`, `Cake\Auth\*`, …), including inside `catch` blocks and `instanceof` checks.
- **Mechanical named-argument conversion goes too far.** `find('list', limit: 200)`, `order: [...]`, `find('all', conditions: [...])` are invalid — only finder options are named args; the rest must be chained. And `find('list', valueField: 'id')` used as an `IN` subquery selects two columns (SQLSTATE[21000]); use `find()->select(['X.id'])`.
- **`fetchTable()` doesn't exist on Table classes** (no `LocatorAwareTrait`). In Tables use `TableRegistry::getTableLocator()->get()` or associations.
- **Don't add `: void` to `beforeFilter()`** in AppController — child controllers that declare it untyped then fatal. To stop a request, use `$event->setResult($response); $event->stopPropagation(); return;` instead of returning a Response.
- **Pagination:** move `conditions`/`contain` from `$this->paginate` into the query; `contain()` before paginating (`loadInto()` on a paginated result fails); manual `limit()/page()` queries don't feed PaginatorHelper — use `paginate($query)`. `setPaginated()` is only needed when a view has more than one paginated result.
- **Query results are no longer collections:** `->toList()`, `->indexBy()`, `->extract()` need `->all()` first.
- **Case-only renames on Windows/macOS** (`Element` → `element`) need two steps: `git mv Element element_tmp && git mv element_tmp element`.
- **Verification that works:** a route scanner (Playwright, or curl against `bin/cake server`) hitting every page and failing on PHP errors/warnings catches far more than an unconverted unit-test suite.

## Tests
- Fixtures with `public $fields = [...]` (3.x style) are ignored in 5.x — schema comes from migrations or an SQL dump loaded in `tests/bootstrap.php`. An old `phpunit.xml.dist` with `<listeners>` FixtureInjector makes nearly every DB test error. Plan this phase explicitly; on large apps it's big.
- PHPUnit 10.5+/11.5: new `phpunit.xml.dist` schema (`<source>`, `<extensions>` with `Cake\TestSuite\Fixture\Extension\PHPUnitExtension`), data providers must be `public static`.
