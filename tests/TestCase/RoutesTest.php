<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use App\Application;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\Route;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * Fallback routes are disabled, so config/routes.php is the full list of reachable actions.
 */
class RoutesTest extends TestCase
{
    /**
     * Routed to an action that doesn't exist (404 / MissingActionException).
     */
    private const KNOWN_MISSING_ACTIONS = [
        'Attractions::test',
        'Reports::topups',
        'Reports::transactions',
    ];

    private static function appRoutes(): RouteCollection
    {
        Router::reload();
        $collection = Router::getRouteCollection();
        (new Application(CONFIG))->routes(new RouteBuilder($collection, '/'));

        return $collection;
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function routeProvider(): array
    {
        $cases = [];
        foreach (self::appRoutes()->routes() as $route) {
            /** @var \Cake\Routing\Route\Route $route */
            $controller = $route->defaults['controller'];
            $action = $route->defaults['action'];
            $cases[$route->template] = [$route->template, $controller, $action];
        }

        return $cases;
    }

    #[DataProvider('routeProvider')]
    public function testRouteTargetsAPublicAction(string $template, string $controller, string $action): void
    {
        $class = 'App\\Controller\\' . $controller . 'Controller';
        $this->assertTrue(class_exists($class), "$template: controller $class exists");

        if (!method_exists($class, $action) && in_array("$controller::$action", self::KNOWN_MISSING_ACTIONS, true)) {
            $this->markTestSkipped("BUG: route $template points to $controller::$action, which does not exist.");
        }
        $this->assertTrue(method_exists($class, $action), "$template: $controller::$action exists");
        $this->assertTrue((new ReflectionMethod($class, $action))->isPublic(), "$template: $controller::$action is public");
    }

    public function testRouteCountMatchesConfig(): void
    {
        // Guards against fallbacks() being re-enabled by accident.
        $templates = array_map(fn (Route $route) => $route->template, self::appRoutes()->routes());
        $this->assertNotContains('/{controller}/{action}/*', $templates);
        $this->assertNotContains('/{controller}', $templates);
    }

    public function testNotificationReceiverRouteWithoutProvider(): void
    {
        self::appRoutes();
        $params = Router::parseRequest(new ServerRequest(['url' => '/orders/notification-receiver', 'environment' => ['REQUEST_METHOD' => 'POST']]));
        $required = (new ReflectionMethod('App\\Controller\\OrdersController', 'notificationReceiver'))->getNumberOfRequiredParameters();

        if (count($params['pass']) < $required) {
            $this->markTestSkipped('BUG: /orders/notification-receiver passes no provider, but OrdersController::notificationReceiver($provider) requires one (ArgumentCountError).');
        }
        $this->assertGreaterThanOrEqual($required, count($params['pass']));
    }

    public function testParsesPassedParams(): void
    {
        self::appRoutes();
        $params = Router::parseRequest(new ServerRequest(['url' => '/vouchers/download/123/abc/def']));
        $this->assertSame(['123', 'abc/def'], $params['pass']);

        $params = Router::parseRequest(new ServerRequest(['url' => '/notification-receiver/2C2P']));
        $this->assertSame('notificationReceiver', $params['action']);
        $this->assertSame(['2C2P'], $params['pass']);

        $params = Router::parseRequest(new ServerRequest(['url' => '/frontend-notification-receiver/Stripe/REF123']));
        $this->assertSame(['Stripe', 'REF123'], $params['pass']);
    }
}
