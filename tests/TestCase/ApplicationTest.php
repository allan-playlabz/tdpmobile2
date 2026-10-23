<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Test\TestCase;

use App\Application;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * ApplicationTest class
 */
class ApplicationTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test bootstrap in production.
     *
     * @return void
     */
    public function testBootstrap(): void
    {
        Configure::write('debug', false);
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $plugins = $app->getPlugins();

        $this->assertTrue($plugins->has('Bake'), 'plugins has Bake?');
        $this->assertFalse($plugins->has('DebugKit'), 'plugins has DebugKit?');
        $this->assertTrue($plugins->has('Migrations'), 'plugins has Migrations?');
        $this->assertTrue($plugins->has('Authentication'), 'plugins has Authentication?');
        $this->assertTrue($plugins->has('CakePdf'), 'plugins has CakePdf?');
        $this->assertTrue($plugins->has('DatabaseLog'), 'plugins has DatabaseLog?');
    }

    /**
     * Test bootstrap add DebugKit plugin in debug mode.
     *
     * @return void
     */
    public function testBootstrapInDebug(): void
    {
        Configure::write('debug', true);
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $plugins = $app->getPlugins();

        $this->assertTrue($plugins->has('DebugKit'), 'plugins has DebugKit?');
    }

    /**
     * The middleware queue (CSRF is scoped route middleware, see config/routes.php).
     *
     * @return void
     */
    public function testMiddleware(): void
    {
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $middleware = $app->middleware(new MiddlewareQueue());
        $queue = iterator_to_array($middleware);

        $this->assertInstanceOf(ErrorHandlerMiddleware::class, $queue[0]);
        $this->assertInstanceOf(AssetMiddleware::class, $queue[1]);
        $this->assertInstanceOf(RoutingMiddleware::class, $queue[2]);
        $this->assertInstanceOf(BodyParserMiddleware::class, $queue[3]);
        $this->assertInstanceOf(AuthenticationMiddleware::class, $queue[4]);
    }
}
