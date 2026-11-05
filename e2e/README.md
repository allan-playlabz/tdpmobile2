# tdpmobile2 – Playwright E2E tests

End-to-end browser tests for the tdpmobile2 B2B agent portal (CakePHP 5). They live in the app
repo's `e2e/` folder (a separate Node project, next to the PHPUnit `tests/`) and only talk to the
app over HTTP, so they never change the app's files. `e2e/.gitignore` keeps `.env` (passwords,
authenticator secrets), `.auth/`, `node_modules/`, test results and `placed-orders.jsonl` out of git.

## Setup

```bash
cd C:\laragon\www\tdpmobile2\e2e
npm install
npx playwright install chromium
copy .env.example .env      # then fill it in
```

Prerequisites on the app side (local):

- The app runs at `BASE_URL` (default `http://tdpmobile2.localhost`, the Laragon Apache vhost;
  start Laragon first). The session cookie is bound to the app's `COOKIE_DOMAIN`, which must match
  that host (`tdpmobile2.localhost`), and set `SESSION_COOKIE_SECURE=false` for plain http.
  Alternative: the PHP dev server
  `C:\laragon\bin\php\php-8.5.12-nts-Win32-vs17-x64\php.exe -S 127.0.0.1:8765 -t webroot webroot/index.php`
  (from the app folder, or `E2E_START_SERVER=true`) with `BASE_URL=http://tpp.localhost:8765` and
  `COOKIE_DOMAIN=tpp.localhost`.
- A **main test account** (`E2E_USER_*`) that can log in without an emailed OTP. Either
  2FA is off for it (`agents.mobile_otp = 0` or `agent_users.two_factor_enabled = 0`), or it uses
  the authenticator method and you put its key in `E2E_USER_TOTP_SECRET` (the key under
  *"Can't scan? Enter this key instead"* on the setup page; set it up once by hand).
  Emailed OTP codes cannot be automated.
- Optional **second account** (`E2E_2FA_*`) with the authenticator already set up, for the 2FA tests.
  It must be a different user: a login ends that user's other sessions.
- Optional `E2E_ATTRACTION_ID`: an attraction with bookable tickets (not two-way, ideally no
  passport/transfer fields) for the cart/checkout tests.

### Ordering + menu pages

The `ordering` and `pages` projects run against the local `tdpmobile2.localhost` app with a
dedicated test agent:

- `BASE_URL`'s host must be in `E2E_ALLOWED_HOSTS` (default
  `tdpmobile2.localhost,tpp.localhost,127.0.0.1,localhost`). The login setup aborts the whole run
  for any other host, so a mistyped URL can't place orders on production.
- The local app talks to whatever backend API (`Api.url`) and payment gateways `config/app_config.php`
  points at — make sure those are test/sandbox ones, since checkouts create orders there.
- One attraction id per pricing type: `E2E_ATTR_SETTLEMENT`, `E2E_ATTR_FDATE`, `E2E_ATTR_FDATETIME`,
  `E2E_ATTR_TWO_WAY`, `E2E_ATTR_QUESTIONS` (booking questions), optional `E2E_ATTR_SECOND`.
- `E2E_PAYMENT_METHODS` = the online methods enabled for the test agent (tests stop at the gateway page).
- Optional `E2E_NORMAL_*` (a non-master user of the same agent) and `E2E_LOW_CREDIT_*`.
- The queue server (`Mailer.api_url`) must be reachable, or the invoice tests fail.

```bash
npx playwright test --project=ordering --project=pages     # read-only apart from unpaid checkouts
```

**Every successful checkout creates an unpaid order** on the backend (that is how the app works),
even without `E2E_ALLOW_PURCHASE`. All orders a run creates are listed in the HTML report
(annotation "order") and appended to `placed-orders.jsonl`, so they can be cleaned up.

## Running

Run all commands from `C:\laragon\www\tdpmobile2\e2e`. Tests that need data or credentials you
haven't configured are **skipped** with a reason, not failed.

### Everything, or one project

