---
name: cake5-auth
description: Phase 4 of a CakePHP 3 → 5 upgrade — replaces the removed AuthComponent with the cakephp/authentication plugin (service provider, middleware, Session/Form authenticators, login/logout, public actions, password hasher) and ports isAuthorized()/Auth->allow logic, optionally to a config-driven RBAC map. Use when migrating CakePHP login/authentication or access control to CakePHP 5.
user-invocable: true
---

# Phase 4 — Authentication (and authorization)

Goal: login, logout, "must be logged in" redirects, public actions, and role checks behave exactly as before. Run after `cake5-code-apis` (the app must compile).

## 0. Inventory the old behaviour first
From `AppController::initialize()` (`loadComponent('Auth', [...])`), every `$this->Auth->allow()/deny()`, `isAuthorized()`, `Auth->user()`, `Auth->setUser()`, `Auth->identify()`, `Auth->redirectUrl()`, `Auth->logout()`, list:
- user model + username/password fields, `loginAction`, `loginRedirect`, `logoutRedirect`, `unauthorizedRedirect`, `authError`;
- password hasher (Default? Legacy/Weak? fallback);
- public actions per controller;
- authorization rule (`'authorize' => 'Controller'` → `isAuthorized($user)` logic per controller).
Write this table to `docs/UPGRADE_PROGRESS.md` — it's the acceptance test for this phase.

## 1. Service provider + middleware (`src/Application.php`)
```php
class Application extends BaseApplication implements AuthenticationServiceProviderInterface
// middleware(): ... ->add(new BodyParserMiddleware())[->add(new CsrfProtectionMiddleware(...))]->add(new AuthenticationMiddleware($this));
// plugin: 'Authentication' => [] in config/plugins.php (5.4 skeleton) — or $this->addPlugin('Authentication') in bootstrap()

public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
{
    $service = new AuthenticationService([
        'unauthenticatedRedirect' => '/users/login',   // old loginAction
        'queryParam' => 'redirect',
    ]);
    $fields = ['username' => 'email', 'password' => 'password'];
    $service->loadAuthenticator('Authentication.Session');
    $service->loadAuthenticator('Authentication.Form', [
        'fields' => $fields,
        'loginUrl' => '/users/login',
        'identifier' => [
            'Authentication.Password' => [
                'fields' => $fields,
                'resolver' => ['className' => 'Authentication.Orm', 'userModel' => 'Users'],
                // Old hashes? add: 'passwordHasher' => ['className' => 'Authentication.Fallback',
                //   'hashers' => ['Authentication.Default', ['className' => 'Authentication.Legacy', 'hashType' => 'sha1']]],
            ],
        ],
    ]);

    return $service;
}
```
This is the current Authentication 3.x shape (identifier nested in the authenticator). `$service->loadIdentifier(...)` (2.x style) still exists but is deprecated since 3.3 — don't use it in new code.

## 2. AppController
```php
public function initialize(): void
{
    parent::initialize();
    $this->loadComponent('Flash');
    $this->loadComponent('Authentication.Authentication');   // was loadComponent('Auth', [...])
}

/** Drop-in for old $this->Auth->user() / user('key') call sites. */
protected function getAuthUser(?string $key = null): mixed
{
    $identity = $this->request->getAttribute('identity');
    if (!$identity) {
        return null;
    }
    // Entity after a Form login; ArrayObject when the session holds a plain array
    // (e.g. $this->session(['Auth' => [...]]) in tests) — calling ->toArray() on it would fatal.
    $user = $identity->getOriginalData();
    if ($user instanceof \Cake\Datasource\EntityInterface) {
        $user = $user->toArray();
    } elseif ($user instanceof \ArrayObject) {
        $user = $user->getArrayCopy();
    }

    return $key === null ? $user : ($user[$key] ?? null);
}
```
Then replace call sites mechanically: `$this->Auth->user()` → `$this->getAuthUser()`, `$this->Auth->user('id')` → `$this->getAuthUser('id')`. In views, `$this->set('authUser', $this->getAuthUser())` in `beforeFilter()`, or use `$this->request->getAttribute('identity')`.

