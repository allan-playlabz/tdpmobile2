# Target versions for CakePHP 5.x

**Source of truth: the current `cakephp/app` 5.x skeleton.** Download it next to the project and copy its constraints:
```bash
composer create-project cakephp/app:^5 ../app5 --no-install --no-scripts
```
The table below is what the skeleton and a trial upgrade resolved to in Oct 2026 (CakePHP **5.4.3**), plus the versions a production app on CakePHP 5.3 resolved to. Re-check before pinning (`composer show -a <package>`).

| Package | CakePHP 3.x (typical) | 5.4 skeleton / trial | on CakePHP 5.3 | Section | Notes |
|---|---|---|---|---|---|
| php | `>=5.6` | `>=8.2` (we target `>=8.3`) | `^8.5` | require | Keep consistent with production and Docker. |
| cakephp/cakephp | `3.x` | `5.4.*` | `^5.0` | **require** | Never require-dev. |
| cakephp/plugin-installer | `^1.0` | `^2.0` | `^1.3` | require | |
| cakephp/migrations | `^2.0` | `^5.0` | `^4.0` | require (if run on servers) | Old migration files keep working. |
| mobiledetect/mobiledetectlib | `2.*` | `^4.8.03` | `2.*` | require | Only if `config/bootstrap.php` uses the mobile detectors. |
| cakephp/authentication | — (AuthComponent) | `^3.3` | `^3.3` | require | Replaces AuthComponent. |
| cakephp/authorization | — | `^3.0` | `^3.4` | require | Only if you will use policies. |
| cakephp/bake | `^1.9` | `^3.6` | `^3.0` | require-dev | |
| cakephp/debug_kit | `^3.17` | `^5.2` | `^5.0` | require-dev | `'DebugKit' => ['onlyDebug' => true]` in `config/plugins.php`. |
| cakephp/cakephp-codesniffer | `^3.0` | `^5.3` | `^4.0` | require-dev | |
| phpunit/phpunit | `^5 \|\| ^6` | `^11.5.3 \|\| ^12 \|\| ^13` | `^10.5` | require-dev | CakePHP 5.4 needs PHPUnit ≥ 11.5; 5.0–5.3 work with 10.5. |
| josegonzalez/dotenv | `3.*` | `^4.0` | `^3.2` | require-dev | Only if `config/.env` is used. |
| friendsofcake/cakepdf | `^3.5` | `^5.0` | `^5.0` | require | |
| dereuromark/cakephp-databaselog | `~2.7` | `^4.0` | `^4.0` | require | Some 4.x releases call `env()` unqualified — may need a composer patch (see gotchas). Custom engines: `log($level, Stringable\|string $message, array $context = []): void`. |
| dompdf/dompdf | `^0.8` | `^2.0` / `^3.0` | `^2.0` | require | Check custom font paths. |
| zendframework/zend-diactoros | (transitive) | remove | — | — | Use `Laminas\Diactoros\*` if referenced directly. |
| cweagans/composer-patches | — | `^2.0` | `^2.0` | require-dev | Allow it in `config.allow-plugins`. |

`config.allow-plugins` from the skeleton: `cakephp/plugin-installer`, `dealerdirect/phpcodesniffer-composer-installer` (+ `cweagans/composer-patches` if used).

## Common replacements

| Old | Replacement |
|---|---|
| AuthComponent (core) | `cakephp/authentication` (+ `cakephp/authorization` or config-driven RBAC) |
| RequestHandlerComponent (core) | `$this->request->is('json')`, `Controller::addViewClasses()` / `viewClasses()` |
| `Cake\Filesystem\File/Folder` (core) | Native PHP, or app shims `App\Filesystem\File/Folder` |
| `Cake\Mailer\Email` (core) | `Cake\Mailer\Mailer` |
| SecurityComponent (core) | `FormProtectionComponent` + `HttpsEnforcerMiddleware` |
| `Cake\Auth\DefaultPasswordHasher` | `Authentication\PasswordHasher\DefaultPasswordHasher` |
| 3.x skeleton `ConsoleShell` (psysh REPL) | none in the 5.x skeleton — drop it unless the team uses `bin/cake console` |
| 3.x skeleton `AjaxView` | not in the 5.x skeleton — drop if unused, else type `protected string $layout` and `initialize(): void` |