```bash
npm test                                      # every project (places orders if E2E_ALLOW_PURCHASE=true)
npm run test:public                           # no login needed
npm run test:app                              # logged-in pages (runs the login setup first)
npx playwright test --project=ordering        # pricing types, cart, checkout, payments, purchases
npx playwright test --project=pages           # menu pages; runs `ordering` first (it depends on it)
npm run test:2fa                              # authenticator login (second account)
npm run test:mobile                           # Pixel 7 viewport
npx playwright test --project=session         # logout / redirect; runs app, mobile, ordering, pages first
```

A project's dependencies run first (`setup` logs in, and `pages` needs the orders `ordering`
placed). To skip them, add `--no-deps`. You need a saved login in `.auth/user.json` from an earlier
run for that, and the `pages` tests that look for this run's orders will skip:

```bash
npx playwright test --project=pages --no-deps   # menu pages without placing any orders
```

### One file

Give the file path together with its project. Without `--project`, a file that two projects match
(such as `tests/app/navigation.spec.ts`, used by `app` and `mobile`) runs once per project.

```bash
npx playwright test --project=ordering tests/ordering/pricing-types.spec.ts   # each pricing type up to confirmation (unpaid)
npx playwright test --project=ordering tests/ordering/cart-rules.spec.ts
npx playwright test --project=ordering tests/ordering/checkout.spec.ts
npx playwright test --project=ordering tests/ordering/pay-online.spec.ts      # stops at the gateway, never pays
npx playwright test --project=ordering tests/ordering/pay-credits.spec.ts     # 1 real credits order
npx playwright test --project=ordering tests/ordering/purchase.spec.ts        # 8 real credits orders (see below)
npx playwright test --project=app tests/app/booking.spec.ts
```

`post-order.spec.ts` checks the order `pay-credits.spec.ts` paid for **in the same run**, so run
the two together:

```bash
npx playwright test --project=ordering tests/ordering/pay-credits.spec.ts tests/ordering/post-order.spec.ts
```

### One test, or a group of tests, by name

`-g` matches part of the test title (a regular expression). Add `--project` so the match doesn't
also run the same title in other projects.

```bash
npx playwright test --project=ordering -g "1 settlement"                  # one purchase
npx playwright test --project=ordering -g "1 fdate:|1 fdatetime"          # several purchases
npx playwright test --project=ordering -g "two-way"                       # every two-way test
npx playwright test --project=ordering -g "settlement \+ "                # settlement + fdate, settlement + fdatetime
npx playwright test --project=ordering -g "Purchase with credits"        # the whole purchase suite
npx playwright test -g "wrong code is rejected"
npx playwright test --project=ordering --grep-invert "Purchase|Pay with credits"   # ordering without paying
npx playwright test --project=ordering --list                             # list test titles without running
```

### Purchases (`tests/ordering/purchase.spec.ts`)

These are full purchases paid with agent credits. Each test places **one real paid order**: 1
settlement, 1 fdate, 1 fdatetime, 1 two-way (two_way_fdatetime), settlement + fdate,
settlement + fdatetime, fdate + fdatetime, and a quantity of 2 of the first configured type. Every
test goes from cart through checkout and Pay Now with credits to the thank-you page, then checks
the order detail page (paid with Credits, ticket lines, quantity, Grand Total = amount paid).

They need `E2E_ALLOW_PURCHASE=true`, plus the attraction ids `E2E_ATTR_SETTLEMENT`, `E2E_ATTR_FDATE`,
`E2E_ATTR_FDATETIME` and `E2E_ATTR_TWO_WAY` in `.env`. A purchase skips when its attraction isn't set,
when the agent doesn't have enough credits, or when the app refuses a mixed cart. For example, an
attraction with tickets from an outside supplier can't be in the same cart as another merchant's
tickets; the skip reason shows the app's message.

### Watching and debugging

```bash
npm run test:headed -- --project=ordering -g "1 fdatetime"   # open a browser window
npm run test:ui                                               # Playwright UI mode: pick, run and step through tests
npx playwright test --project=ordering -g "1 fdate:" --debug  # Playwright Inspector, step by step
npm run report                                                # open the last HTML report (failures have trace, screenshot, video)
npx playwright show-trace test-results/<test-folder>/trace.zip
npm run typecheck                                             # TypeScript check without running anything
```

