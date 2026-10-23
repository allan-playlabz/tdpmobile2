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
- [ ] cake5-structure
- [ ] cake5-code-apis
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

## Found while upgrading

## Decisions (2026-10-23)
- Backup files `src/Controller/OrdersController - Copy.php` and `ignore/` deleted (kept in git history).
- Phase 5 gate: suite runs to completion; failures triaged and listed, not necessarily all green. HTTP smoke test is the primary check.
- Auth: like-for-like `cakephp/authentication` (Session + Form on `AgentUsers`, email/password, same login/logout URLs); no authorization plugin.
