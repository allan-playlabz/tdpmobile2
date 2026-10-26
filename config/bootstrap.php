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
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/*
 * This file is loaded by your src/Application.php bootstrap method.
 * Feel free to extend/extract parts of the bootstrap process into your own files
 * to suit your needs/preferences.
 */

/*
 * Configure paths required to find CakePHP + general filepath constants
 */
require __DIR__ . DIRECTORY_SEPARATOR . 'paths.php';

/*
 * Bootstrap CakePHP
 * Currently all this does is initialize the router (without loading your routes)
 */
require CORE_PATH . 'config' . DS . 'bootstrap.php';

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorTrap;
use Cake\Error\ExceptionTrap;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Detection\MobileDetect;
use function Cake\Core\env;

/*
 * Load global functions for collections, translations, debugging etc.
 */
require CAKE . 'functions.php';

/*
 * See https://github.com/josegonzalez/php-dotenv for API details.
 *
 * Uncomment block of code below if you want to use `.env` file during development.
 * You should copy `config/.env.example` to `config/.env` and set/modify the
 * variables as required.
 *
 * The purpose of the .env file is to emulate the presence of the environment
 * variables like they would be present in production.
 *
 * If you use .env files, be careful to not commit them to source control to avoid
 * security risks. See https://github.com/josegonzalez/php-dotenv#general-security-information
 * for more information for recommended practices.
*/
if (!env('APP_NAME') && file_exists(CONFIG . '.env')) {
    $dotenv = new \josegonzalez\Dotenv\Loader([CONFIG . '.env']);
    $dotenv->parse()
        ->putenv()
        ->toEnv()
        ->toServer();
}

/*
 * Initializes default Config store and loads the main configuration file (app.php)
 *
 * CakePHP contains 2 configuration files after project creation:
 * - `config/app.php` for the default application configuration.
 * - `config/app_config.php` for environment specific configuration.
 */
try {
    Configure::config('default', new PhpConfig());
    Configure::load('app', 'default', false);
} catch (Exception $e) {
    exit($e->getMessage() . "\n");
}

/*
 * Load an environment local configuration file to provide overrides to your configuration.
 * Notice: For security reasons app_config.php **should not** be included in your git repo.
 */
if (file_exists(CONFIG . 'app_config.php')) {
    Configure::load('app_config', 'default');
}

/*
 * When debug = true the metadata cache should only last for a short time.
 */
if (Configure::read('debug')) {
    Configure::write('Cache._cake_model_.duration', '+2 minutes');
    Configure::write('Cache._cake_translations_.duration', '+2 minutes');
}

/*
 * Set the default server timezone. Using UTC makes time calculations / conversions easier.
 * Check https://php.net/manual/en/timezones.php for list of valid timezone strings.
 */
date_default_timezone_set(Configure::read('App.defaultTimezone'));

/*
 * Configure the mbstring extension to use the correct encoding.
 */
mb_internal_encoding(Configure::read('App.encoding'));

/*
 * Set the default locale. This controls how dates, number and currency is
 * formatted and sets the default language to use for translations.
 */
ini_set('intl.default_locale', Configure::read('App.defaultLocale'));

/*
 * Register application error and exception handlers.
 */
(new ErrorTrap(Configure::read('Error')))->register();
(new ExceptionTrap(Configure::read('Error')))->register();

/*
 * CLI/Command specific configuration.
 */
if (PHP_SAPI === 'cli') {
    // Set the fullBaseUrl to allow URLs to be generated in commands.
    // This is useful when sending email from commands.
    // Configure::write('App.fullBaseUrl', php_uname('n'));

    // Set logs to different files so they don't have permission conflicts.
    if (Configure::check('Log.debug')) {
        Configure::write('Log.debug.file', 'cli-debug');
    }
    if (Configure::check('Log.error')) {
        Configure::write('Log.error.file', 'cli-error');
    }
}

