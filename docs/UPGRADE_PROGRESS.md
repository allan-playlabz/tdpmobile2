# CakePHP 5 upgrade progress
Target: cakephp/cakephp ^5.4, PHP 8.3+ — branch `upgrade/cake5`

## Audit (2026-10-23)
Current: cakephp/cakephp 3.10.5 (locked); local CLI PHP 8.5.2.

- Files scanned: 348. Blocker hits: **956** (structure 83, code-apis 562, auth 171, tests 140).
  - 82 of those hits are in `src/Controller/OrdersController - Copy.php` (backup file, not routed).
- Warning hits: 175 (`FrozenTime` 52, `Cake\ORM\Query` 47, paginate settings 34, find options arrays 12, uploaded-file array access 10, `toList()` without `all()` 14, other 6).
- Largest blocker groups: AuthComponent 171, `Cake\I18n\Time` (now time-of-day) 112, untyped PHPUnit hooks 91, `.ctp` templates 79, `loadModel()` 73, `initialize()` without `: void` 72, untyped framework properties 58, `$_accessible` untyped 57, `validationDefault` signature 56, `->order()` 47, fixture `$fields` 45, `Cake\Filesystem` 19, legacy request methods 14, `newEntity()` without data 14, `Cake\Mailer\Email` 8.

### Dependencies
| Package | Locked | Target | Action |
|---|---|---|---|
| php | `>=5.6` | `>=8.3` | bump |
| cakephp/cakephp | 3.10.5 | `5.4.*` | bump (in `require` ✓) |
| cakephp/plugin-installer | ^1.0 | `^2.0` | bump |
| cakephp/migrations | ^2.0 | `^5.0` | bump |
| friendsofcake/cakepdf | 3.5.3 | `^5.0` | bump; config uses `CakePdf.WkHtmlToPdf` engine (bootstrap.php) |
| dompdf/dompdf | 0.8.6 | `^2.0`/`^3.0` | bump (only needed if a DomPdf engine is used) |
| dereuromark/cakephp-databaselog | 2.7.2 | `^4.0` | bump; may need composer patch (unqualified `env()`); custom `App\Log\Engine\DbLog` must match new `log()` signature |
| mobiledetect/mobiledetectlib | 2.8.47 | `^4.8` | bump; used in `config/bootstrap.php` `mobile` detector |
| cakephp/authentication | — | `^3.3` | add (replaces AuthComponent) |
| aws/aws-sdk-php | 3.337.3 | ^3 | ok (framework-independent) |
| firebase/php-jwt | 6.4.0 | ^6 | ok |
| stripe/stripe-php | 7.128.0 | ^7 works on PHP 8.3 | ok for upgrade; SDK bump is a separate change |
| mustache/mustache | 2.14.2 | ^2.14 | ok |
| picqer/php-barcode-generator | v0.3 | check PHP 8.3 runtime | verify barcode generation in smoke test |
| cunheise/phpqrcode | 1.0.2 | check PHP 8.3 runtime | verify QR generation in smoke test |
| cakephp/bake (dev) | ^1.9 | `^3.6` | bump |
| cakephp/debug_kit (dev) | ^3.17 | `^5.2` | bump |
| cakephp/cakephp-codesniffer (dev) | ^3.0 | `^5.3` | bump |
| phpunit/phpunit (dev) | 6.5.14 | `^11.5.3` | bump |
| josegonzalez/dotenv (dev) | 3.* | `^4.0` | bump (`config/.env` loader in bootstrap) |
| psy/psysh (dev) | @stable | — | drop with `ConsoleShell` unless used |

### Structure
- `src/Template/` with 79 `.ctp` files (incl. `Layout/Email`, `Layout/pdf`, `Layout/rss`, `Pdf/`, `Cell/`, `Error/`).
- `src/Shell/`: `ConsoleShell.php` (skeleton), `DeleteTempShell.php` (app — check cron for `bin/cake delete_temp`).
- `src/Command/GenerateReportCommand.php` already a Command (needs 5.x signatures).
- `src/Application.php`: no `getAuthenticationService()`; plugins added via `addPlugin()` (CakePdf, DatabaseLog, DebugKit, Bake, Migrations); no `Plugin::load()` in bootstrap.
- `config/app.php` sets `'templates' => [APP . 'Template' . DS]`.
- Custom DB session handler `App\Http\Session\CustomSession` (`AgentSessions` model) — must match 5.x `SessionHandlerInterface` signatures.
- CSRF is scoped route middleware with `whitelistCallback` (→ `skipCheckCallback`); webhook routes are in their own scopes (keep them outside CSRF).
- `tests/Fixture/`: 45 fixtures using `$fields`.
- **Tests do not run before the upgrade**: PHPUnit 6.5 fatals under PHP 8.5. Test DB is `test_myapp` on localhost (placeholder credentials).
- `config/app.php` is tracked in git; real credentials live in gitignored `config/app_config.php` / `config/.env`, but `app.php` has a hardcoded `Security.salt` fallback.

