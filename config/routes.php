<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Routing\RouteBuilder;
use function Cake\Core\env;
use Cake\Routing\Route\DashedRoute;

/**
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `$routes->setRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
 * `{action}` markers.
 *
 * Cache: Routes are cached to improve performance, check the RoutingMiddleware
 * constructor in your `src/Application.php` file to change this behavior.
 *
 */
return static function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    // Payment gateway callbacks: own scopes, connected before '/', so CSRF is not applied.
    $routes->scope('/orders/sps', function (RouteBuilder $routes): void {
        $routes->connect('/', ['controller' => 'Orders', 'action' => 'sps']);
    });

    $routes->scope('/frontend-notification-receiver', function (RouteBuilder $routes): void {
        $routes->connect('/{provider}/{order_ref_id}', ['controller' => 'Orders', 'action' => 'frontendNotificationReceiver'],['pass'=>['provider', 'order_ref_id']]);
    });

    $routes->scope('/notification-receiver', function (RouteBuilder $routes): void {
        $routes->connect('/{provider}', ['controller' => 'Orders', 'action' => 'notificationReceiver'],['pass'=>['provider']]);
    });

    $routes->scope('/', function (RouteBuilder $routes): void {
   // Register scoped middleware for in scopes.
   $csrf = new CsrfProtectionMiddleware([
      'httponly' => true,
      'secure' => filter_var(env('SESSION_COOKIE_SECURE', true), FILTER_VALIDATE_BOOLEAN),
      'samesite' => 'Lax',
   ]);

   $csrf->skipCheckCallback(function ($request) {
      // Skip token check for notification receiver.
      if ($request->getParam('controller') === 'Orders' && in_array($request->getParam('action'), ['notificationReceiver', 'frontendNotificationReceiver'])) {
         return true;
      }
   });
   $routes->registerMiddleware('csrf', $csrf);

   /**
    * Apply a middleware to the current route scope.
     * Requires middleware to be registered via `Application::routes()` with `registerMiddleware()`
     */

   $routes->applyMiddleware('csrf');

    /**
     * Here, we are connecting '/' (base path) to a controller called 'Pages',
     * its action called 'display', and we pass a param to select the view file
     * to use (in this case, src/Template/Pages/home.ctp)...
     */


    $routes->connect('/', ['controller' => 'Attractions', 'action' => 'index']);

   //  attractions
   $routes->connect('/attractions', ['controller' => 'Attractions', 'action' => 'index']);
   $routes->connect('/attractions/test', ['controller' => 'Attractions', 'action' => 'test']);
    $routes->connect('/attractions/view/{id}', ['controller' => 'Attractions', 'action' => 'view'], ['pass' => ['id']]);
    $routes->connect('/attractions/two-way/{id}', ['controller' => 'Attractions', 'action' => 'viewTwoWay'], ['pass' => ['id']]);
    $routes->connect('/attractions/get-tickets/{id}', ['controller' => 'Attractions', 'action' => 'getTickets'], ['pass' => ['id']]);
    $routes->connect('/attractions/get-two-way-tickets/{id}', ['controller' => 'Attractions', 'action' => 'getTwoWayTickets'], ['pass' => ['id']]);
    $routes->connect('/attractions/get-fixeddate-time/{id}', ['controller' => 'Attractions', 'action' => 'getFixeddateTime'], ['pass' => ['id']]);
    $routes->connect('/attractions/get-availability/{id}', ['controller' => 'Attractions', 'action' => 'getAvailability'], ['pass' => ['id']]);
    $routes->connect('/attractions/toggle-favorite', ['controller' => 'Attractions', 'action' => 'toggleFavorite']);

   //  agents
    $routes->connect('/agents', ['controller' => 'Agents', 'action' => 'index']);

    //  agent topups
    $routes->connect('/agent-topups', ['controller' => 'AgentTopups', 'action' => 'index']);
    $routes->connect('/agent-topups/add', ['controller' => 'AgentTopups', 'action' => 'add']);
    $routes->connect('/agent-topups/view/{id}', ['controller' => 'AgentTopups', 'action' => 'view'], ['pass' => ['id']]);

   //  agent users
   $routes->connect('/agent-users', ['controller' => 'AgentUsers', 'action' => 'index']);
   $routes->connect('/agent-users/view', ['controller' => 'AgentUsers', 'action' => 'view']);
   $routes->connect('/agent-users/view/{id}', ['controller' => 'AgentUsers', 'action' => 'view'], ['pass' => ['id']]);
   $routes->connect('/agent-users/add', ['controller' => 'AgentUsers', 'action' => 'add']);
   $routes->connect('/agent-users/edit', ['controller' => 'AgentUsers', 'action' => 'edit']);
   $routes->connect('/agent-users/edit/{id}', ['controller' => 'AgentUsers', 'action' => 'edit'], ['pass' => ['id']]);
   $routes->connect('/agent-users/read/{id}', ['controller' => 'AgentUsers', 'action' => 'read'], ['pass' => ['id']]);
   $routes->connect('/agent-users/agent-reset-password/{id}', ['controller' => 'AgentUsers', 'action' => 'agentResetPassword'], ['pass' => ['id']]);
   $routes->connect('/agent-users/suspend/{id}', ['controller' => 'AgentUsers', 'action' => 'suspend'], ['pass' => ['id']]);
    $routes->connect('/agent-users/contact', ['controller' => 'AgentUsers', 'action' => 'contact']);
    $routes->connect('/agent-users/login', ['controller' => 'AgentUsers', 'action' => 'login']);
    $routes->connect('/agent-users/logout', ['controller' => 'AgentUsers', 'action' => 'logout']);
    $routes->connect('/agent-users/otp', ['controller' => 'AgentUsers', 'action' => 'otp']);
    $routes->connect('/agent-users/resend-otp', ['controller' => 'AgentUsers', 'action' => 'resendOtp']);
    $routes->connect('/agent-users/change-password', ['controller' => 'AgentUsers', 'action' => 'changePassword']);
    $routes->connect('/agent-users/set-password', ['controller' => 'AgentUsers', 'action' => 'setPassword']);
    $routes->connect('/agent-users/forgot-password', ['controller' => 'AgentUsers', 'action' => 'forgotPassword']);
    $routes->connect('/agent-users/reset-password/{email}/{check}', ['controller' => 'AgentUsers', 'action' => 'resetPassword'], ['pass' => ['email', 'check']]);

   //  credit logs
   $routes->connect('/credit-logs/index', ['controller' => 'CreditLogs', 'action' => 'index']);

    $routes->connect('/orders/cart', ['controller' => 'Orders', 'action' => 'cart']);
    $routes->connect('/orders/checkout', ['controller' => 'Orders', 'action' => 'checkout']);
    $routes->connect('/orders/confirmation', ['controller' => 'Orders', 'action' => 'confirmation']);
    $routes->connect('/orders/get-status', ['controller' => 'Orders', 'action' => 'getStatus']);
    $routes->connect('/orders/thank-you', ['controller' => 'Orders', 'action' => 'thankYou']);
    $routes->connect('/orders/notification-receiver', ['controller' => 'Orders', 'action' => 'notificationReceiver']);
    $routes->connect('/orders', ['controller' => 'Orders', 'action' => 'index']);
    $routes->connect('/orders/view/{id}', ['controller' => 'Orders', 'action' => 'view'], ['pass' => ['id']]);
    $routes->connect('/orders/download-invoice/{order_ref_id}', ['controller' => 'Orders', 'action' => 'downloadInvoice'], ['pass' => ['order_ref_id']]);
    $routes->connect('/orders/my-tickets', ['controller' => 'Orders', 'action' => 'myTickets']);
    $routes->connect('/credit-logs', ['controller' => 'CreditLogs', 'action' => 'index']);
    $routes->connect('/orders/cancel-voucher/{voucher_code}', ['controller' => 'Orders', 'action' => 'cancelVoucher'], ['pass' => ['voucher_code']]);
    $routes->connect('/orders/remove-ticket/{ticket_id}/{date}', ['controller' => 'Orders', 'action' => 'removeTicket'], ['pass' => ['ticket_id','date']]);


    $routes->connect('/vouchers/download/{id}/{check}', ['controller' => 'Vouchers', 'action' => 'download'],['check'=>'.+','pass'=>['id','check']]);
    $routes->connect('/vouchers/sdownload/{id}/{check}', ['controller' => 'Vouchers', 'action' => 'sdownload'],['check'=>'.+','pass'=>['id','check']]);
    $routes->connect('/vouchers/apiview/{year}/{monthday}/{code}', ['controller' => 'Vouchers', 'action' => 'apiview'],['pass'=>['year','monthday','code']]);
    $routes->connect('/vouchers/apiview/{year}/{monthday}/{code}/{check}', ['controller' => 'Vouchers', 'action' => 'apiview'],['pass'=>['year','monthday','code','check']]);
    $routes->connect('/vouchers/download-all/{id}', ['controller' => 'Vouchers', 'action' => 'downloadAll'],['pass'=>['id']]);
    $routes->connect('/agent-topups/topup-images/{key}', ['controller' => 'AgentTopups', 'action' => 'topupImages'],['key'=>'.+','pass'=>['key']]);
    $routes->connect('/signup', ['controller' => 'AgentsSignups', 'action' => 'add']);
    $routes->connect('/signup/thank-you', ['controller' => 'AgentsSignups', 'action' => 'thankyou']);

    $routes->connect('/reports', ['controller' => 'Reports', 'action' => 'index']);
    $routes->connect('/reports/topups', ['controller' => 'Reports', 'action' => 'topups']);
    $routes->connect('/reports/transactions', ['controller' => 'Reports', 'action' => 'transactions']);
    $routes->connect('/reports/reports', ['controller' => 'Reports', 'action' => 'reports']);
    $routes->connect('/reports/download/{filename}', ['controller' => 'Reports', 'action' => 'download'])->setPass(['filename']);

    $routes->connect('/voucher-merging/get-status', ['controller' => 'VoucherMerging', 'action' => 'getStatus']);
    /**
     * Connect catchall routes for all controllers.
     *
     * Using the argument `DashedRoute`, the `fallbacks` method is a shortcut for
     *
     * ```
     * $routes->connect('/{controller}', ['action' => 'index'], ['routeClass' => 'DashedRoute']);
     * $routes->connect('/{controller}/{action}/*', [], ['routeClass' => 'DashedRoute']);
     * ```
     *
     * Any route class can be used with this method, such as:
     * - DashedRoute
     * - InflectedRoute
     * - Route
     * - Or your own route class
     *
     * You can remove these routes once you've connected the
     * routes you want in your application.
     */
   //  $routes->fallbacks(DashedRoute::class);
    });
};