/*
 * Set the full base URL for the application.
 *
 * SECURITY: In production, App.fullBaseUrl MUST be explicitly configured to prevent
 * Host Header Injection attacks. The HostHeaderMiddleware enforces this requirement
 * and validates incoming Host headers against the configured value.
 *
 * Set APP_FULL_BASE_URL in your environment variables or configure App.fullBaseUrl
 * in config/app.php or config/app_config.php
 *
 * Example: APP_FULL_BASE_URL=https://example.com
 */
$fullBaseUrl = Configure::read('App.fullBaseUrl');
if (!$fullBaseUrl) {
    $httpHost = env('HTTP_HOST');

    /*
     * Development mode fallback: Use HTTP_HOST for convenience.
     * WARNING: This is ONLY safe in development. In production, the
     * HostHeaderMiddleware will reject requests when fullBaseUrl is not configured.
     */
    if ($httpHost) {
        $s = null;
        if (env('HTTPS')) {
            $s = 's';
        }
        $fullBaseUrl = 'http' . $s . '://' . $httpHost;
    }
    unset($httpHost, $s);
}
if ($fullBaseUrl) {
    Router::fullBaseUrl($fullBaseUrl);
}
unset($fullBaseUrl);

/*
 * Apply the loaded configuration settings to their respective systems.
 * This will also remove the loaded config data from memory.
 */
Cache::setConfig(Configure::consume('Cache'));
ConnectionManager::setConfig(Configure::consume('Datasources'));
TransportFactory::setConfig(Configure::consume('EmailTransport'));
Mailer::setConfig(Configure::consume('Email'));
Log::setConfig(Configure::consume('Log'));
$securitySalt = Configure::consume('Security.salt');
if (empty($securitySalt)) {
    throw new \RuntimeException('SECURITY_SALT is not set. Configure it in the environment (or config/.env locally).');
}
Security::setSalt($securitySalt);
unset($securitySalt);

/*
 * Setup detectors for mobile and tablet.
 * If you don't use these checks you can safely remove this code
 * and the mobiledetect package from composer.json.
 */
ServerRequest::addDetector('mobile', function ($request) {
    $detector = new MobileDetect();

    return $detector->isMobile();
});
ServerRequest::addDetector('tablet', function ($request) {
    $detector = new MobileDetect();

    return $detector->isTablet();
});

/*
 * You can enable default locale format parsing by adding calls
 * to `useLocaleParser()`. This enables the automatic conversion of
 * locale specific date formats when processing request data. For details see
 * @link https://book.cakephp.org/5/en/core-libraries/internationalization-and-localization.html#parsing-localized-datetime-data
 */
// \Cake\Database\TypeFactory::build('time')->useLocaleParser();
// \Cake\Database\TypeFactory::build('date')->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetime')->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestamp')->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetimefractional')->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestampfractional')->useLocaleParser();
// \Cake\Database\TypeFactory::build('datetimetimezone')->useLocaleParser();
// \Cake\Database\TypeFactory::build('timestamptimezone')->useLocaleParser();

/*
 * Custom Inflector rules, can be set to correctly pluralize or singularize
 * table, model, controller names or whatever other string is passed to the
 * inflection functions.
 */
// \Cake\Utility\Inflector::rules('plural', ['/^(inflect)or$/i' => '\1ables']);
// \Cake\Utility\Inflector::rules('irregular', ['red' => 'redlings']);
// \Cake\Utility\Inflector::rules('uninflected', ['dontinflectme']);

// set a custom date and time format
// see https://book.cakephp.org/5/en/core-libraries/time.html#setting-the-default-locale-and-format-string
// and https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
// \Cake\I18n\Date::setToStringFormat('dd.MM.yyyy');
// \Cake\I18n\Time::setToStringFormat('dd.MM.yyyy HH:mm');