## Phases
- [x] cake5-deps (2026-10-23) — CakePHP 5.4.3 installed
- [x] cake5-structure (2026-10-23) — structure scan 0 blockers; `bin/cake --help` lists `delete_temp`, `generate_report`
- [x] cake5-code-apis (2026-10-23) — code-api scan 0 blockers; lint clean; `generate_report` runs on local DB. Total blockers 956 → 280 (auth 140, tests 140)
- [ ] cake5-auth
- [ ] cake5-tests
- [ ] final scan = 0 blockers, smoke test done

## Phase 1 — dependencies (2026-10-23)
- Start: cakephp/cakephp 3.10.5, PHPUnit 6.5.14; 3.x lock backed up outside the repo (composer.lock is gitignored).
- Resolved: cakephp 5.4.3, authentication 3.3.7, migrations 5.2.6, cakepdf 5.3.0, databaselog 4.7.0, dompdf 3.1.6, mobiledetect 4.11.0, bake 3.8.1, debug_kit 5.2.4, codesniffer 5.3.2, phpunit 11.5.57, dotenv 4.0.0. Unchanged: aws-sdk, php-jwt 6.4, stripe 7.128, mustache 2.14, picqer 0.3, phpqrcode 1.0.2.
- Dropped `psy/psysh` (only used by skeleton `ConsoleShell`).
- No composer patches: DatabaseLog's unqualified `env()` resolves to the global helper once bootstrap requires `CAKE . 'functions.php'` (phase 2).
- `config/app.php` rebuilt from the 5.4 skeleton with project values (DbLog engines, CustomSession DB sessions, `log_ds`/`log_ds_ro`, `DatabaseLog.datasource`, Asia/Singapore tz, salt fallback, skipLog list). `app_config.php` stays the override (loaded by bootstrap).
  - Datasources deliberately have **no `timezone`** key (3.x had none; the skeleton's `UTC` would change MySQL session time).
- `config/paths.php` from skeleton (`RESOURCES`); `src/Locale` → `resources/locales`.
- `config/plugins.php` added: DebugKit (onlyDebug), Bake (onlyCli, optional), Migrations (onlyCli), Authentication, CakePdf, DatabaseLog (routes off). `addPlugin()` calls in `Application.php` to be removed in phase 2.
- `phpcs.xml` from skeleton; `cs-check`/`cs-fix` scripts use it.
- Follow-up: `composer audit` reports CVE-2025-45769 (low) in firebase/php-jwt < 7 (pre-existing; used by Payment2C2P).

## Phase 2 — structure (2026-10-23)
- `src/Template` → `templates/` (79 files, `.ctp` → `.php`); `Element/Flash` → `element/flash` (5.x FlashComponent path); `Error/` kept capitalised.
- Untouched 3.x skeleton files replaced with 5.4 versions: `ErrorController`, `AjaxView`, `Installer` (no `app_local.php` creation/salt rewrite — project uses `app_config.php`), `webroot/index.php`, `bin/cake.php`, `templates/Error/*`, `layout/{ajax,error}.php`, email layouts/templates.
- Removed (3.x skeleton, unused or calling removed APIs): `ConsoleShell`, `config/requirements.php`, `config/bootstrap_cli.php` (its CLI log-file rename is in the 5.x bootstrap), `config/app.default.php`, `templates/Pages/home.php` (no PagesController/route), `templates/layout/rss` (RssHelper removed, unused).
- `DeleteTempShell` → `DeleteTempCommand` (native PHP instead of Folder/File). Cron: `bin/cake delete_temp` — **unchanged name**.
- `GenerateReportCommand`: base class + signatures. Called from `ReportsController` via `exec('php bin/cake.php generate_report --...')` — options unchanged.
- `Application.php`: plugins via `config/plugins.php`; middleware ErrorHandler → Asset → Routing → BodyParser (replaces RequestHandler body parsing). **Not adopted from skeleton**: global CSRF (kept scoped in routes for gateway callbacks), `allowFallbackClass(false)` (tables like `VoucherTemplates` have no class), `HostHeaderMiddleware` (throws 500 in production unless `App.fullBaseUrl` is configured — see follow-ups).
- `config/bootstrap.php`: 5.4 skeleton + project's `.env` loading, `app_config.php` override, HTTPS-only fullBaseUrl detection, and the project's `Configure::write` blocks verbatim; `Type::build()->useImmutable()` removed; `Email::setConfig` → `Mailer::setConfig`.
- `config/routes.php`: closure format; `whitelistCallback` → `skipCheckCallback`; `httpOnly` → `httponly`; gateway callback scopes moved before `/`.
- `Error.exceptionRenderer` left unset so CLI gets the console renderer.
- `DbLog::log()` signature updated (needed to boot).

## Phase 3 — code APIs (2026-10-23)
- Rector (cakephp/upgrade 5.x branch, run directly with `disableParallel()` + `--clear-cache` — parallel workers hang on Windows and the cache makes later sets skip files): cakephp40 (76 files), 44, 45, 50 (128 files), 53; 41/42/51/52/chronos3 no changes. **cakephp43 skipped**: broken against current Rector, and its `getTableLocator()->get()` → `fetchTable()` rule is wrong inside Tables.
- Manual:
  - `App\Filesystem\File` / `Folder` shims (3.x semantics: `find()`, `->path`, create, `lastChange/delete/name/mime`).
  - `Email` → `Mailer` (`deliver()`), RequestHandler removed; XHR requests still render with `AjaxView` via `AppController::beforeRender()` (3.x RequestHandler behaviour); body parsing via BodyParserMiddleware.
  - `Cake\I18n\Time` → `DateTime` (17 files). Mutability audit: `AttractionsController` `$first_release_date->addSeconds()` reassigned (was a silent no-op otherwise); `ReportsController`/`OrdersController` `->hour()->minute()->second()` chains reassigned; `addDay()` → `addDays()`.
  - `date` columns are now `Cake\I18n\Date` (not DateTimeInterface): `fixeddates.date` arithmetic converted to local-midnight `DateTime` (= 3.x FrozenDate semantics) in Attractions/Categories controllers.
  - `loadModel()` → `$this->X = $this->fetchTable('X')`, `#[\AllowDynamicProperties]` on AppController (inherited), CreditsCell, GenerateReportCommand.
  - `->order()` → `->orderBy()`; `find('list', [...])` → named args; `->all()` before `toList()/extract()`; `Table::query()` → `insertQuery()`.
  - **Paginate `conditions`/`contain` moved onto the query** (5.x silently ignores them — would have listed all agents' orders/vouchers/credit logs).
  - Uploads are `UploadedFile` objects: `image.error/tmp_name/name` → `getError()/getStream()->getMetadata('uri')/getClientFilename()`.
  - CSRF token in templates: `getParam('_csrfToken')` → `getAttribute('csrfToken')`.
  - `Number::defaultCurrency()` → `setDefaultCurrency()`; `DefaultPasswordHasher` → Authentication plugin's.
  - Log messages must be strings: arrays `json_encode()`d at call sites (same output as 3.x DbLog).
  - `OrderItemsTable`: duplicate `Vouchers` alias (3.x silently kept the later `hasMany`) — `belongsTo` removed.
  - Session handler: `SessionHandlerInterface` return types; null-safe `agent_user_id` lookup.
  - `fputcsv()` explicit escape `'\'` (PHP 8.4 deprecation; output unchanged).
- PHPStan level 1 on `src/` used as an extra net (remaining findings are pre-existing, listed below).
- **Phase 4 note:** `CustomDatabaseSession::write()` reads `$_SESSION['Auth']['User']['id']` to enforce one session per agent user → configure the Authentication session key as `Auth.User`.
## Follow-ups (not part of the upgrade)
- Consider `HostHeaderMiddleware` with `APP_FULL_BASE_URL` set per environment (Host header injection protection).
- firebase/php-jwt < 7 advisory (CVE-2025-45769, low).

## Found while upgrading
- `ReportsController` builds the `generate_report` shell command from unescaped request data (`issue_start_date`, `issue_end_date`) — command injection risk; use `escapeshellarg()`.

## Decisions (2026-10-23)
- Backup files `src/Controller/OrdersController - Copy.php` and `ignore/` deleted (kept in git history).
- Phase 5 gate: suite runs to completion; failures triaged and listed, not necessarily all green. HTTP smoke test is the primary check.
- Auth: like-for-like `cakephp/authentication` (Session + Form on `AgentUsers`, email/password, same login/logout URLs); no authorization plugin.
- `AttractionsController::getFixeddateTime` (≈ line 1017): `$now->addSeconds($ticket->cutoff_seconds)` inside the loop **accumulated** in 3.x (mutable Time). Kept as-is (`$now = $now->addSeconds(...)`) — probably meant `$now->addSeconds()` per ticket without accumulating. **Decide.**
- `ReportsController::reports()`: builds `new File($file)` from bare file names, so the "delete reports older than 24h" never deleted anything (3.x and now). Kept.
- `AgentsController::index()` (`/agents`) loads a `Search` component that doesn't exist — fatal in 3.x too.
- `OrdersController::addToCart/subtractQuantity/removeFromCart` call `_ajaxResponse()` (not defined) and `$this->Products` — not routed, dead code.
- `S3PublicComponent` uses `Mimey\MimeTypes`, which isn't installed (also not in the 3.x lock).
- `VouchersController` ≈ line 177 uses undefined `$id`.
- `TicketsTable::isValidReturnTicket()`: `find('list', ['type' => 'return'])` — `type` was never applied (probably meant `->where(['type' => 'return'])`). Kept.
- `AgentTopupsTable` validates `image` with `scalar()`, which rejects an uploaded file (3.x array / 5.x object) — check in smoke test whether top-ups with an image save.
- `AgentsSignupsTable` `MasterAgents` association has no `className` (targets non-existent `master_agents`); clashes with `AgentsTable`'s `MasterAgents` if both load. Only `index()` (not routed) uses it.
- Baked associations to non-existent tables in the local DB (`records`, `txns`, `jobs`, `package_tickets`, `fixeddates_agents`).
