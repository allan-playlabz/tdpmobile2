---
name: cake5-code-apis
description: Phase 3 of a CakePHP 3 → 5 upgrade — runs the cakephp/upgrade Rector rules, then fixes what Rector leaves (ORM query API, named arguments, immutable DateTime, request/response PSR-7 API, Mailer, removed classes, typed properties and signatures, pagination, helpers) using a before→after table from a real upgrade. Use when fixing CakePHP 5 API errors/deprecations in controllers, tables, entities, commands or templates.
argument-hint: "[path or pattern-id]"
user-invocable: true
---

# Phase 3 — Code APIs

Goal: `src/`, `templates/`, `config/` compile and the scan shows 0 code-API blockers. Run after `cake5-structure`.

## 1. Make deprecations visible
Set `'Error' => ['errorLevel' => E_ALL]` in `config/app.php` for the whole phase. Hiding `E_DEPRECATED` is how deprecated `->order()`, `FrozenTime` and `Cake\ORM\Query` uses survive an upgrade unnoticed.

## 2. Rector (mechanical changes)
The [cakephp/upgrade](https://github.com/cakephp/upgrade) tool is a standalone app — clone it **next to** the project, never `composer require` it into the project:
```bash
git clone https://github.com/cakephp/upgrade ../upgrade
cd ../upgrade && composer install --no-dev
```
Point it at sub-directories (`src`, `tests`, `config`, `templates`, `plugins/*/src`) — **never the project root** (it runs out of memory). Commit before each run so `git diff` shows exactly what it changed.

**Use only the tool's default `5.x` branch** — it ships every rule set from `cakephp30` to `cakephp54` plus `chronos3`, `phpunit80`, `migrations45/50`, and runs on current PHP. (Verified in the trial: the old `4.x` branch won't install on PHP ≥ 8.4 — `nette/utils 3.2` — and its Rector 0.16 errors out on PHP 8.5.)

Run the sets **in order**, one at a time, on each directory:
```bash
cd ../upgrade
for set in cakephp40 cakephp41 cakephp42 cakephp43 cakephp44 cakephp45 \
           cakephp50 cakephp51 cakephp52 cakephp53 cakephp54 chronos3; do   # stop at the target minor
  for d in src tests config templates; do
    bin/cake upgrade rector --rules $set <project>/$d
  done
  # review `git diff`, revert behaviour-changing hunks, php -l, commit
done
bin/cake upgrade rector --rules phpunit80 <project>/tests   # old PHPUnit APIs
```
Add `--dry-run` to preview a set. After each set: `git diff --name-only | grep '\.php$' | xargs -n1 php -l`, then re-run the scan.

**What Rector does and doesn't do** (trial on a 3.10 app: 89 → 34 blockers):
- ✅ signatures/return types (`initialize(): void`, `validationDefault(): Validator`, `EventInterface`), typed properties (`array $_accessible`, `$paginate`, `$fixtures`, `string $layout`), `TableRegistry::get` → `getTableLocator()->get`, `find('list', [...])` → named args, `FrozenTime` → `DateTime`, `Query` → `SelectQuery` hints, `order()`/`group()` → `orderBy()`/`groupBy()`, `_cake_core_` → `_cake_translations_`.
- ❌ left for you: `View::initialize()` signatures, `hydrate(false)`, `get($id, ['contain' => ...])`, `find('all', [...options])`, `->toList()` on queries, `loadModel()`, `$this->request->data`, response `body()/type()/statusCode()`, `here()`, `Email`, `Cake\Filesystem`, `newEntity()` without data, `notEmpty()`/`allowEmpty()`, `sortWhitelist` / paginate `conditions`, singular date helpers and **immutable-date reassignment**, `instanceof Time`, RequestHandler, AuthComponent, template helpers (`Form->input`, `counter([...])`, `'inline'`), and unused old `use` imports.
- Rector won't touch `.ctp` files — run `cake5-structure` first.

## 3. Manual fixes from the scan
```bash
php .claude/skills/cake5-audit/scripts/scan.php . --phase=cake5-code-apis --max-hits=50
```
Work pattern by pattern, using [references/api-changes.md](references/api-changes.md) for each. Order that minimises churn:
1. **Removed classes** (`removed-fqcn`, `cake-email`, `cake-filesystem`, `request-handler`) — these fatal only when reached, so they're invisible in a quick click-through. Check `catch` blocks and `instanceof` too.
2. **Signatures & typed properties** (`initialize-no-void`, `untyped-accessible`, `validation-default-signature`, `before-filter-event-type`) — a single wrong signature fatals the whole class.
3. **ORM/query** (`query-order`, `query-group`, `hydrate`, `find-options-array`, `get-options-array`, `named-arg-misuse`, `find-all-conditions-array`, `query-tolist-without-all`, `load-model`, `table-registry-get`).
4. **Request/response** (`request-data-property`, `request-env-mutators`, `request-legacy-methods`).
5. **Dates** (`frozen-time`, `chronos-singular-methods`, `immutable-result-discarded`, `time-of-day-instanceof`) — check each `immutable-result-discarded` hit: a bare `$d->addDays(1);` is a silent no-op bug.
6. **Pagination & helpers** (`paginate-sort-whitelist`, `paginator-counter-array`, `form-create-empty-string`, `deprecated-helpers`).
7. **Entities & validation** (`new-entity-no-data`, `validator-removed-rules`, `untyped-framework-property`, `connection-connect`).
Finally remove `use` imports left pointing at old classes (`Cake\I18n\Time`, `FrozenTime`, `Cake\ORM\Query`, `Cake\Event\Event`, `TableRegistry`).

Rules:
- Edit in small batches (one pattern, or one controller) and `php -l` as you go.
- Inside **Table** classes use `TableRegistry::getTableLocator()->get()` or associations — `fetchTable()` only exists on controllers, commands, cells, components (LocatorAwareTrait).
- Don't add `: void` to `beforeFilter()` overrides in AppController (child controllers break) — match the parent signature.
- Keep `epilog('FOR UPDATE')` and transaction wrappers exactly as they are.
- Where the fix needs a decision (plugin behaviour, data semantics), note it in `docs/UPGRADE_PROGRESS.md` and ask.

## 4. CLI runtime check
Web pages can't run yet if the app used AuthComponent (it's gone until `cake5-auth`), but commands don't go through controllers:
- Run every command with `--help`, and the safe ones for real (dry-run flags, a test database).
- Tail `logs/error.log` while doing so.
The full **web** runtime sweep happens after `cake5-auth` (see the orchestrator).

## Gate
- Scan `--phase=cake5-code-apis` → 0 blockers; warnings triaged (fixed or listed as follow-ups).
- `php -l` clean on all of `src/` and `templates/`.
- Commands run without PHP errors.
- Commit: `upgrade(cake5): code apis` (or one commit per Rector set / pattern).