Configure::write('Default.max_order_quantity',50);
Configure::write('Default.voucher_templates',['angry_birds','gardens_by_the_bay','gardens_by_the_bay_new','gardens_by_the_bay_floral','kidzania_singapore','batch_id_gen_format','resorts_world_sentosa','fixed_purchase_date']);
Configure::write('Default.land_transfer_details',[
    'pickup_date'=>['label'=>'Pickup Date','type'=>'date'],
    'pickup_location'=>['label'=>'Pickup Location','type'=>'text'],
    'pickup_time'=>['label'=>'Pickup Time','type'=>'time'],
    'flight_number'=>['label'=>'Flight Number (Only for Airport Transfer)','type'=>'text'],
    'contact_method'=>['label'=>'Contact Method (e.g. WhatsApp/Wechat/Live)','type'=>'text'],
    'destination_location'=>['label'=>'Destination Location','type'=>'text'],
    'number_of_passengers'=>['label'=>'Number of Passengers','type'=>'number'],
    'number_of_child_seats'=>['label'=>'Number of Child Seats','type'=>'number']
]);

Configure::write('Default.transfer_subtypes',['1'=>'1-way Airport Transfer','2'=>'2-way Airport Transfer','3'=>'Hourly Transfer','4'=>'Point To Point']);

Configure::write('CakePdf', array(
       'engine' => [
            'className' => 'CakePdf.WkHtmlToPdf',
            'options' => [/*
                'isRemoteEnabled' => true,
                'enable_font_subsetting' => true*/
                'margin-left'=> 5,
                'margin-right'=> 5,
                'margin-top'=> 5,
                'margin-bottom'=> 5,

            ],
        ],
       'pageSize'=>'A4',
        'orientation' => 'portrait',
        'download' => true
    ));

Configure::write('Options.countries', ["Singapore","Afghanistan","Albania","Algeria","Andorra","Angola","Anguilla","Antigua &amp; Barbuda","Argentina","Armenia","Aruba","Australia","Austria","Azerbaijan","Bahamas"
        ,"Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin","Bermuda","Bhutan","Bolivia","Bosnia &amp; Herzegovina","Botswana","Brazil","British Virgin Islands"
        ,"Brunei","Bulgaria","Burkina Faso","Burundi","Cambodia","Cameroon","Canada","Cape Verde","Cayman Islands","Chad","Chile","China","Colombia","Congo","Cook Islands","Costa Rica"
        ,"Cote D Ivoire","Croatia","Cruise Ship","Cuba","Cyprus","Czech Republic","Denmark","Djibouti","Dominica","Dominican Republic","Ecuador","Egypt","El Salvador","Equatorial Guinea"
        ,"Estonia","Ethiopia","Falkland Islands","Faroe Islands","Fiji","Finland","France","French Polynesia","French West Indies","Gabon","Gambia","Georgia","Germany","Ghana"
        ,"Gibraltar","Greece","Greenland","Grenada","Guam","Guatemala","Guernsey","Guinea","Guinea Bissau","Guyana","Haiti","Honduras","Hong Kong","Hungary","Iceland","India"
        ,"Indonesia","Iran","Iraq","Ireland","Isle of Man","Israel","Italy","Jamaica","Japan","Jersey","Jordan","Kazakhstan","Kenya","Kuwait","Kyrgyz Republic","Laos","Latvia"
        ,"Lebanon","Lesotho","Liberia","Libya","Liechtenstein","Lithuania","Luxembourg","Macau","Macedonia","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Mauritania"
        ,"Mauritius","Mexico","Moldova","Monaco","Mongolia","Montenegro","Montserrat","Morocco","Mozambique","Namibia","Nepal","Netherlands","Netherlands Antilles","New Caledonia"
        ,"New Zealand","Nicaragua","Niger","Nigeria","Norway","Oman","Pakistan","Palestine","Panama","Papua New Guinea","Paraguay","Peru","Philippines","Poland","Portugal"
        ,"Puerto Rico","Qatar","Reunion","Romania","Russia","Rwanda","Saint Pierre &amp; Miquelon","Samoa","San Marino","Satellite","Saudi Arabia","Senegal","Serbia","Seychelles"
        ,"Sierra Leone","Slovakia","Slovenia","South Africa","South Korea","Spain","Sri Lanka","St Kitts &amp; Nevis","St Lucia","St Vincent","St. Lucia","Sudan"
        ,"Suriname","Swaziland","Sweden","Switzerland","Syria","Taiwan","Tajikistan","Tanzania","Thailand","Timor L'Este","Togo","Tonga","Trinidad &amp; Tobago","Tunisia"
        ,"Turkey","Turkmenistan","Turks &amp; Caicos","Uganda","Ukraine","United Arab Emirates","United Kingdom","United States","United States Minor Outlying Islands","Uruguay"
        ,"Uzbekistan","Venezuela","Vietnam","Virgin Islands (US)","Yemen","Zambia","Zimbabwe"]
);