## What is covered

| Project | Files | Covers |
|---|---|---|
| `public` | `tests/public/login.spec.ts` | Login form, generic error for bad email/password, forgot password, signup link, zh_CN/en_US switch |
| | `tests/public/access-control.spec.ts` | 16 protected URLs redirect to login with `?redirect=`, OTP/2FA pages need a pending login, removed `/agents` and fallback routes are 404, bad voucher signature, CSRF rejects missing/forged tokens |
| | `tests/public/signup.spec.ts` | Signup form, browser validation, thank-you page |
| `setup` | `tests/auth.setup.ts` | Logs in once (password, plus authenticator code if configured), saves `.auth/user.json` |
| `app` | `tests/app/navigation.spec.ts` | Navbar, credits, role-based user menu, **every menu page loads without PHP warnings/errors or JS errors**, language persists |
| | `tests/app/attractions.spec.ts` | Listing, search/no results/Show All, pagination, category filter, detail page, AJAX ticket load, favourite toggle (restored), unknown id |
| | `tests/app/booking.spec.ts` | Empty cart, add ticket, +/- quantity, remove (confirm / dismiss), checkout HTML validation, checkout → confirmation, payment-method prompt, optional credits purchase → thank-you |
| | `tests/app/orders-reports.spec.ts` | My Orders / My Tickets filters, order detail, unknown order, Sales Reports, report path traversal, master pages (Top Up, Balance Report, Agent Users list/sort/add, reset-password confirm), non-master refused |
| `ordering` | `tests/ordering/pricing-types.spec.ts` | Settlement, fixed date, fixed date + time slot and two-way: cart line (date/time/return leg/unit price), confirmation total = cart total, unavailable date not pickable, slot required, return before departure refused |
| | `tests/ordering/cart-rules.spec.ts` | Badge + total with two tickets/dates, over max quantity refused (cart unchanged), Buy Now → cart, quantity survives reload, removing one date keeps the other, cart after logout/login |
| | `tests/ordering/checkout.spec.ts` | Required fields, email/mobile format, tracking number (reused one refused), booking questions required and shown on confirmation, customer details and answers kept on Back (tracking number not) |
| | `tests/ordering/pay-credits.spec.ts` | Pay with credits → thank-you ref, header credits drop by the total, order in My Orders (`E2E_ALLOW_PURCHASE`); low-credit account refused (`E2E_LOW_CREDIT_*`) |
| | `tests/ordering/pay-online.spec.ts` | Per `E2E_PAYMENT_METHODS`: fee and grand total, Pay Now lands on the gateway host (2C2P / Stripe / PayPal), Back → order unpaid and credits unchanged; Pay Now without a method. **Never pays.** |
| | `tests/ordering/post-order.spec.ts` | The credits order from pay-credits: detail items/totals, invoice is a PDF (`INVOICE_<ref>.pdf`), voucher PDFs, download-all, My Tickets codes, cancel (`E2E_ALLOW_CANCEL`), no cancel on a closed voucher |
| | `tests/ordering/purchase.spec.ts` | Full credits purchases, one real order each (`E2E_ALLOW_PURCHASE`): 1 settlement, 1 fdate, 1 fdatetime, 1 two-way, settlement + fdate, settlement + fdatetime, fdate + fdatetime, quantity 2. Checks cart total, confirmation total, thank-you ref, order detail (Credits, ticket lines, quantity, Grand Total) and an empty cart |
| | `tests/ordering/roles.spec.ts` | Normal user sees only own orders vs master sees the agent's (`E2E_NORMAL_*`), viewer kept out of cart/checkout |
| `pages` | `tests/pages/my-orders.spec.ts` | This run's orders listed (date/status/total), ref/date/status filters, empty result, newest first + pagination, row → detail, unknown ref refused |
| | `tests/pages/my-tickets.spec.ts` | This run's voucher codes listed, code/date filters, voucher link is a PDF, a garbage `?from=` is ignored, Show All goes back to My Tickets |
| | `tests/pages/reports.spec.ts` | Sales Report form defaults, empty ranges → "No transactions found.", generated reports list, generate + download CSV (`E2E_ALLOW_REPORTS`), download path traversal |
| | `tests/pages/balance-report.spec.ts` | Credits debit of this run's orders, date filter, download refused without range / over 31 days, CSV = table, normal user refused (`E2E_NORMAL_*`) |
| | `tests/pages/topups.spec.ts` | List + express top-up bank details, view, missing / non-image proof refused, submit a 1x1 PNG (`E2E_ALLOW_TOPUP`), CSV, non-master refused |
| | `tests/pages/agent-users.spec.ts` | Sub-users only, sort links (asc/desc order), add-form validation (browser, server, duplicate email), refused actions, throwaway user add → edit → reset password / 2FA → suspend (`E2E_ALLOW_USER_ADMIN`), non-master refused |
| | `tests/pages/account.spec.ts` | My Account, change password (wrong old; change + restore with `E2E_ALLOW_PASSWORD_CHANGE`), Contact Us validation / send, two-factor settings page |
| | `tests/pages/header.spec.ts` | Logo, cart badge, credits = My Account, LOW badge (`E2E_LOW_CREDIT_*`), announcements popover / unread dot (read call answered locally), language switch keeps path + query |
| `two-factor` | `tests/two-factor/authenticator-login.spec.ts` | Password step doesn't log in, wrong/right code, code replay refused, `?redirect=` kept through 2FA, settings page, backup code single use |
| `session` | `tests/session/logout-and-redirect.spec.ts` | Logout, return to requested page after login, off-site redirect targets ignored, one session per user |
| `mobile-public`, `mobile` | login + navigation specs | Same checks at phone size (collapsed navbar) |

