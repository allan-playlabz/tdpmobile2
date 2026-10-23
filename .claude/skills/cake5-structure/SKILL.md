---
name: cake5-structure
description: Phase 2 of a CakePHP 3 → 5 upgrade — moves src/Template to templates/ (.ctp → .php; lowercase layout/element/email/cell, but Error stays capitalised), converts Shells to Commands, replaces untouched skeleton files with the 5.x skeleton's, and updates Application.php, config/bootstrap.php, paths.php, plugins.php, routes.php and the entry points. Use when restructuring a CakePHP project's files for CakePHP 5, or when asked to convert shells to commands.
user-invocable: true
---

# Phase 2 — Structure

Goal: the project has the CakePHP 5 file layout and boots far enough that `bin/cake` lists commands. Run after `cake5-deps` (which downloads the 5.x skeleton to `../app5`).

## 1. Templates
```bash
bash .claude/skills/cake5-structure/scripts/move_templates.sh <project> --dry-run   # preview (later paths show pre-rename names; the real run is sequential)
bash .claude/skills/cake5-structure/scripts/move_templates.sh <project>
```
It does, with `git mv` (history kept, case-only renames via a temp name so it works on Windows/macOS):
- `src/Template` → `templates/`, `*.ctp` → `*.php`;
- `Layout/ Element/ Email/ Cell/ Plugin/` → `layout/ element/ email/ cell/ plugin/`;
- **`Error/` stays `Error/`** — it is `ErrorController`'s template path (`templates/Error/error400.php`). Lowercasing it works on Windows but breaks custom error pages on Linux. (Already have `templates/error/`? `git mv templates/error templates/Error__tmp && git mv templates/Error__tmp templates/Error`.)

Then:
- `config/app.php`: `'App' => ['paths' => ['templates' => [ROOT . DS . 'templates' . DS]]]`.
- Email templates: `templates/email/html|text/`; email layouts: `templates/layout/email/html|text/`.
- Search code for hard-coded paths: `grep -rn "Template" src config | grep -v "templates"` (e.g. `APP . 'Template'`, `->setTemplatePath('Email')`).
- Commit this step on its own — it touches every template.

## 2. Replace untouched skeleton files
Projects carry files they never edited from the 3.x skeleton; several call removed APIs (the 3.x `Pages/home` template calls `Connection::connect()`, the 3.x tests extend `IntegrationTestCase`). For each file below, if the project never changed it (compare with the 3.x skeleton, or `git log --follow` shows only the initial commit), **take the 5.x skeleton's version** from `../app5`; otherwise port the project's changes into the 5.x version:
- `src/Application.php`, `src/Controller/AppController.php` (keep project code!), `ErrorController.php`, `PagesController.php`, `src/View/AppView.php`, **`src/Middleware/*`** (the 5.4 `Application.php` uses `App\Middleware\HostHeaderMiddleware` — copy it or every web request fatals)
- `config/app.php`, `paths.php`, `plugins.php`, `bootstrap.php`, `routes.php`, `app_local.example.php`
- `webroot/index.php`, `bin/cake.php` (and `bin/cake`, `bin/cake.bat`), `webroot/css/*` if unchanged
- `templates/Pages/home.php`, `templates/layout/{default,error,ajax}.php`, `templates/layout/email/*`, `templates/email/*/default.php`, `templates/Error/*`
- `tests/bootstrap.php`, `phpunit.xml.dist`, `tests/TestCase/ApplicationTest.php`, `tests/TestCase/Controller/PagesControllerTest.php` (see `cake5-tests`)
- Not in the 5.4 skeleton — remove if unused: `config/requirements.php`, `config/bootstrap_cli.php`, `src/Shell/ConsoleShell.php`, `src/View/AjaxView.php`.

## 3. Shells → Commands
For each `src/Shell/*Shell.php` (and `src/Shell/Task/*`), create `src/Command/<Name>Command.php`. Recipe: [references/shell-to-command.md](references/shell-to-command.md).
- The command name stays the same if the class basename matches (`DigestShell` → `DigestCommand` → `bin/cake digest`) — **verify every cron entry / supervisor job** against `bin/cake --help` and list changes for the user.
- Existing 3.x/4.x Commands: `Cake\Console\Command` → `Cake\Command\Command`.
- Delete `src/Shell/` only after every command is converted and run once with `--help`. (Running them for real usually needs phase 3 — Table classes with 3.x signatures fatal on load.)

## 4. Application, bootstrap, routes, entry points
Port the project's customisations into the 5.x shape. Snippets: [references/skeleton-files.md](references/skeleton-files.md).

- **`src/Application.php`** — plugins via `config/plugins.php` (5.4) or `$this->addPlugin()` in `bootstrap()` (replaces `Plugin::load()`); `middleware()` with ErrorHandler → HostHeader (5.4) → Asset → Routing → BodyParser → CSRF (→ Authentication, see `cake5-auth`).
  - The 5.4 skeleton applies **CSRF globally** in the middleware queue; many older apps apply it as **scoped route middleware** in `routes.php`. Global is fine unless the app receives webhooks/API calls — then keep CSRF scoped (see gotchas) or use `skipCheckCallback`.
  - The 5.4 skeleton also sets `FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false))`. Legacy apps often rely on auto-generated tables for join tables or tables without a class — remove that line, or create the missing Table classes.
- **`config/bootstrap.php`** — `ErrorTrap`/`ExceptionTrap` instead of `ErrorHandler`/`ConsoleErrorHandler`; `Mailer::setConfig` / `TransportFactory::setConfig` instead of `Email::setConfig`; remove `useImmutable()` calls; **global functions**: `require CAKE . 'functions.php';` (one line loads `h()`, `__()`, `env()`, `debug()`, collection and routing helpers) and `use function Cake\Core\env;`.
- **`config/routes.php`** — `return static function (RouteBuilder $routes): void { ... }`; `Router::scope()` → `$routes->scope()`; `:id` → `{id}`; `'httponly'` lowercase. **Webhook/API routes that must skip CSRF go in their own scope connected before `/`** (when CSRF is scoped).
- **`webroot/index.php`**, **`bin/cake.php`** — skeleton versions (`Server` / `CommandRunner` with `new Application(dirname(__DIR__) . '/config')`).

## Gate
- `php bin/cake.php --help` lists the app's commands under their old names (a DB connection error at boot means the datasource config, not structure — see gotchas).
- `php .claude/skills/cake5-audit/scripts/scan.php . --phase=cake5-structure` → 0 blockers.
- Commit: `upgrade(cake5): structure`.
