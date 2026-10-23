---
name: cake5-upgrade
description: Orchestrates converting a CakePHP 3.x (or 4.x) application to the latest CakePHP 5.x and PHP 8.3+ — audits, then runs the deps, structure, code-API (Rector), auth, and tests phases in order with a gate after each, tracking progress in docs/UPGRADE_PROGRESS.md so the upgrade can resume across sessions. Use when asked to upgrade, migrate, or convert a CakePHP project to version 5.
argument-hint: "[project-path] [resume]"
user-invocable: true
---

# CakePHP 3 → 5 Upgrade (orchestrator)

Built from a real production upgrade (3.8 → 5.3) and validated by a trial run that took a CakePHP 3.10 app to **5.4.3** end to end (0 blockers, `OK (11 tests)`, login/roles/CRUD/commands verified over HTTP). Each phase is its own skill — load it when you reach that phase. Read [references/gotchas.md](references/gotchas.md) before starting.

## Using these skills on another project
Copy the `cake5-*` folders into the target project's `.claude/skills/` (or `~/.claude/skills/` to have them in every project), or run the scripts from wherever the skills live and pass the project path — every script takes it as an argument:
```bash
php <skills>/cake5-audit/scripts/scan.php <project>
bash <skills>/cake5-structure/scripts/move_templates.sh <project>
```
Working layout used by the phases (siblings of the project):
```
../app5      cakephp/app 5.x skeleton  (composer create-project cakephp/app:^5 ../app5 --no-install --no-scripts)
../upgrade   cakephp/upgrade tool, default 5.x branch (git clone + composer install --no-dev)
```

## Ground rules
- Work on a branch: `git switch -c upgrade/cake5`. Commit at the end of every phase (`upgrade(cake5): <phase>`), so a phase can be reverted alone.
- Never mix behaviour changes into the upgrade. If business logic looks wrong, note it in `docs/UPGRADE_PROGRESS.md` under "Found while upgrading" and leave it.
- Mechanical first (Rector, renames, skeleton files), judgment second (auth, plugin replacements, shims).
- Don't delete code you can't explain; convert it or ask.
- Confirm with the user before: replacing a plugin, changing the auth model, or touching production config/credentials.
- Use a local throwaway database (SQLite in `app_local.php` works) for runtime checks — never production.

## 0. Start or resume
1. If `docs/UPGRADE_PROGRESS.md` exists, read it and continue from the first unchecked phase.
2. Otherwise: check `git status` is clean, create the branch, then run **`cake5-audit`** — it writes `docs/UPGRADE_PROGRESS.md` with the scan counts and dependency table.
3. Show the user the audit summary (blocker counts per phase, plugins needing replacement) and agree on the plan before editing.

## Phases (in order)

| # | Skill | What it does | Gate |
|---|---|---|---|
| 1 | `cake5-deps` | Download the 5.x skeleton; composer.json → CakePHP 5.x + plugins; `app.php`/`paths.php`/`plugins.php` from the skeleton; patches | `composer install` succeeds; `Configure::version()` = 5.x |
| 2 | `cake5-structure` | `src/Template`→`templates/` (keep `Error/`), `.ctp`→`.php`, untouched skeleton files → 5.x versions (incl. `src/Middleware`), Shells→Commands, bootstrap/routes | `bin/cake --help` lists commands under their old names; scan `--phase=cake5-structure` = 0 |
| 3 | `cake5-code-apis` | Rector sets `cakephp40`…`cakephp5x` + `chronos3` (5.x tool), then the manual API table | `php -l` clean; scan `--phase=cake5-code-apis` = 0; commands run |
| 4 | `cake5-auth` | AuthComponent → Authentication plugin; port `isAuthorized()`/RBAC; **then the web runtime sweep** | Every row of the auth inventory passes; every page renders without PHP warnings |
| 5 | `cake5-tests` | Skeleton phpunit config + bootstrap, schema loading, fixtures, test cases, login in tests | `vendor/bin/phpunit` runs to completion (failures triaged, not hidden) |

After each phase:
1. Run its gate. If it fails, fix within the phase — don't move on with a red gate.
2. Re-run the scan (`--max-hits=5`) and record the new blocker count in `docs/UPGRADE_PROGRESS.md`.
3. Tick the phase, commit.

Order matters: Rector and the API fixes need the new layout and packages; the web pages can't run until auth is converted (AuthComponent no longer exists), so the web sweep is part of phase 4.

Expected effort split (trial on a small 3.10 app): Rector fixed ~60% of blocker hits; the rest — removed classes, request/response, immutable dates, pagination, templates, auth, tests — was manual.

## 6. Finish
1. Full scan: **0 blockers**. Warnings may remain (deprecated aliases such as `FrozenTime`, `Cake\ORM\Query`) — list them as follow-ups.
2. Smoke test with the user, using the project's real flows: login per role, list/view/add/edit/delete, a 404 page (checks `templates/Error/`), a PDF/email if the app has them, each CLI command used by cron, and any inbound webhook routes (see gotchas: CSRF).
3. Run `composer cs-check` (CakePHP codesniffer ^5) and fix what's mechanical.
4. Update the target project's `CLAUDE.md` with a "CakePHP 5 Migration Notes" section: version jump, Commands not Shells, shims added, patches applied, plugins replaced.
5. Deployment notes for the user: install on **Linux** catches case problems Windows hides (`templates/Error`); run migrations/SQL; update cron (`bin/cake <command>` names); clear `tmp/cache`; restart PHP-FPM/OPcache or rebuild Docker images.

## When to stop and ask
- A plugin has no 5.x release and no obvious replacement.
- The app uses `AuthComponent` with custom authorize/authenticate objects.
- Database type handling is custom (e.g. binary UUID columns, custom `Type` classes).
- Tests didn't run before the upgrade (agree on what "green" means).