## Side effects

By default the suite only reads, plus these writes: unpaid orders from the ordering checkouts (see above), failed-login records (throwaway emails,
and at most two wrong passwords for the main user, below the 5-per-3-minutes limit), session rows,
the cart, a favourite toggled and restored, and login logs.

Opt-in only:

- `E2E_ALLOW_PURCHASE=true` – places real orders paid with agent credits: 1 in `pay-credits.spec.ts`
  and up to 8 in `purchase.spec.ts` on every full `ordering` run.
- `E2E_ALLOW_CANCEL=true` – cancels a voucher of the order this run paid for.
- `E2E_ALLOW_EMAIL=true` – sends a Contact Us email.
- `E2E_2FA_BACKUP_CODE` – uses up that backup code; replace it before the next run.
- `E2E_ALLOW_REPORTS=true` – generates a Sales Report on the server.
- `E2E_ALLOW_TOPUP=true` – submits a 1.00 SGD top-up request with a 1x1 PNG (remarks say "please reject").
- `E2E_ALLOW_USER_ADMIN=true` – creates a sub-user `e2e-<timestamp>@example.invalid`, renames it, resets its password and authenticator, then suspends it (users can't be deleted).
- `E2E_ALLOW_PASSWORD_CHANGE=true` – changes the main password and changes it back (in `finally`).

Never submitted without a flag: signup, top-up, add user, reset password/authenticator, change password (only a wrong old password),
Sales Report generation. Rejected forms (no image, bad email, duplicate email, empty range) are submitted; they save nothing.

## Notes

- Runs with **one worker**: the cart and the one-session-per-user rule make parallel runs with
  the same account unreliable.
- The `session` project runs last because it logs the main account out and in again.
- The app refuses a reused authenticator time step, so `utils/totp.ts` remembers the last step
  per secret in `.auth/totp-steps.json` and waits up to 30 s for a fresh one when needed.
  Keep the PC clock in sync.
- Page objects are in `pages/`, shared checks (`expectHealthyPage` catches CakePHP error pages and
  PHP warnings/notices/deprecations) in `utils/checks.ts`.
- `npm run codegen` opens the Playwright recorder on the login page for writing new tests.
