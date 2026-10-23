---
name: cake5-deps
description: Phase 1 of a CakePHP 3/4 → 5 upgrade — rewrites composer.json for CakePHP 5.x and PHP 8.3+, bumps or replaces plugins, adds composer patches for unfixed upstream packages, and refreshes config/app.php from the 5.x skeleton. Use when updating dependencies for a CakePHP 5 upgrade or when composer can't resolve after bumping cakephp/cakephp.
user-invocable: true
---

# Phase 1 — Dependencies

Goal: `composer install` resolves and installs on CakePHP 5.x. The app may not boot yet — that's phases 2–3.

## Steps

1. **Record the starting point** in `docs/UPGRADE_PROGRESS.md`: current `composer.json` require blocks and `composer show cakephp/cakephp` version.

2. **Download the current 5.x skeleton next to the project** — it is the reference for versions, config and boilerplate files in this and the next phase:
   ```bash
   composer create-project cakephp/app:^5 ../app5 --no-install --no-scripts
   ```

3. **Rewrite `require` / `require-dev`** using `../app5/composer.json` and [references/versions.md](references/versions.md):
   - `php` → the version production runs (≥ 8.2 for Cake 5.4; we target `>=8.3`). Keep it consistent with docs/Docker.
   - `cakephp/cakephp` → `^5.x` **in `require`** (never `require-dev` — `composer install --no-dev` on production would drop the framework; easy to get wrong when bumping with `composer require --dev`).
   - Runtime plugins (e.g. Authentication, PDF/logging plugins, Migrations if migrations run on servers) → `require`. Dev-only tools (bake, debug_kit, phpunit, codesniffer, psysh) → `require-dev`.
   - `phpunit/phpunit` → `^11.5.3` for CakePHP 5.4 (5.0–5.3 also accept `^10.5`), `cakephp/cakephp-codesniffer` → `^5.3`.
   - Add `cakephp/authentication ^3` if the app used AuthComponent (see `cake5-auth`). Add `cakephp/authorization ^3` only if you will actually use it.
   - Remove packages the framework now covers or that are dead (e.g. `zendframework/zend-diactoros` → nothing; Cake 5 uses `laminas/laminas-diactoros` transitively).

4. **Plugins without a 5.x release** — decide per plugin and confirm with the user:
   1. Newer major exists under a new name/vendor → switch.
   2. Small fix needed → composer patch (below).
   3. Unmaintained and small → vendor it into `plugins/` and fix it.
   4. Otherwise → replace with a maintained package or app code.

5. **Composer patches** (only for a released package that needs a small fix):
   ```bash
   composer require --dev cweagans/composer-patches:^2
   ```
   ```json
   "extra": { "patches": {
     "vendor/some-plugin": {
       "Fix missing env() function import": "patches/some-plugin-env-fix.patch"
     }
   } },
   "config": { "allow-plugins": { "cweagans/composer-patches": true, "cakephp/plugin-installer": true } }
   ```
   Make the patch with `git diff` inside a copy of the vendor package (paths relative to the package root). **Commit the `patches/` directory** — check it isn't in `.gitignore`. Note each patch in `docs/UPGRADE_PROGRESS.md` with the upstream issue/version to drop it at.

6. **Install**: `composer update -W`. On conflicts, read the "Problem N" lines, bump/replace that package, repeat. Don't use `--ignore-platform-reqs` to "solve" PHP version conflicts.

7. **Config skeleton**: start from `../app5/config/app.php`, `paths.php` and `plugins.php` and merge the project's own values in (datasources, email, custom keys). Things that changed shape:
   - `Error`: `'exceptionRenderer' => \Cake\Error\Renderer\WebExceptionRenderer::class`, `errorLevel` → `E_ALL` during the upgrade (don't hide `E_DEPRECATED`).
   - `Log.*.scopes` → `null` (not `false`).
   - Cache `_cake_core_` → `_cake_translations_`.
   - `App.paths.templates` → `[ROOT . DS . 'templates' . DS]`; `App.paths.locales` → `[RESOURCES . 'locales' . DS]` (take `config/paths.php` from the skeleton — it defines `RESOURCES` — and create `resources/locales/`).
   - Plugins: 5.4 registers them in `config/plugins.php` (`'DebugKit' => ['onlyDebug' => true]`, `'Bake' => ['onlyCli' => true, 'optional' => true]`, `'Migrations' => ['onlyCli' => true]`, `'Authentication' => []`). `$this->addPlugin()` in `Application::bootstrap()` still works — use one mechanism, not both.
   - Datasources/salt/debug go in `config/app_local.php` (gitignored), as in the skeleton.
   - Keep the project's local override mechanism (`app_local.php`, or a custom file the bootstrap loads with `Configure::load(...)`).
   - `config/requirements.php` and `config/bootstrap_cli.php` no longer exist in the 5.4 skeleton (composer's `php` constraint does the check). Remove them together with the `require` lines in `webroot/index.php` / `bin/cake.php` (done in `cake5-structure`), or update the PHP minimum if you keep them (old copies often still check PHP 5.6).

## Gate
- `composer install` succeeds; `composer validate` has no errors.
- `php -r "require 'vendor/autoload.php'; echo \Cake\Core\Configure::version();"` prints 5.x.
- Commit: `upgrade(cake5): dependencies`.