Configure::write('Payment', [
    '2C2P' => [
        'payment_token_endpoint' => env('P2C2P_PAYMENT_TOKEN_ENDPOINT', '/payment/4.1/paymentToken'),
        'payment_inquiry_endpoint' => env('P2C2P_PAYMENT_INQUIRY_ENDPOINT', '/payment/4.1/paymentInquiry'),
        'payment_methods' => [
            'alipay' => 'Alipay',
            'wechat_pay' => 'Wechat Pay',
            'credit_card' => 'Credit Card',
            'paynow' => 'PayNow',
        ],
        'payment_channels' => [
            'alipay' => ['ALIPAY', 'ALQR'],
            'wechat_pay' => ['WECHAT', 'WCQR'],
            'credit_card' => ['CC'],
            'paynow' => ['PNQR'],
        ],
        'codes' => [
            '0000' => 'Successful',
            '0001' => 'Transaction is pending',
            '0003' => 'Transaction is cancelled',
            '0999' => 'System error',
            '2001' => 'Transaction in progress',
            '2002' => 'Transaction not found',
            '2003' => 'Failed To Inquiry',
            '4001' => 'Refer to card issuer',
            '4002' => 'Refer to issuer\'s special conditions',
            '4003' => 'Invalid merchant ID',
            '4004' => 'Pick up card',
            '4005' => 'Do not honor',
            '4006' => 'Error',
            '4007' => 'Pick up card, special condition',
            '4008' => 'Honor with ID',
            '4009' => 'Request in progress',
            '4010' => 'Partial amount approved',
            '4011' => 'Approved VIP',
            '4012' => 'Invalid Transaction',
            '4013' => 'Invalid Amount',
            '4014' => 'Invalid Card Number',
            '4015' => 'No such issuer',
            '4016' => 'Approved, Update Track 3',
            '4017' => 'Customer Cancellation',
            '4018' => 'Customer Dispute',
            '4019' => 'Re-enter Transaction',
            '4020' => 'Invalid Response',
            '4021' => 'No Action Taken',
            '4022' => 'Suspected Malfunction',
            '4023' => 'Unacceptable Transaction Fee',
            '4024' => 'File Update Not Supported by Receiver',
            '4025' => 'Unable to Locate Record on File',
            '4026' => 'Duplicate File Update Record',
            '4027' => 'File Update Field Edit Error',
            '4028' => 'File Update File Locked Out',
            '4029' => 'File Update not Successful',
            '4030' => 'Format Error',
            '4031' => 'Bank Not Supported by Switch',
            '4032' => 'Completed Partially',
            '4033' => 'Expired Card - Pick Up',
            '4034' => 'Suspected Fraud - Pick Up',
            '4035' => 'Restricted Card - Pick Up',
            '4036' => 'Allowable PIN Tries Exceeded',
            '4037' => 'No Credit Account',
            '4038' => 'Allowable PIN Tries Exceeded',
            '4039' => 'No Credit Account',
            '4040' => 'Requested Function not Supported',
            '4041' => 'Lost Card - Pick Up',
            '4042' => 'No Universal Amount',
            '4043' => 'Stolen Card - Pick Up',
            '4044' => 'No Investment Account',
            '4045' => 'Settlement Success',
            '4046' => 'Settlement Fail',
            '4047' => 'Cancel Success',
            '4048' => 'Cancel Fail',
            '4049' => 'No Transaction Reference Number',
            '4050' => 'Host Down',
            '4051' => 'Insufficient Funds',
            '4052' => 'No Cheque Account',
            '4053' => 'No Savings Account',
            '4054' => 'Expired Card',
            '4055' => 'Incorrect PIN',
            '4056' => 'No Card Record',
            '4057' => 'Transaction Not Permitted to Cardholder',
            '4058' => 'Transaction Not Permitted to Terminal',
            '4059' => 'Suspected Fraud',
            '4060' => 'Card Acceptor Contact Acquirer',
            '4061' => 'Exceeds Withdrawal Amount Limits',
            '4062' => 'Restricted Card',
            '4063' => 'Security Violation',
            '4064' => 'Original Amount Incorrect',
            '4065' => 'Exceeds Withdrawal Frequency Limit',
            '4066' => 'Card Acceptor Call Acquirer Security',
            '4067' => 'Hard Capture - Pick Up Card at ATM',
            '4068' => 'Response Received Too Late',
            '4069' => 'Reserved',
            '4070' => 'Settle amount cannot exceed authorized amount',
            '4071' => 'Inquiry Record Not Exist',
            '4072' => 'Promotion not allowed in current payment method',
            '4073' => 'Promotion Limit Reached',
            '4074' => 'Reserved',
            '4075' => 'Allowable PIN Tries Exceeded',
            '4076' => 'Invalid Credit Card Format',
            '4077' => 'Invalid Expiry Date Format',
            '4078' => 'Invalid Three Digits Format',
            '4079' => 'Reserved',
            '4080' => 'User Cancellation by Closing Internet Browser',
            '4081' => 'Unable to Authenticate Card Holder',
            '4082' => 'Reserved',
            '4083' => 'Reserved',
            '4084' => 'Reserved',
            '4085' => 'Reserved',
            '4086' => 'ATM Malfunction',
            '4087' => 'No Envelope Inserted',
            '4088' => 'Unable to Dispense',
            '4089' => 'Administration Error',
            '4090' => 'Cut-off in Progress',
            '4091' => 'Issuer or Switch is Inoperative',
            '4092' => 'Financial Insititution Not Found',
            '4093' => 'Trans Cannot Be Completed',
            '4094' => 'Duplicate Transmission',
            '4095' => 'Reconcile Error',
            '4096' => 'System Malfunction',
            '4097' => 'Reconciliation Totals Reset',
            '4098' => 'MAC Error',
            '4099' => 'Unable to Complete Payment',
            '4110' => 'Settled',
            '4120' => 'Refunded',
            '4121' => 'Refund Rejected',
            '4122' => 'Refund Failed',
            '4130' => 'Chargeback',
            '4131' => 'Chargeback Rejected',
            '4132' => 'Chargeback Failed',
            '4140' => 'Transaction Does Not Exist',
            '4200' => 'Tokenization Successful',
            '4201' => 'Tokenization Failed',
            '4202' => 'Invalid Customer Token',
            '5002' => 'Timeout',
            '5003' => 'Invalid Message',
            '5004' => 'Invalid Profile (Merchant) ID',
            '5005' => 'Duplicated Invoice',
            '5006' => 'Invalid Amount',
            '5007' => 'Insufficient Balance',
            '5008' => 'Invalid Currency Code',
            '5009' => 'Payment Expired',
            '5010' => 'Payment Canceled By Payer',
            '5011' => 'Invalid Payee ID',
            '5012' => 'Invalid Customer ID',
            '5013' => 'Account Does Not Exist',
            '5014' => 'Authentication Failed',
            '5015' => 'Customer paid more than transaction amount',
            '5016' => 'Customer paid less than transaction amount',
            '5017' => 'Paid Expired',
            '5018' => 'Reserved',
            '5019' => 'No-Action From WebPay',
            '5998' => 'Internal Error',
            '6012' => 'Invalid Transaction',
            '6101' => 'Invalid request message',
            '6102' => 'Required Payload',
            '6103' => 'Invalid JWT data',
            '6104' => 'Required merchantId',
            '6105' => 'Required paymentChannel',
            '6106' => 'Required authCode',
            '6107' => 'Invalid merchantId',
            '6108' => 'Invalid paymentChannel',
            '6109' => 'paymentChannel is not configured',
            '6110' => 'Unable to retrieve usertoken',
            '7012' => 'Invalid Transaction',
            '9004' => 'The value is not valid',
            '9005' => 'Some mandatory fields are missing',
            '9006' => 'This field exceeded its authorized length',
            '9007' => 'Invalid merchant',
            '9008' => 'Invalid payment expiry',
            '9009' => 'Amount is invalid',
            '9010' => 'Invalid Currency Code',
            '9012' => 'paymentItem name is required',
            '9013' => 'paymentItem quantity is required',
            '9014' => 'paymentItem amount is required',
            '9015' => 'Existing Invoice Number',
            '9016' => 'Failed to retrieve PaymentInstruction',
            '9017' => 'PaymentInstruction not available',
            '9035' => 'Payment failed',
            '9037' => 'Merchant configuration is missing',
            '9038' => 'Failed To Generate Token',
            '9039' => 'The merchant frontend URL is missing',
            '9040' => 'The token is invalid',
            '9041' => 'Payment token already used',
            '9042' => 'Hash value mismatch',
            '9057' => 'Payment options are invalid',
            '9058' => 'Payment channel invalid',
            '9059' => 'Payment channel unauthorized',
            '9060' => 'Payment channel unconfigured',
            '9078' => 'Promotion code does not exist',
            '9080' => 'Tokenization not allowed',
            '9088' => 'SubMerchant is required',
            '9089' => 'Duplicated SubMerchant',
            '9090' => 'SubMerchant Not Found',
            '9091' => 'Invalid Sub Merchant ID',
            '9092' => 'Invalid Sub Merchant invoiceNo',
            '9093' => 'Existing Sub Merchant Invoice Number',
            '9094' => 'Invalid Sub Merchant Amount',
            '9095' => 'Sub Merchant Amount mismatch',
            '9100' => 'FxRateId and OriginalAmount are required',
            '9101' => 'Not allow to make a payment with Fx',
            '9102' => 'FxRate not available',
            '9103' => 'Invalid amount. (for the transaction which using the FxRateId)',
            '9104' => 'Invalid Country Code (Airline Info)',
            '9105' => 'Invalid Currency Code (Airline Info)',
            '9106' => 'Invalid Loyalty Redeem Amount',
            '9107' => 'Invalid Loyalty Provider',
            '9108' => 'Duplicated Loyalty Reward Id',
            '9109' => 'Required Loyalty\'s External Merchant Id',
            '9110' => 'Failed to Inquiry Loyalty Rewards',
            '9900' => 'Unable to decrypt the payload',
            '9901' => 'Invalid invoicePrefix',
            '9902' => 'allowAccumulate is required',
            '9903' => 'maxAccumulateAmount is required',
            '9904' => 'recurringInterval or ChargeOnDate is required',
            '9905' => 'recurringCount is required',
            '9906' => 'recurringInterval or ChargeOnDate is required',
            '9907' => 'Invalid ChargeNextDate',
            '9908' => 'Invalid ChargeOnDate',
            '9909' => 'chargeNextDate is required',
            '9990' => 'Request to merchant front end has failed',
            '9991' => 'Request merchant secure has failed',
            '9992' => 'Request payment secure has failed',
            '9993' => 'An unknown error has occured',
            '9994' => 'Request DB service has failed',
            '9995' => 'Request payment service has failed',
            '9996' => 'Request Qwik service has failed',
            '9997' => 'Request user preferences has failed',
            '9998' => 'Request store card has failed',
            '9999' => 'Request to merchant backend has failed'
        ]
    ]
]);

