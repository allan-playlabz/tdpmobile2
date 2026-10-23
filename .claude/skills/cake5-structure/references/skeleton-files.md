# CakePHP 5 skeleton files — working versions

The **current 5.x skeleton** (`composer create-project cakephp/app:^5 ../app5 --no-install --no-scripts`) is the primary reference. Below: the differences in the 5.4 skeleton, then working 5.3-style versions of the files with typical customisations (plugins, authentication, scoped CSRF, webhooks).

## 5.4 skeleton differences (verified in the trial upgrade)
- `config/plugins.php` registers plugins: `return ['DebugKit' => ['onlyDebug' => true], 'Bake' => ['onlyCli' => true, 'optional' => true], 'Migrations' => ['onlyCli' => true], 'Authentication' => []];` — `Application::bootstrap()` then only calls `parent::bootstrap()`.
- Middleware: `new ErrorHandlerMiddleware(Configure::read('Error'), $this)` → **`new HostHeaderMiddleware()`** (`src/Middleware/HostHeaderMiddleware.php`, copy it) → Asset → Routing → BodyParser → **`new CsrfProtectionMiddleware(['httponly' => true])`** (global).
- `bootstrap()` adds `FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));` — remove for legacy apps that use tables without classes.
- `config/bootstrap.php` loads globals with one line: `require CAKE . 'functions.php';`.
- No `config/requirements.php`, no `config/bootstrap_cli.php`; `webroot/index.php` and `bin/cake.php` don't require them.

## src/Application.php (addPlugin style + scoped CSRF + Authentication)
```php
namespace App;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Core\Exception\MissingPluginException;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    public function bootstrap(): void
    {
        $this->addPlugin('CakePdf', ['bootstrap' => true]);   // example plugin; was Plugin::load() in bootstrap.php
        $this->addPlugin('Authentication');

        parent::bootstrap();   // loads config/bootstrap.php

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        }
        if (Configure::read('debug')) {
            $this->addPlugin('DebugKit');
        }
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))
            ->add(new AssetMiddleware(['cacheTime' => Configure::read('Asset.cacheTime')]))
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())          // JSON/XML bodies -> getData()
            ->add(new AuthenticationMiddleware($this)); // see cake5-auth
        // CSRF is NOT here: it is scoped route middleware in config/routes.php
    }

    public function services(ContainerInterface $container): void
    {
    }

    public function routes(RouteBuilder $routes): void
    {
        $routesClosure = require $this->configDir . 'routes.php';
        if (is_callable($routesClosure)) {
            $routesClosure($routes);
        }
    }

    protected function bootstrapCli(): void
    {
        try {
            $this->addPlugin('Bake');
        } catch (MissingPluginException $e) {
            // bake is a dev dependency
        }
        $this->addPlugin('Migrations');
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        // see cake5-auth/SKILL.md
    }
}
```

## config/bootstrap.php (the parts that change)
```php
use Cake\Error\ErrorTrap;          // was Cake\Error\ErrorHandler
use Cake\Error\ExceptionTrap;      // was Cake\Console\ConsoleErrorHandler
use Cake\Mailer\Mailer;            // was Cake\Mailer\Email
use Cake\Mailer\TransportFactory;

use function Cake\Core\env;

// Global functions (h(), __(), env(), debug(), collection(), ...) are no longer loaded automatically.
// 5.x skeleton: one line loads them all (src/functions.php exists in 5.3 and 5.4).
require CAKE . 'functions.php';
// (Requiring only Core/ and I18n/functions_global.php individually also works, but misses the
//  Collection/Error/Routing helpers.)

Configure::config('default', new PhpConfig());
Configure::load('app', 'default', false);
if (file_exists(CONFIG . 'app_local.php')) {        // or the project's own local override file
    Configure::load('app_local', 'default');
}

(new ErrorTrap(Configure::read('Error')))->register();
(new ExceptionTrap(Configure::read('Error')))->register();

Cache::setConfig(Configure::consume('Cache'));
ConnectionManager::setConfig(Configure::consume('Datasources'));
TransportFactory::setConfig(Configure::consume('EmailTransport'));
Mailer::setConfig(Configure::consume('Email'));      // was Email::setConfig
Log::setConfig(Configure::consume('Log'));
Security::setSalt(Configure::consume('Security.salt'));

// Remove: Type::build('time')->useImmutable(); etc. (immutable is the only mode now)
// Remove: Plugin::load(...) — use Application::bootstrap()
```

## config/app.php (keys that change shape)
```php
'App' => ['paths' => [
    'plugins' => [ROOT . DS . 'plugins' . DS],
    'templates' => [ROOT . DS . 'templates' . DS],
    'locales' => [RESOURCES . 'locales' . DS],
]],
'Error' => [
    'errorLevel' => E_ALL,   // keep deprecations visible during the upgrade
    'exceptionRenderer' => \Cake\Error\Renderer\WebExceptionRenderer::class,
    'skipLog' => [],
    'log' => true,
    'trace' => true,
],
'Cache' => ['_cake_translations_' => [/* was _cake_core_ */]],
'Log' => ['debug' => [/* ... */ 'scopes' => null /* was false */]],
```

## config/routes.php
```php
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    // Inbound webhooks: own scope, BEFORE '/', so CSRF is not applied
    $routes->scope('/webhooks', function (RouteBuilder $builder): void {
        $builder->connect('/payment', ['controller' => 'PaymentWebhooks', 'action' => 'receive']);
    });

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->registerMiddleware('csrf', new CsrfProtectionMiddleware(['httponly' => true]));
        $builder->applyMiddleware('csrf');

        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
        $builder->connect('/orders/view/{id}', ['controller' => 'Orders', 'action' => 'view'], ['pass' => ['id']]); // was :id
        $builder->fallbacks();
    });
};
```

## webroot/index.php
```php
require dirname(__DIR__) . '/config/requirements.php';
// (cli-server static-file passthrough block)
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;

$server = new Server(new Application(dirname(__DIR__) . '/config'));
$server->emit($server->run());
```

## bin/cake.php
```php
require dirname(__DIR__) . '/config/requirements.php';
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Console\CommandRunner;

$runner = new CommandRunner(new Application(dirname(__DIR__) . '/config'), 'cake');
exit($runner->run($argv));
```
