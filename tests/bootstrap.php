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
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\ConnectionHelper;
use Cake\TestSuite\Fixture\SchemaLoader;

/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

require dirname(__DIR__) . '/config/bootstrap.php';

if (empty($_SERVER['HTTP_HOST']) && !Configure::read('App.fullBaseUrl')) {
    Configure::write('App.fullBaseUrl', 'http://localhost');
}

// Fixate now to avoid one-second-leap-issues
Chronos::setTestNow(Chronos::now());

// Fixate sessionid early on, as php7.2+
// does not allow the sessionid to be set after stdout
// has been written to.
session_id('cli');

// Connection aliasing needs to happen before migrations are run.
// Otherwise, table objects inside migrations would use the default datasource
ConnectionHelper::addTestAliases();

// Use migrations to build test database schema.
//
// Will rebuild the database if the migration state differs
// from the migration history in files.
//
// If you are not using CakePHP's migrations you can
// hook into your migration tool of choice here or
// load schema from a SQL dump file with
// use Cake\TestSuite\Fixture\SchemaLoader;
// (new SchemaLoader())->loadSqlFiles('./tests/schema.sql', 'test');

// The app has no migrations: the schema comes from schema-only dumps of the
// application and log databases (regenerate when the schema changes).
(new SchemaLoader())->loadSqlFiles(dirname(__DIR__) . "/tests/schema/app.sql", "test");
(new SchemaLoader())->loadSqlFiles(dirname(__DIR__) . "/tests/schema/log.sql", "test_log_ds");

// LoggableBehavior reads request headers via getallheaders(), which only exists under web SAPIs.
if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        return [];
    }
}

// LoggableBehavior falls back to $_SERVER['REMOTE_ADDR'], which CLI runs don't set.
$_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';

// Never reach the real backend API, queue server, S3, payment gateways or mail server from tests.
// Requests to these fake hosts fail fast unless a test mocks them (Cake\Http\TestSuite\HttpClientTrait).
Configure::write('Api.url', 'http://api.test');
Configure::write('Api.secret', 'test-api-secret');
Configure::write('Api.timeout', 5);
Configure::write('Mailer.api_url', 'http://queue.test');
Configure::write('Mailer.secret', 'test-queue-secret');
Configure::write('Mailer.timeout', 5);
foreach (['AWS', 'AWSPublic'] as $aws) {
    Configure::write($aws . '.credentials', ['key' => 'test', 'secret' => 'test']);
    Configure::write($aws . '.region', 'ap-southeast-1');
    Configure::write($aws . '.version', 'latest');
    Configure::write($aws . '.endpoint', 'http://s3.test');
    Configure::write($aws . '.Bucket', 'test-bucket');
}
Configure::write('AWSPublic.public_host', 'http://public.test/');
Configure::write('Default.admin_email', 'admin@example.com');
Configure::write('Default.from_email', ['noreply@example.com' => 'TDP']);
Configure::write('Default.contact_us_email', 'contact@example.com');
Configure::write('Default.company_email', 'company@example.com');
Configure::write('Default.exception_emails', ['dev@example.com']);
Configure::write('Default.max_order_quantity', 20);
Configure::write('Default.previous_orders_months_to_view', 6);
Configure::write('Vouchers.display_in_browser.agent_ids', []);
Configure::write('Vouchers.allowLegacyMd5Links', false);
Configure::write('Login.max_minutes', 3);
Configure::write('Login.max_count_per_max_minutes', 5);
Configure::write('Security.cfConnectingIp', 'cloudflare');

TransportFactory::drop('default');
TransportFactory::setConfig('default', ['className' => 'Debug']);

foreach (Log::configured() as $logger) {
    Log::drop($logger);
}
Log::setConfig('debug', ['className' => 'Array', 'levels' => ['notice', 'info', 'debug']]);
Log::setConfig('error', ['className' => 'Array', 'levels' => ['warning', 'error', 'critical', 'alert', 'emergency']]);
