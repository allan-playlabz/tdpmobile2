---
name: cake5-tests
description: Phase 5 of a CakePHP 3 → 5 upgrade — moves the test suite to PHPUnit 11.5 (10.5 for CakePHP ≤ 5.3) and CakePHP 5 fixtures (skeleton phpunit.xml.dist with the fixture extension, schema from migrations or an SQL dump in tests/bootstrap.php, fixtures reduced to records, IntegrationTestCase → IntegrationTestTrait, logging in from tests). Use when CakePHP tests fail after upgrading to 5.x, or when converting old $fields/$import fixtures.
user-invocable: true
---

# Phase 5 — Tests

Goal: `vendor/bin/phpunit` runs with CakePHP 5 fixtures; remaining failures are real and triaged. Projects that skip this phase end up with an old `phpunit.xml.dist`, `$fields` fixtures and nearly every DB test erroring — don't. A trial upgrade of a 3.10 app finished this phase green (`OK (11 tests, 26 assertions)`).

Agree with the user first what "done" means if the suite was already red before the upgrade (e.g. "suite runs; no fixture/bootstrap errors; failing tests listed").

## 1. phpunit.xml.dist and tests/bootstrap.php — take the skeleton's
Copy both from the 5.x skeleton (`../app5`, see `cake5-deps`), then re-add the project's own suites/settings. The skeleton's config:
```xml
<phpunit ... bootstrap="tests/bootstrap.php" cacheDirectory=".phpunit.cache"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.1/phpunit.xsd">
    <testsuites><testsuite name="app"><directory>tests/TestCase/</directory></testsuite></testsuites>
    <extensions>
        <bootstrap class="Cake\TestSuite\Fixture\Extension\PHPUnitExtension"/>
    </extensions>
    <source><include><directory suffix=".php">src/</directory></include></source>
</phpunit>
```
Remove the old `<listeners>` (`FixtureInjector` / `FixtureManager`) and `<filter><whitelist>` blocks. PHPUnit: `^11.5.3` for CakePHP 5.4, `^10.5` is enough for 5.0–5.3.

The skeleton `tests/bootstrap.php` already does `ConnectionHelper::addTestAliases()` (maps `default` → `test`, `log_ds` → `test_log_ds`, …) and `Chronos::setTestNow(Chronos::now())`. Its last line builds the schema — choose one:
```php
// a) from migrations (skeleton default)
(new \Migrations\TestSuite\Migrator())->run();
// b) from an SQL dump (apps whose schema lives in .sql files, not migrations)
(new \Cake\TestSuite\Fixture\SchemaLoader())->loadSqlFiles('./config/schema/app.sql', 'test');
```
Every datasource the app uses needs a `test_<name>` connection (in `app_local.php` / `app.php`).

## 2. Fixtures
```php
// before (3.x)
class ArticlesFixture extends TestFixture
{
    public $fields = ['id' => ['type' => 'integer', ...], '_constraints' => [...]];
    public $records = [...];
}
// after (5.x)
class ArticlesFixture extends TestFixture
{
    public string $table = 'articles';   // only if it differs from the inflected name
    public function init(): void
    {
        $this->records = [ /* same rows */ ];
        parent::init();
    }
}
```
- Delete `$fields` / `$import` (schema comes from step 1). Keep the records (`public array $records = [...]` also works).
- The scan pattern `fixture-fields` counts what's left — convert in bulk with a careful script for large suites.

## 3. Test cases
- `IntegrationTestCase` was removed → `class XTest extends \Cake\TestSuite\TestCase { use \Cake\TestSuite\IntegrationTestTrait; }` (the 3.x skeleton's `ApplicationTest` / `PagesControllerTest` use it — replace those with the 5.x skeleton's versions).
- `protected array $fixtures = ['app.Articles'];` (not `public $fixtures`).
- `protected function setUp(): void` / `tearDown(): void`; data providers `public static function ...(): array`.
- Tables: `$this->getTableLocator()->get('Articles')` and a typed property (`protected ArticlesTable $Articles;`) — undeclared dynamic properties are deprecated in PHP 8.2+.
- Queries aren't collections: `->find(...)->all()->extract('title')->toList()`.
- `assertRegExp` → `assertMatchesRegularExpression`, `expectExceptionMessageRegExp` → `expectExceptionMessageMatches` (Rector set `phpunit80` does most of this).
- Middleware-queue assertions: `$middleware->get($i)` → `iterator_to_array($middleware)[$i]`.

## 4. Auth-protected controllers in tests
Pages that sat behind AuthComponent still require login — the 5.x skeleton tests assume public pages and will get 302s. Log in instead of weakening the app:
```php
protected function setUp(): void
{
    parent::setUp();
    $this->session(['Auth' => ['id' => 1, 'email' => 'admin@example.com', 'role' => 'admin']]);
}

public function testRequiresLogin(): void   // guards the behaviour the upgrade must keep
{
    $this->session(['Auth' => null]);
    $this->get('/pages/home');
    $this->assertRedirectContains('/users/login');
}
```
A session array becomes an `ArrayObject` identity — the app's `getAuthUser()` helper must handle it (see `cake5-auth`), or every authenticated test 500s. For POSTs use `$this->enableCsrfToken()` (and `enableSecurityToken()` with FormProtection).

To see the real exception behind a test's 500, add `$this->disableErrorHandlerMiddleware();` temporarily.

## 5. Beyond unit tests
A route-scanner E2E (Playwright, or curl against `bin/cake server`) visiting every page as each role and failing on PHP "Warning"/"Deprecated"/"Error" output is the fastest safety net — in practice it catches more upgrade regressions than the unit tests.

## Gate
- `vendor/bin/phpunit` runs to completion (no bootstrap/fixture errors); results recorded in `docs/UPGRADE_PROGRESS.md` (pass / fail / incomplete counts, failing tests listed with cause).
- Scan `--phase=cake5-tests` → 0 blockers.
- Commit: `upgrade(cake5): tests`.
