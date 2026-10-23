# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

B2B agent portal (mobile front end) for booking attraction tickets, built on **CakePHP 3.10** (`App\` namespace → `src/`). Agents (companies) have agent users who log in, browse attractions, add tickets to a cart, check out (paying with prepaid agent credits or an online gateway), and download vouchers/invoices. Default currency is SGD; UI is translatable (`en_US`, `zh_CN` via `src/Locale/`, switched with `?l=` query param).

Runs locally under Laragon (`C:\laragon\www\tdpmobile2`). Note the CLI PHP may be newer than what CakePHP 3.10 officially supports; deprecation noise under the CLI is expected.

## Commands

```bash
composer install
bin/cake server -p 8765                 # built-in dev server
composer test                           # phpunit (all of tests/TestCase)
vendor/bin/phpunit tests/TestCase/Controller/OrdersControllerTest.php            # single file
vendor/bin/phpunit --filter testCheckout tests/TestCase/Controller/OrdersControllerTest.php  # single test
composer cs-check                       # phpcs, CakePHP standard, src/ + tests/
composer cs-fix                         # phpcbf
bin/cake generate_report --agent_user_id=... --start_date=... --end_date=...   # src/Command/GenerateReportCommand.php
bin/cake bake ...                       # scaffolding (dev only)
```

Tests use fixtures in `tests/Fixture/` and the `test` datasource from `config/app.php`; many baked tests are still stubs.

## Configuration

- `config/app.php` – framework config (datasources, cache, session, logging). It is environment-specific, not the skeleton default.
- `config/app_config.php` – loaded after `app.php` (see `config/bootstrap.php`), overrides/adds app-specific keys. Template: `config/app_config.example.php`. Key groups: `Datasources` (`default`, `log_ds`, `log_ds_ro`), `Api` (url/secret/timeout for the backend API), `AWS`/`AWSPublic` (S3), `Mailer` (queue server), `PdfGenerator`, `Translation.languages`, `Login` (rate limiting), `Features`, `TDP` (image base / paths).
- `config/.env` is loaded by bootstrap if `APP_NAME` isn't set.
- Both `app.php` and `app_config.php` contain real credentials — never echo or commit their values.

## Architecture

**Routing** (`config/routes.php`): every route is explicitly connected; `fallbacks()` is disabled, so **a new controller action is unreachable until a route is added**. CSRF middleware is applied to the `/` scope, with a whitelist for `Orders::notificationReceiver` / `frontendNotificationReceiver`. Payment gateway callbacks (`/notification-receiver/{provider}`, `/frontend-notification-receiver/{provider}/{order_ref_id}`, `/orders/sps`) live in separate scopes without CSRF.

**Auth** (`src/Controller/AppController.php`): `AuthComponent` with Form auth against `AgentUsers` (email/password). `isAuthorized()` returns true globally; per-controller `beforeFilter` decides allowed actions. `AppController::beforeFilter` reloads the agent on every request to inject live `credits`, `allowed_api_cancel`, announcements and OTP flags into the `auth_user` view var — use `auth_user` in templates rather than re-querying.

**Sessions**: stored in the database via `src/Http/Session/CustomSession.php` (extends `CustomDatabaseSession`, model `AgentSessions`). The shopping cart (`ShoppingCartComponent`) lives in the session, keyed by ticket id + date.

**Two data sources**:
1. Local MySQL tables via the ORM (`src/Model/Table`) – attractions, tickets, orders, vouchers, agents, credit logs, payment config, etc.
2. A remote backend API called through `src/Lib/ApiCaller.php` (base `Api.url`), signed with HMAC-SHA256 headers derived from `Api.secret` and the agent user's email/id. Used by `AttractionsController` and `OrdersTable` for availability/booking with suppliers.

**Pricing types** (`src/Lib/PricingType.php`): `settlement`, `fdate`, `fdatetime`, `two_way_fdatetime`. Ticket/attraction flows branch on these (e.g. fixed-date tickets, two-way trips, `Fixeddates*`/`Fdatesettlements*`/`Settlements*` tables); check the pricing type before touching cart, availability or checkout logic.

**Checkout & payments** (`src/Controller/OrdersController.php`, the largest file): `cart` → `checkout` → `confirmation` → payment → `completeOrder` → `thankYou`. Payment by agent credits is handled in the controller (writes `CreditLogs`); other methods go through `src/Lib/Payments.php`, which dispatches to `Payment2C2P`, `PaymentStripe`, or `PaymentPaypal`. Gateway settings and markups are **database-driven**: `Payments::config()` (called in `OrdersController::initialize`) reads `PaymentMethods`/`PaymentGateways`/`PaymentMarkups` and writes them into `Configure` under `Payment.*`.

**Async work**: `src/Lib/QueueSender.php` posts HMAC-signed requests to a queue/mailer server (`Mailer.api_url`) for emails and similar side effects. `S3Component` / `S3PublicComponent` handle private vs public file storage (vouchers, top-up images, reports).

**Logging**: the `debug` and `error` log configs use `App\Log\Engine\DbLog`, writing to the `log_ds` datasource rather than only to `logs/`. `ApiCaller` logs every request/response at debug level.

**PDFs**: vouchers/invoices via CakePdf/dompdf (templates in `src/Template/Pdf`) or an external `PdfGenerator` service; barcodes/QR via picqer and phpqrcode.

## Repo notes

- `ignore/` and files like `src/Controller/OrdersController - Copy.php` are old backups, not live code — don't edit them or treat them as reference for current behaviour.
- Views are `.ctp` templates in `src/Template/`; the UI uses Bootstrap, jQuery plugins and jsGrid from `webroot/`.
