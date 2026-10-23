---
name: cake5-audit
description: Audit a CakePHP 3.x/4.x project for what must change to run on CakePHP 5.x — scans code for removed/deprecated APIs (Shells, .ctp, AuthComponent, Email, TableRegistry::get, ->order(), FrozenTime, fixture $fields, …) and checks composer dependencies. Use before or during a CakePHP 5 upgrade, or to find leftovers after one.
argument-hint: "[project-path] [--phase=cake5-code-apis]"
user-invocable: true
---

# CakePHP 5 Upgrade Audit

Produces a factual inventory of what a project needs to reach CakePHP 5.x. Run it first (the `cake5-upgrade` orchestrator does), and re-run after each phase — the counts are the progress meter.

## 1. Scan the code

```bash
php .claude/skills/cake5-audit/scripts/scan.php <project-path>                 # Markdown report
php .claude/skills/cake5-audit/scripts/scan.php <project-path> --format=json   # machine-readable
php .claude/skills/cake5-audit/scripts/scan.php <project-path> --phase=cake5-structure   # one phase only
```

- Scans `src/ templates/ config/ tests/ plugins/ webroot/index.php`; skips `vendor/ tmp/ logs/`.
- Skips comment lines (pass `--comments=1` to include commented-out code).
- Exit code `1` when any **blocker** pattern matches — use as the gate between phases.
- Patterns live in [references/patterns.json](references/patterns.json) (`id`, `regex`, `glob`, `severity`, `phase`, `fix`). Add a pattern whenever a project turns up a new leftover; keep `fix` actionable.

Severity meaning:
- **blocker** — removed in 5.x; fatal error or broken behaviour.
- **warning** — deprecated alias still works in 5.x but will break in 6 or behaves differently (e.g. `FrozenTime` → immutable `DateTime`).
- **info** — style/consistency (e.g. global function usage in config).

The regexes are line-based heuristics: confirm each hit before editing, and expect a few false positives (e.g. `->order(` on a non-Query object). Multi-line chains (`find('all', [` … `])->toList()`) are only partly caught — check the files the scan already flags. `*.php` patterns also cover `.ctp` templates, so the first audit counts template issues before the rename.

Some failures can't be found statically at all — the trial upgrade's runtime-only bugs were an `ArrayObject` identity in tests and a missing skeleton middleware class. That's why phases 3–5 have runtime gates.

## 2. Check dependencies

1. Read `composer.json` (`require`, `require-dev`, `extra.patches`) and `composer.lock` if present.
2. For every `cakephp/*` package and every CakePHP plugin, find the 5.x-compatible version:
   - use the table in [../cake5-deps/references/versions.md](../cake5-deps/references/versions.md);
   - otherwise `composer show -a <package>` / Packagist — look for a release requiring `cakephp/cakephp ^5`.
3. Mark each: **ok**, **bump to X**, **abandoned → replacement**, or **no 5.x release → patch/fork/replace**.
4. Check `php` constraint vs. the target (CakePHP 5 needs PHP ≥ 8.1; we target 8.3+).
5. Flag `cakephp/cakephp` listed under `require-dev` instead of `require` — production installs with `--no-dev` would miss the framework.

## 3. Check structure

Note which of these exist (each maps to a `cake5-structure` step):
- `src/Template/` or any `*.ctp`
- `src/Shell/` (and cron entries calling `bin/cake <shell>`)
- `src/Application.php` without a middleware queue / without `getAuthenticationService()`
- `config/app.php` using `'App' => ['paths' => ['templates' => [APP . 'Template' . DS]]]`
- `tests/Fixture/*` with `$fields` / `$import`
- Plugins loaded via `Plugin::load()` in bootstrap (5.x: `$this->addPlugin()` in `Application::bootstrap()`)

## 4. Report

Write (or update) `docs/UPGRADE_PROGRESS.md` in the target project:

```markdown
# CakePHP 5 upgrade progress
Target: cakephp/cakephp ^5.x, PHP 8.3 — branch upgrade/cake5

## Audit (<date>)
- Blocker hits: N (per phase: structure N, code-apis N, auth N, tests N)
- Dependencies: <table: package | current | target | action>
- Structure: <bullets from step 3>

## Phases
- [ ] cake5-deps
- [ ] cake5-structure
- [ ] cake5-code-apis
- [ ] cake5-auth
- [ ] cake5-tests
- [ ] final scan = 0 blockers, smoke test done
```

Keep the report factual — counts and file lists, not guesses.
