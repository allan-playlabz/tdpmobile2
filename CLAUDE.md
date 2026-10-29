# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

B2B agent portal (mobile front end) for booking attraction tickets, built on **CakePHP 5.4** / PHP ≥ 8.3 (`App\` namespace → `src/`). Agents (companies) have agent users who log in, browse attractions, add tickets to a cart, check out (paying with prepaid agent credits or an online gateway), and download vouchers/invoices. Default currency is SGD; UI is translatable (`en_US`, `zh_CN` via `resources/locales/`, switched with `?l=` query param).

Runs locally under Laragon (`C:\laragon\www\tdpmobile2`). The app was upgraded from CakePHP 3.10 — see "CakePHP 5 migration notes" below and `docs/UPGRADE_PROGRESS.md`.

## Commands

```bash
composer install
bin/cake server -p 8765                 # dev server; browse http://tpp.localhost:8765 (session cookie domain, see below)
composer test                           # phpunit 11 (tests/TestCase)
vendor/bin/phpunit tests/TestCase/Controller/AuthenticationTest.php                    # single file
vendor/bin/phpunit --filter testProtectedPageRedirectsToLogin tests/TestCase/Controller/AuthenticationTest.php  # single test
composer cs-check                       # phpcs (CakePHP standard, phpcs.xml)
composer cs-fix                         # phpcbf
bin/cake generate_report --agent_user_id=... --start_date=... --end_date=...   # src/Command/GenerateReportCommand.php
bin/cake delete_temp                    # cron: removes generated PDFs/reports older than 2 days
php .claude/skills/cake5-audit/scripts/scan.php .   # CakePHP 5 leftover scan (exit 1 on blockers)
```

Tests: schema comes from `tests/schema/app.sql` + `tests/schema/log.sql` (schema-only dumps, loaded by `tests/bootstrap.php`; regenerate when the DB schema changes). Test connections `test` / `test_log_ds` / `test_log_ds_ro` / `test_database_log` use `DATABASE_TEST_URL` / `DATABASE_TEST_LOG_URL`. Most model/controller tests are baked stubs (`markTestIncomplete`); `ApplicationTest` and `AuthenticationTest` are real.

## Configuration

- `config/app.php` – framework config (datasources, cache, DB sessions, DbLog logging), based on the 5.4 skeleton.
- `config/app_config.php` – loaded after `app.php` (see `config/bootstrap.php`); this project's local override (instead of the skeleton's `app_local.php`). Template: `config/app_config.example.php`. Key groups: `Datasources` (`default`, `log_ds`, `log_ds_ro`, `database_log`), `Api` (backend API), `AWS`/`AWSPublic` (S3), `Mailer` (queue server), `PdfGenerator`, `Translation.languages`, `Login` (rate limiting), `Features`, `TDP`.
- `config/.env` is loaded by bootstrap if `APP_NAME` isn't set (DB URLs, `COOKIE_DOMAIN`, …). Locally `COOKIE_DOMAIN=tpp.localhost`, so the session only works on that host.
- `config/plugins.php` registers plugins (Authentication, CakePdf, DatabaseLog, DebugKit/Bake/Migrations dev/CLI only).
- `app_config.php`, `.env` and `config/.setenv.properties` (Apache `SetEnv`, untracked; template `config/.setenv.properties.example`) contain real credentials — never echo or commit their values.
- Security-related settings: `SECURITY_SALT` (required — bootstrap throws without it), `DEBUG` (defaults to false), `APP_FULL_BASE_URL` (set in production so absolute links don't come from the Host header), `SESSION_COOKIE_SECURE` (default true; `false` only for local http), `Vouchers.allowLegacyMd5Links` (accept old `md5(id.Api.secret)` voucher links; new format is HMAC-SHA256), `Security.cfConnectingIp` (`cloudflare` default / `always` / `never` — when to trust `CF-Connecting-IP`, see `src/Util/ClientIp.php`).
- Local CLI: the `php` on PATH may not be 8.x; use the Laragon PHP 8.5 binary for `bin/cake`, `phpunit` and the dev server (`php -S localhost:8765 -t webroot webroot/index.php`).

## Architecture

**Routing** (`config/routes.php`): every route is explicitly connected; `fallbacks()` is disabled, so **a new controller action is unreachable until a route is added**. CSRF is scoped route middleware on the `/` scope (`skipCheckCallback` for `Orders::notificationReceiver` / `frontendNotificationReceiver`). Payment gateway callbacks (`/notification-receiver/{provider}`, `/frontend-notification-receiver/{provider}/{order_ref_id}`, `/orders/sps`) are separate scopes connected before `/`, without CSRF.

**Auth** (`src/Application.php`, `src/Controller/AppController.php`): Authentication plugin with **only the Session authenticator** on session key `Auth.User` (the 3.x AuthComponent layout, read directly by `CustomDatabaseSession`, `LoggableBehavior`, `CreditsCell`). `AgentUsersController::login()` checks credentials itself via `AppController::identifyAuthUser()` and logs in with `setAuthUser()` only after the OTP / trusted-IP checks — don't add the Form authenticator (it would log in before OTP). Use `getAuthUser('dot.path')` for the current user (session read, like the old `Auth->user()`), `setAuthUser()` to log in / refresh the stored user, `logoutAuthUser()`, and `$this->Authentication->addUnauthenticatedActions()` for public actions. **Two-factor** (`src/Lib/TwoFactor.php`): applies when `agents.mobile_otp` AND `agent_users.two_factor_enabled`; `agent_users.two_factor_method` is `authenticator` (TOTP app, default; code at every login, backup codes; first login = emailed code → `/agent-users/two-factor/setup`) or `email_otp` (emailed code, skipped on the user's last 3 trusted IPs). Switch/method are set in the DB/back office only. Until the second factor passes the user is not logged in — only session `agent_id` (+ `TwoFactor.*` flags) is set; `_completeLogin()` finishes. TOTP secrets are encrypted with `TwoFactor.encryptionKey` (`TWO_FACTOR_KEY` env — never change it). Masters reset sub-users from Agent Users; anyone else via `bin/cake two_factor reset <email>`. Schema change: `config/schema/two_factor.sql`. There is no authorization layer (`isAuthorized()` is always true); role checks are inline in controllers. `AppController::beforeFilter` reloads the agent on every request to inject live `credits`, `allowed_api_cancel`, announcements and OTP flags into the `auth_user` view var — use `auth_user` in templates.

**Sessions**: stored in the database via `src/Http/Session/CustomSession.php` (extends `CustomDatabaseSession`, model `AgentSessions`); writing a session for a logged-in user deletes that user's other sessions (one session per agent user). The shopping cart (`ShoppingCartComponent`) lives in the session, keyed by ticket id + date.

**Two data sources**:
1. Local MySQL tables via the ORM (`src/Model/Table`) – attractions, tickets, orders, vouchers, agents, credit logs, payment config, etc. Primary keys are binary UUIDs (`binaryuuid`).
2. A remote backend API called through `src/Lib/ApiCaller.php` (base `Api.url`), signed with HMAC-SHA256 headers derived from `Api.secret` and the agent user's email/id. Used by `AttractionsController` and `OrdersTable` for availability/booking with suppliers.

**Pricing types** (`src/Lib/PricingType.php`): `settlement`, `fdate`, `fdatetime`, `two_way_fdatetime`. Ticket/attraction flows branch on these (fixed-date tickets, two-way trips, `Fixeddates*`/`Fdatesettlements*`/`Settlements*` tables); check the pricing type before touching cart, availability or checkout logic.

**Checkout & payments** (`src/Controller/OrdersController.php`, the largest file): `cart` → `checkout` → `confirmation` → payment → `completeOrder` → `thankYou`. Credits are handled in the controller (writes `CreditLogs`); other methods go through `src/Lib/Payments.php` → `Payment2C2P` / `PaymentStripe` / `PaymentPaypal`. Gateway settings and markups are **database-driven**: `Payments::config()` (called in `OrdersController::initialize`) reads `PaymentMethods`/`PaymentGateways`/`PaymentMarkups` into `Configure` under `Payment.*`.

**Async work**: `src/Lib/QueueSender.php` posts HMAC-signed requests to a queue/mailer server (`Mailer.api_url`). `S3Component` / `S3PublicComponent` handle private vs public file storage (vouchers, top-up images, reports). Mail goes through `MailerComponent` / `App\Util\CatMailer` (`Cake\Mailer\Mailer`).

**Logging**: the `debug` and `error` log configs use `App\Log\Engine\DbLog` (DatabaseLog plugin, `log_ds` datasource). Log messages must be strings in 5.x — `json_encode()` arrays before logging. `ApiCaller` logs every request/response at debug level.

**PDFs**: vouchers/invoices via CakePdf (WkHtmlToPdf engine, `templates/Pdf`, `templates/layout/pdf`) or an external `PdfGenerator` service; barcodes/QR via picqer and phpqrcode.

## CakePHP 5 migration notes

- Upgraded 3.10.5 → **5.4.3** (PHP ≥ 8.3) on branch `upgrade/cake5`; phase log, decisions and open issues in `docs/UPGRADE_PROGRESS.md`.
- Layout: templates in `templates/` (`.php`; `element/flash`, `layout/email`, `Error/` capitalised), locales in `resources/locales/`. Shells are Commands (`bin/cake delete_temp` name unchanged).
- Shims: `App\Filesystem\File` / `Folder` (3.x semantics, replace the removed `Cake\Filesystem`). `AppController` has `#[\AllowDynamicProperties]` so `$this->X = $this->fetchTable('X')` (old `loadModel()`) keeps working; also `paginateToArray()` for actions that post-process paginated rows (keeps PaginatorHelper working).
- XHR requests render with `App\View\AjaxView` (done in `AppController::beforeRender()`, replacing RequestHandler); JSON bodies are parsed by `BodyParserMiddleware`.
- Gotchas found here: dates are immutable (always reassign `$d = $d->addDays(1)`); `date` columns hydrate to `Cake\I18n\Date` (not a `DateTimeInterface` — convert with `new DateTime($date->format('Y-m-d'))` for arithmetic/comparison); `where(['col' => null])` throws; paginate settings only honour `order`/`limit`/`page` (put conditions/contain on the query); uploads are `UploadedFile` objects; queries need `->all()` before `toList()/extract()`.
- Not adopted from the 5.4 skeleton: global CSRF middleware, `allowFallbackClass(false)` (tables like `VoucherTemplates` have no class), `HostHeaderMiddleware` (needs `App.fullBaseUrl` per environment).
- No composer patches; no plugins replaced. The 3.x AuthComponent was replaced by `cakephp/authentication` (see Auth above).

## Repo notes

- The UI uses Bootstrap, jQuery plugins and jsGrid from `webroot/`.
- `.claude/skills/cake5-*` are the upgrade skills used for the migration (audit/scan can be re-run to catch leftovers).