(A helper that calls `->toArray()` / `->get()` directly works for real logins but fatals as soon as a test logs in with a session array.)

## 3. Public actions
`$this->Auth->allow(['login', 'receive'])` → in that controller:
```php
public function beforeFilter(EventInterface $event)
{
    parent::beforeFilter($event);
    $this->Authentication->addUnauthenticatedActions(['login', 'receive']);
}
```
Webhook/API endpoints also need a route scope outside the CSRF scope (see `cake5-structure`).

## 4. Login / logout actions
```php
public function login()
{
    $this->request->allowMethod(['get', 'post']);
    $result = $this->Authentication->getResult();
    if ($result && $result->isValid()) {
        return $this->redirect($this->Authentication->getLoginRedirect() ?? '/');
    }
    if ($this->request->is('post')) {
        $this->Flash->error(__('Invalid email or password'));
    }
}

public function logout()
{
    $this->Authentication->logout();
    return $this->redirect(['action' => 'login']);
}
```
`$this->Auth->setUser($user)` (e.g. after signup/OTP) → `$this->Authentication->setIdentity($user)`.
Password hashing in entities: `Authentication\PasswordHasher\DefaultPasswordHasher` (was `Cake\Auth\DefaultPasswordHasher`).

## 5. Authorization (replacing `isAuthorized()`)
Pick one with the user:
- **Keep `isAuthorized()` as-is** — smallest change when the rules are simple. Call it from `AppController::beforeFilter()` (verified in the trial upgrade):
  ```php
  public function beforeFilter(EventInterface $event)
  {
      parent::beforeFilter($event);
      $user = $this->getAuthUser();
      if ($user !== null && !$this->isAuthorized($user)) {
          $this->Flash->error('You are not allowed to do that.');                               // old authError
          $event->setResult($this->redirect(['controller' => 'Articles', 'action' => 'index'])); // old unauthorizedRedirect
          $event->stopPropagation();

          return;
      }
  }
  ```
  Child controllers that call `$this->Authentication->addUnauthenticatedActions()` must call `parent::beforeFilter($event)` first, as before.
- **cakephp/authorization** policies — right for per-record rules (owner checks). More work.
- **Config-driven RBAC** — right for role → controller/action rules across many controllers:
  - `config/permissions.php`: `'Rbac' => ['Articles' => ['roles' => ['admin', 'editor'], 'actions' => ['delete' => ['admin'], 'feed' => '*']]]`; unlisted controllers fall back to a default role list; `'*'` = public. `Configure::load('permissions', 'default')` in bootstrap.
  - `AppController::beforeFilter()` calls a `checkAccess()` method that reads the identity's `role`, resolves allowed roles (action override > controller default > global default) and, if denied, sets `$event->setResult($this->redirect(...))` + `stopPropagation()`.
  - A `RbacHelper::canAccess($role, $controller, $action)` view helper uses the same resolver to show/hide navigation links.

## Gate (with the user, or scripted)
Go through the inventory table from step 0: logged-out redirect to login with `?redirect=`, valid/invalid login, logout, each public action reachable logged out, each role allowed/denied on a sample of controllers, sidebar links match permissions.

Scripting it with `bin/cake server` + curl works well (the trial did this). Two CSRF details trip scripts up:
- GET forms (`Form->create(null, ['type' => 'get'])`) carry no `_csrfToken` — take the token from a POST form (the login page) or from the `csrfToken` cookie.
- When sending the cookie value as the `X-CSRF-Token` header, **URL-decode it first** (cookie jars store it encoded) — otherwise every POST is a 403 "CSRF token ... did not match".

Then do the **web runtime sweep** postponed from `cake5-code-apis`: with `debug => true`, load every list/view/add page as each role and fail on "Warning", "Deprecated", "Fatal error" or "Missing Template/Controller" in the HTML; tail `logs/error.log`. Commit: `upgrade(cake5): authentication`.
