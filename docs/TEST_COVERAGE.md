# Test coverage

Generated 2026-11-03 from the PHPUnit suite in `tests/TestCase` (CakePHP 5.4, PHPUnit 11, PHP 8.5).

> **What this measures.** No coverage driver (PCOV/Xdebug) is installed for the Laragon PHP 8.5 build, so this is not line coverage.
> It maps each class in `src/` to the test files that exercise it, and each public method or controller action to whether a test calls it
> (for controllers: whether a test requests one of its routes). Framework hooks (`initialize`, `validationDefault`, `buildRules`,
> `beforeFilter`, `execute`, …) are left out because CakePHP calls them, not the tests. A method marked tested is called at least once;
> its branches may still be uncovered. See [Line coverage](#line-coverage) to get real numbers.

## Summary

| | |
|---|---|
| Test files | 69 |
| Tests | 744 (incl. data-provider cases) |
| Passed | 705 |
| Skipped | 39 (see [Skipped tests](#skipped-tests)) |
| Failed / errors | 0 |
| Classes in `src/` with at least one test | 78 of 156 |

### By area

| Area | Classes | With tests | Methods / actions called by tests |
|---|---:|---:|---:|
| Controllers | 13 | 9 | 56 / 57 (98%) |
| Components | 6 | 6 | 30 / 42 (71%) |
| Lib | 9 | 8 | 46 / 51 (90%) |
| Util | 4 | 4 | 7 / 7 (100%) |
| Tables | 54 | 25 | 24 / 27 (89%) |
| Behaviors | 1 | 1 | – |
| Entities | 52 | 14 | – |
| Commands | 3 | 3 | – |
| Http (sessions) | 2 | 1 | 5 / 11 (45%) |
| Filesystem shims | 2 | 2 | 6 / 6 (100%) |
| View | 4 | 3 | 1 / 1 (100%) |
| Forms | 2 | 1 | 0 / 1 (0%) |
| Logging | 1 | 0 | 0 / 1 (0%) |
| Error | 1 | 0 | 0 / 4 (0%) |
| Console | 1 | 0 | 0 / 7 (0%) |
| Application | 1 | 1 | 0 / 1 (0%) |

## Controllers

| Controller | Tests | Actions requested by tests | Not requested | Public, but no route |
|---|---|---|---|---|
| [AgentTopupsController](../src/Controller/AgentTopupsController.php) | [AgentTopupsControllerTest](../tests/TestCase/Controller/AgentTopupsControllerTest.php)<br>[ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php)<br>[SecurityTest](../tests/TestCase/Controller/SecurityTest.php) | `index`, `view`, `add`, `topupImages` | – | – |
| [AgentUsersController](../src/Controller/AgentUsersController.php) | [AgentUsersControllerTest](../tests/TestCase/Controller/AgentUsersControllerTest.php)<br>[AttractionsControllerTest](../tests/TestCase/Controller/AttractionsControllerTest.php)<br>[AuthenticationTest](../tests/TestCase/Controller/AuthenticationTest.php)<br>[OrdersControllerTest](../tests/TestCase/Controller/OrdersControllerTest.php)<br>[ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php)<br>[SecurityTest](../tests/TestCase/Controller/SecurityTest.php)<br>[TwoFactorLoginTest](../tests/TestCase/Controller/TwoFactorLoginTest.php)<br>[VoucherMergingControllerTest](../tests/TestCase/Controller/VoucherMergingControllerTest.php) | `login`, `changePassword`, `otp`, `resendOtp`, `twoFactorSetup`, `twoFactorBackupCodes`, `twoFactor`, `resetTwoFactor`, `resetPassword`, `setPassword`, `forgotPassword`, `logout`, `contact`, `view`, `index`, `add`, `read`, `edit`, `agentResetPassword`, `suspend` | – | – |
| [AgentsController](../src/Controller/AgentsController.php) | **none** | – | – | `index`, `view`, `creditLogs`, `SubAgents`, `agentUsers`, `notActive`, `tickets`, `add`, `addSubagent`, `edit`, `api`, `delete` |
| [AgentsSignupsController](../src/Controller/AgentsSignupsController.php) | [AgentsSignupsControllerTest](../tests/TestCase/Controller/AgentsSignupsControllerTest.php)<br>[AuthenticationTest](../tests/TestCase/Controller/AuthenticationTest.php)<br>[ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php) | `add`, `thankyou` | – | `index` |
| [AttractionsController](../src/Controller/AttractionsController.php) | [AttractionsControllerTest](../tests/TestCase/Controller/AttractionsControllerTest.php)<br>[ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php) | `index`, `view`, `viewTwoWay`, `getTickets`, `getTwoWayTickets`, `getFixeddateTime`, `getAvailability`, `toggleFavorite` | – | – |
| [CategoriesController](../src/Controller/CategoriesController.php) | **none** | – | – | `view` |
| [CreditLogsController](../src/Controller/CreditLogsController.php) | [CreditLogsControllerTest](../tests/TestCase/Controller/CreditLogsControllerTest.php)<br>[ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php) | `index` | – | – |
| [OrdersController](../src/Controller/OrdersController.php) | [AttractionsControllerTest](../tests/TestCase/Controller/AttractionsControllerTest.php)<br>[AuthenticationTest](../tests/TestCase/Controller/AuthenticationTest.php)<br>[OrdersControllerTest](../tests/TestCase/Controller/OrdersControllerTest.php)<br>[ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php)<br>[SecurityTest](../tests/TestCase/Controller/SecurityTest.php)<br>[Payment2C2PDbTest](../tests/TestCase/Lib/Payment2C2PDbTest.php)<br>[PaymentPaypalDbTest](../tests/TestCase/Lib/PaymentPaypalDbTest.php)<br>[PaymentPaypalTest](../tests/TestCase/Lib/PaymentPaypalTest.php)<br>[RoutesTest](../tests/TestCase/RoutesTest.php) | `cart`, `checkout`, `confirmation`, `getStatus`, `frontendNotificationReceiver`, `notificationReceiver`, `thankYou`, `downloadInvoice`, `removeTicket`, `index`, `myTickets`, `view`, `cancelVoucher` | `sps` | `addToCart`, `subtractQuantity`, `removeFromCart`, `completeOrder`, `executeStripe` |
| [ReportsController](../src/Controller/ReportsController.php) | [ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php)<br>[ReportsControllerTest](../tests/TestCase/Controller/ReportsControllerTest.php) | `index`, `reports`, `download` | – | `usage` |
| [VoucherMergingController](../src/Controller/VoucherMergingController.php) | [ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php)<br>[VoucherMergingControllerTest](../tests/TestCase/Controller/VoucherMergingControllerTest.php) | `getStatus` | – | – |
| [VouchersController](../src/Controller/VouchersController.php) | [ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php)<br>[SecurityTest](../tests/TestCase/Controller/SecurityTest.php)<br>[VouchersControllerTest](../tests/TestCase/Controller/VouchersControllerTest.php)<br>[RoutesTest](../tests/TestCase/RoutesTest.php) | `apiview`, `sdownload`, `downloadAll`, `download` | – | `view`, `sview`, `apidownload` |

Actions under "no route" can't be reached: `config/routes.php` connects every route explicitly and has no fallbacks. `AgentsController` and `CategoriesController` have no routes at all.

## Components

| Class | Tests | Called by tests | Not called |
|---|---|---|---|
| [InputCleanerComponent](../src/Controller/Component/InputCleanerComponent.php) | [InputCleanerComponentTest](../tests/TestCase/Controller/Component/InputCleanerComponentTest.php) | `startup` | – |
| [MailerComponent](../src/Controller/Component/MailerComponent.php) | [MailerComponentTest](../tests/TestCase/Controller/Component/MailerComponentTest.php) | `mail` | – |
| [S3Component](../src/Controller/Component/S3Component.php) | [S3ComponentTest](../tests/TestCase/Controller/Component/S3ComponentTest.php) | `headObject`, `getObject`, `putObject`, `putObjectAsStream`, `deleteObject`, `getFolderSize`, `getMimeType` | `ListObjects`, `getIterator`, `copyObject`, `getCommand`, `deleteObjects` |
| [S3PublicComponent](../src/Controller/Component/S3PublicComponent.php) | [S3ComponentTest](../tests/TestCase/Controller/Component/S3ComponentTest.php) | `headObject`, `getObject`, `putObject`, `putObjectAsStream`, `deleteObject`, `getFolderSize`, `getMimeType` | `ListObjects`, `getIterator`, `copyObject`, `getCommand`, `deleteObjects` |
| [ShoppingCartComponent](../src/Controller/Component/ShoppingCartComponent.php) | [ShoppingCartComponentTest](../tests/TestCase/Controller/Component/ShoppingCartComponentTest.php) | `decrease`, `remove`, `add`, `clear`, `getAll`, `updateQuantity`, `getCount`, `hasDuplicateSupplier`, `addReturnDatetime` | `gettickets` |
| [TicketsHelperComponent](../src/Controller/Component/TicketsHelperComponent.php) | [TicketsHelperComponentTest](../tests/TestCase/Controller/Component/TicketsHelperComponentTest.php) | `getTicketAvailableQty`, `getOpenDateAvailableQuantity`, `getTicketResellerPrice`, `getFixeddateResellerPrice`, `getFixeddate` | `getTicketAllotment` |

## Lib

| Class | Tests | Called by tests | Not called |
|---|---|---|---|
| [ApiCaller](../src/Lib/ApiCaller.php) | [ApiCallerTest](../tests/TestCase/Lib/ApiCallerTest.php) | `get`, `post` | `getAttractions` |
| [Payment2C2P](../src/Lib/Payment2C2P.php) | [Payment2C2PDbTest](../tests/TestCase/Lib/Payment2C2PDbTest.php)<br>[Payment2C2PTest](../tests/TestCase/Lib/Payment2C2PTest.php) | `requestPaymentToken`, `isPaymentSuccess`, `getOrderRefId`, `verifyPayment`, `getMarkup` | `getRedirectLink` |
| [PaymentPaypal](../src/Lib/PaymentPaypal.php) | [PaymentPaypalDbTest](../tests/TestCase/Lib/PaymentPaypalDbTest.php)<br>[PaymentPaypalTest](../tests/TestCase/Lib/PaymentPaypalTest.php)<br>[PaymentsTest](../tests/TestCase/Lib/PaymentsTest.php) | `getRedirectLink`, `createOrder`, `getOrderRefId`, `capturePayment`, `verifyPayment`, `getMarkup`, `verifyWebhook` | – |
| [PaymentStripe](../src/Lib/PaymentStripe.php) | [PaymentStripeTest](../tests/TestCase/Lib/PaymentStripeTest.php)<br>[PaymentsTest](../tests/TestCase/Lib/PaymentsTest.php) | `getOrderRefId`, `verifyPayment`, `getMarkup` | `getRedirectLink` |
| [Payments](../src/Lib/Payments.php) | [PaymentsConfigTest](../tests/TestCase/Lib/PaymentsConfigTest.php)<br>[PaymentsTest](../tests/TestCase/Lib/PaymentsTest.php)<br>[OrdersTableTest](../tests/TestCase/Model/Table/OrdersTableTest.php) | `config`, `handlePayments`, `getOrderRefId`, `isPaymentSuccess`, `verifyPayment`, `getMarkup`, `getPaymentMethodList`, `getPaymentMethods`, `getPaymentMarkups`, `getPaymentProvider`, `isCreditsEnabled` | – |
| [PricingType](../src/Lib/PricingType.php) | **none** | – | – |
| [QueueSender](../src/Lib/QueueSender.php) | [QueueSenderTest](../tests/TestCase/Lib/QueueSenderTest.php) | `send` | – |
| [Translator](../src/Lib/Translator.php) | [TranslatorTest](../tests/TestCase/Lib/TranslatorTest.php) | `translateAttraction`, `translateTicket`, `translateCategory` | – |
| [TwoFactor](../src/Lib/TwoFactor.php) | [TwoFactorLoginTest](../tests/TestCase/Controller/TwoFactorLoginTest.php)<br>[TwoFactorTest](../tests/TestCase/Lib/TwoFactorTest.php) | `required`, `usesAuthenticator`, `isSetUp`, `newSecret`, `qrSvg`, `formatSecret`, `verify`, `useBackupCode`, `backupCodesLeft`, `enable`, `regenerateBackupCodes`, `reset`, `encrypt`, `decrypt` | `matchStep`, `newBackupCodes` |

## Util

| Class | Tests | Called by tests | Not called |
|---|---|---|---|
| [CatMailer](../src/Util/CatMailer.php) | [Payment2C2PDbTest](../tests/TestCase/Lib/Payment2C2PDbTest.php)<br>[Payment2C2PTest](../tests/TestCase/Lib/Payment2C2PTest.php)<br>[CatMailerTest](../tests/TestCase/Util/CatMailerTest.php) | `mail` | – |
| [ClientIp](../src/Util/ClientIp.php) | [ClientIpTest](../tests/TestCase/Util/ClientIpTest.php) | `fromRequest`, `inRanges` | – |
| [Csv](../src/Util/Csv.php) | [CsvTest](../tests/TestCase/Util/CsvTest.php) | `writeReport`, `appendToReport`, `finishReport` | – |
| [Rounder](../src/Util/Rounder.php) | [RounderTest](../tests/TestCase/Util/RounderTest.php) | `roundUpCent` | – |

## Commands

| Class | Tests | Called by tests | Not called |
|---|---|---|---|
| [DeleteTempCommand](../src/Command/DeleteTempCommand.php) | [DeleteTempCommandTest](../tests/TestCase/Command/DeleteTempCommandTest.php) | – | – |
| [GenerateReportCommand](../src/Command/GenerateReportCommand.php) | [GenerateReportCommandTest](../tests/TestCase/Command/GenerateReportCommandTest.php) | – | – |
| [TwoFactorResetCommand](../src/Command/TwoFactorResetCommand.php) | [TwoFactorResetCommandTest](../tests/TestCase/Command/TwoFactorResetCommandTest.php) | – | – |

## Http, Filesystem, View, Forms, Logging

| Class | Tests | Called by tests | Not called |
|---|---|---|---|
| [CustomDatabaseSession](../src/Http/Session/CustomDatabaseSession.php) | [CustomDatabaseSessionTest](../tests/TestCase/Http/Session/CustomDatabaseSessionTest.php) | `setTimeout`, `read`, `write`, `destroy`, `gc` | `open`, `close` |
| [CustomSession](../src/Http/Session/CustomSession.php) | **none** | – | `read`, `write`, `destroy`, `gc` |
| [File](../src/Filesystem/File.php) | [FilesystemTest](../tests/TestCase/Filesystem/FilesystemTest.php) | `exists`, `name`, `lastChange`, `delete`, `mime` | – |
| [Folder](../src/Filesystem/Folder.php) | [FilesystemTest](../tests/TestCase/Filesystem/FilesystemTest.php) | `find` | – |
| [AjaxView](../src/View/AjaxView.php) | [AjaxViewTest](../tests/TestCase/View/AjaxViewTest.php) | – | – |
| [AppView](../src/View/AppView.php) | [QuestionGroupHelperTest](../tests/TestCase/View/Helper/QuestionGroupHelperTest.php) | – | – |
| [CreditsCell](../src/View/Cell/CreditsCell.php) | **none** | – | – |
| [QuestionGroupHelper](../src/View/Helper/QuestionGroupHelper.php) | [QuestionGroupHelperTest](../tests/TestCase/View/Helper/QuestionGroupHelperTest.php) | `render` | – |
| [QuestionGroupForm](../src/Form/QuestionGroupForm.php) | **none** | – | `getOrderData` |
| [SignupForm](../src/Form/SignupForm.php) | [AgentsSignupsControllerTest](../tests/TestCase/Controller/AgentsSignupsControllerTest.php) | – | – |
| [DbLog](../src/Log/Engine/DbLog.php) | **none** | – | `log` |
| [ValidationError](../src/Error/ValidationError.php) | **none** | – | `getCode`, `getResult`, `setCode`, `setResult` |

## Behaviors

| Class | Tests | Called by tests | Not called |
|---|---|---|---|
| [LoggableBehavior](../src/Model/Behavior/LoggableBehavior.php) | [AuthenticationTest](../tests/TestCase/Controller/AuthenticationTest.php)<br>[SecurityTest](../tests/TestCase/Controller/SecurityTest.php)<br>[TwoFactorLoginTest](../tests/TestCase/Controller/TwoFactorLoginTest.php)<br>[LoggableBehaviorTest](../tests/TestCase/Model/Behavior/LoggableBehaviorTest.php) | – | – |

## Tables with tests

| Class | Tests | Called by tests | Not called |
|---|---|---|---|
| [AdminsTable](../src/Model/Table/AdminsTable.php) | [AdminsTableTest](../tests/TestCase/Model/Table/AdminsTableTest.php) | – | – |
| [AgentUsersTable](../src/Model/Table/AgentUsersTable.php) | [AgentUsersTableTest](../tests/TestCase/Model/Table/AgentUsersTableTest.php) | – | – |
| [AgentsSignupsTable](../src/Model/Table/AgentsSignupsTable.php) | [AgentsSignupsTableTest](../tests/TestCase/Model/Table/AgentsSignupsTableTest.php) | – | – |
| [AgentsTable](../src/Model/Table/AgentsTable.php) | [AgentsTableTest](../tests/TestCase/Model/Table/AgentsTableTest.php) | – | – |
| [AttractionTranslationsTable](../src/Model/Table/AttractionTranslationsTable.php) | [AttractionTranslationsTableTest](../tests/TestCase/Model/Table/AttractionTranslationsTableTest.php) | `findTranslation` | – |
| [AttractionsTable](../src/Model/Table/AttractionsTable.php) | [AttractionsTableTest](../tests/TestCase/Model/Table/AttractionsTableTest.php) | – | – |
| [BarcodesTable](../src/Model/Table/BarcodesTable.php) | [BarcodesTableTest](../tests/TestCase/Model/Table/BarcodesTableTest.php) | – | – |
| [CategoryTranslationsTable](../src/Model/Table/CategoryTranslationsTable.php) | [CategoryTranslationsTableTest](../tests/TestCase/Model/Table/CategoryTranslationsTableTest.php) | `findTranslation` | – |
| [CreditLogsTable](../src/Model/Table/CreditLogsTable.php) | [CreditLogsTableTest](../tests/TestCase/Model/Table/CreditLogsTableTest.php) | `credit` | – |
| [FavoriteAttractionsTable](../src/Model/Table/FavoriteAttractionsTable.php) | [FavoriteAttractionsTableTest](../tests/TestCase/Model/Table/FavoriteAttractionsTableTest.php) | `toggle` | – |
| [InventoriesTable](../src/Model/Table/InventoriesTable.php) | [InventoriesTableTest](../tests/TestCase/Model/Table/InventoriesTableTest.php) | `purchase` | – |
| [LastOtpIpsTable](../src/Model/Table/LastOtpIpsTable.php) | [LastOtpIpsTableTest](../tests/TestCase/Model/Table/LastOtpIpsTableTest.php) | `getLatestIps`, `addAndPrune` | – |
| [LoginsTable](../src/Model/Table/LoginsTable.php) | [LoginsTableTest](../tests/TestCase/Model/Table/LoginsTableTest.php) | `isAllowed`, `clearLogin` | – |
| [MerchantsTable](../src/Model/Table/MerchantsTable.php) | [MerchantsTableTest](../tests/TestCase/Model/Table/MerchantsTableTest.php) | – | – |
| [OptionsTable](../src/Model/Table/OptionsTable.php) | [OptionsTableTest](../tests/TestCase/Model/Table/OptionsTableTest.php) | `getValueByName` | – |
| [OrderItemsTable](../src/Model/Table/OrderItemsTable.php) | [OrderItemsTableTest](../tests/TestCase/Model/Table/OrderItemsTableTest.php) | – | – |
| [OrdersTable](../src/Model/Table/OrdersTable.php) | [PaymentPaypalTest](../tests/TestCase/Lib/PaymentPaypalTest.php)<br>[OrdersTableTest](../tests/TestCase/Model/Table/OrdersTableTest.php) | `setPaymentMethod`, `setConvenienceFee`, `createInvoice` | `setPaymentId` |
| [PasswordResetsTable](../src/Model/Table/PasswordResetsTable.php) | [PasswordResetsTableTest](../tests/TestCase/Model/Table/PasswordResetsTableTest.php) | – | – |
| [PaymentResponsesTable](../src/Model/Table/PaymentResponsesTable.php) | [PaymentResponsesTableTest](../tests/TestCase/Model/Table/PaymentResponsesTableTest.php) | `create`, `setOrderRefId` | – |
| [SettlementsTable](../src/Model/Table/SettlementsTable.php) | [SettlementsTableTest](../tests/TestCase/Model/Table/SettlementsTableTest.php) | – | – |
| [TicketTranslationsTable](../src/Model/Table/TicketTranslationsTable.php) | [TicketTranslationsTableTest](../tests/TestCase/Model/Table/TicketTranslationsTableTest.php) | `findTranslation` | – |
| [TicketsTable](../src/Model/Table/TicketsTable.php) | [TicketsTableTest](../tests/TestCase/Model/Table/TicketsTableTest.php) | `consume`, `getReturnTicket`, `isValidReturnTicket` | – |
| [UsersIpaddressTable](../src/Model/Table/UsersIpaddressTable.php) | [UsersIpaddressTableTest](../tests/TestCase/Model/Table/UsersIpaddressTableTest.php) | `addOrUpdate` | – |
| [VoucherMergingTable](../src/Model/Table/VoucherMergingTable.php) | [VoucherMergingTableTest](../tests/TestCase/Model/Table/VoucherMergingTableTest.php) | `isJobExists` | – |
| [VouchersTable](../src/Model/Table/VouchersTable.php) | [VouchersTableTest](../tests/TestCase/Model/Table/VouchersTableTest.php) | `belongsToAgent`, `isCancellable`, `getStatus` | – |

## Tables without a test

Many of these are only read through associations from tested controllers and tables, so they run during the suite but have no test of their own.

[AgentSessionsTable](../src/Model/Table/AgentSessionsTable.php), [AgentTopupsTable](../src/Model/Table/AgentTopupsTable.php), [AllotmentsTable](../src/Model/Table/AllotmentsTable.php), [AnnouncementsTable](../src/Model/Table/AnnouncementsTable.php), [ApiPdfsTable](../src/Model/Table/ApiPdfsTable.php), [CategoriesTable](../src/Model/Table/CategoriesTable.php), [CitiesTable](../src/Model/Table/CitiesTable.php), [ConnectionsTable](../src/Model/Table/ConnectionsTable.php), [FdateOverrideRatesTable](../src/Model/Table/FdateOverrideRatesTable.php), [FdatesettlementsAgentsTable](../src/Model/Table/FdatesettlementsAgentsTable.php), [FdatesettlementsTable](../src/Model/Table/FdatesettlementsTable.php), [FixeddatesAgentsTable](../src/Model/Table/FixeddatesAgentsTable.php), [FixeddatesTable](../src/Model/Table/FixeddatesTable.php), [GeneratedCodesTable](../src/Model/Table/GeneratedCodesTable.php), [LogsTable](../src/Model/Table/LogsTable.php), [OnefgPerformanceProductsTable](../src/Model/Table/OnefgPerformanceProductsTable.php), [OrdersToCompleteTable](../src/Model/Table/OrdersToCompleteTable.php), [PackagesTicketsTable](../src/Model/Table/PackagesTicketsTable.php), [PaymentGatewaysTable](../src/Model/Table/PaymentGatewaysTable.php), [PaymentMarkupsTable](../src/Model/Table/PaymentMarkupsTable.php), [PaymentMethodsTable](../src/Model/Table/PaymentMethodsTable.php), [PaypalRecordsTable](../src/Model/Table/PaypalRecordsTable.php), [PdfsTable](../src/Model/Table/PdfsTable.php), [RecommendedAttractionsTable](../src/Model/Table/RecommendedAttractionsTable.php), [RwsProductsTable](../src/Model/Table/RwsProductsTable.php), [SettlementAgentsTable](../src/Model/Table/SettlementAgentsTable.php), [StripeRecordsTable](../src/Model/Table/StripeRecordsTable.php), [TagsTable](../src/Model/Table/TagsTable.php), [TicketTripTypesTable](../src/Model/Table/TicketTripTypesTable.php)

## Entities

52 entity classes. They hold only `$_accessible` / `$_hidden` and virtual-field getters, and are covered indirectly through the table and controller tests.

## Not covered by any test

- [Console/Installer.php](../src/Console/Installer.php)
- [Controller/AgentsController.php](../src/Controller/AgentsController.php)
- [Controller/AppController.php](../src/Controller/AppController.php)
- [Controller/CategoriesController.php](../src/Controller/CategoriesController.php)
- [Controller/ErrorController.php](../src/Controller/ErrorController.php)
- [Error/ValidationError.php](../src/Error/ValidationError.php)
- [Form/QuestionGroupForm.php](../src/Form/QuestionGroupForm.php)
- [Http/Session/CustomSession.php](../src/Http/Session/CustomSession.php)
- [Lib/PricingType.php](../src/Lib/PricingType.php)
- [Log/Engine/DbLog.php](../src/Log/Engine/DbLog.php)
- [View/Cell/CreditsCell.php](../src/View/Cell/CreditsCell.php)
- plus 29 tables listed in [Tables without a test](#tables-without-a-test)

## Skipped tests

Tests skip themselves with a `BUG:` reason when they hit a known defect in the app, so the suite stays green while the bug is recorded. Fixing the bug turns the test back on.

### Open bugs (test skipped in this run)

| Test file | Test | Bug |
|---|---|---|
| [TwoFactorResetCommandTest](../tests/TestCase/Command/TwoFactorResetCommandTest.php) | `testResetsTheUsersAuthenticator` | TwoFactorResetCommand logs type "two_factor_reset" (16 chars), logs.type is varchar(15) |
| [AgentUsersControllerTest](../tests/TestCase/Controller/AgentUsersControllerTest.php) | `testSetPasswordWithVerificationCode` | setPassword() needs agent_users.password NULL, but the column is NOT NULL, so the flow can never run. |
| [AgentUsersControllerTest](../tests/TestCase/Controller/AgentUsersControllerTest.php) | `testReadAjaxWithUnknownIdIs404` | AgentUsersController::read() dereferences a null user for an unknown id (500 instead of 404). |
| [AttractionsControllerTest](../tests/TestCase/Controller/AttractionsControllerTest.php) | `testGetTicketsForFixedDateAttractionWithoutDateIs400` | getTickets on a non-settlement attraction without ?date calls format() on null (500). |
| [AttractionsControllerTest](../tests/TestCase/Controller/AttractionsControllerTest.php) | `testGetTwoWayTicketsWithoutParamsIs400` | getTwoWayTickets without departure/return date+time calls format() on null (500). |
| [AttractionsControllerTest](../tests/TestCase/Controller/AttractionsControllerTest.php) | `testGetAvailabilityForTicketWithConnection` | getAvailability calls $this->_<function_name>Availability(), which does not exist in AttractionsController (500). |
| [AttractionsControllerTest](../tests/TestCase/Controller/AttractionsControllerTest.php) | `testToggleFavoriteWithoutAttractionIdIs400` | toggleFavorite without attraction_id passes validation and queries where([id => null]), which throws (500). |
| [MailerComponentTest](../tests/TestCase/Controller/Component/MailerComponentTest.php) | `testOtherTransportErrorIsReportedToAdmin` | MailerComponent catches `Exception` without importing it (AppControllerComponentException), so non-socket mail errors escape instead of being reported. |
| [S3ComponentTest](../tests/TestCase/Controller/Component/S3ComponentTest.php) | `testPublicGetFolderSizeListsThePublicBucket` | S3PublicComponent::getFolderSize() lists AWS.Bucket (private) instead of AWSPublic.Bucket. |
| [ShoppingCartComponentTest](../tests/TestCase/Controller/Component/ShoppingCartComponentTest.php) | `testDecrease` | ShoppingCartComponent::decrease() subtracts from the ticket's date array (ShoppingCart.<id>) instead of ShoppingCart.<id>.<date>. |
| [OrdersControllerTest](../tests/TestCase/Controller/OrdersControllerTest.php) | `testGetStatusWithoutOrderInSessionIs404` | getStatus without session order_id queries where([id => null]), which throws (500). |
| [OrdersControllerTest](../tests/TestCase/Controller/OrdersControllerTest.php) | `testThankYouWithoutOrderRenders` | thankYou without session order_id queries where([Orders.id => null]), which throws (500). |
| [OrdersControllerTest](../tests/TestCase/Controller/OrdersControllerTest.php) | `testMyTicketsWithBadFromDateIsIgnored` | myTickets with an unparsable ?from throws from DateTime::createFromFormat (500). |
| [OrdersControllerTest](../tests/TestCase/Controller/OrdersControllerTest.php) | `testViewWhenApiUnreachableRedirectsToOrders` | Orders::view renders the template without $order when the API call throws (undefined-variable warnings) instead of redirecting to /orders. |
| [VouchersControllerTest](../tests/TestCase/Controller/VouchersControllerTest.php) | `testSdownloadByMasterOfTheAgent` | VouchersController::sdownload() uses $this->AgentUsers without loading it (500 for masters). |
| [PaymentPaypalDbTest](../tests/TestCase/Lib/PaymentPaypalDbTest.php) | `testCapturePayment` | PaymentPaypal::capturePayment() saves to EventPaypalRecords (event_paypal_records), a table this app does not have. |
| [PaymentPaypalTest](../tests/TestCase/Lib/PaymentPaypalTest.php) | `testCreateOrderReturnsPaypalOrder` | PaymentPaypal::createOrder() (unused) builds a URL for the unrouted Orders::complete action and calls the nonexistent OrdersTable::setPid() |
| [PaymentPaypalTest](../tests/TestCase/Lib/PaymentPaypalTest.php) | `testWebhookRejectedWhenVerificationFails` | PaymentPaypal::verifyWebhook() concatenates the response array into its log message ("Array to string conversion"). |
| [PaymentsConfigTest](../tests/TestCase/Lib/PaymentsConfigTest.php) | `testGatewaySettings` | Payments::config() json_decode()s payment_gateways.data, which the ORM already decodes (json column) |
| [PaymentsTest](../tests/TestCase/Lib/PaymentsTest.php) | `testStripeAndPaypalPaymentSuccessCheck` | Payments::isPaymentSuccess() calls PaymentStripe/PaymentPaypal::isPaymentSuccess(), which do not exist (fatal error for Stripe/Paypal returns). |
| [QueueSenderTest](../tests/TestCase/Lib/QueueSenderTest.php) | `testGetRequest` | QueueSender::send() with type "get" signs an array (hash_hmac TypeError before the try block). |
| [LoggableBehaviorTest](../tests/TestCase/Model/Behavior/LoggableBehaviorTest.php) | `testDeleteIsLogged` | LoggableBehavior::afterDelete logs user_table |
| [AgentsSignupsTableTest](../tests/TestCase/Model/Table/AgentsSignupsTableTest.php) | `testRulesUniqueEmail` | AgentsSignupsTable belongsTo MasterAgents has no className (table master_agents does not exist), so its existsIn rule throws |
| [CreditLogsTableTest](../tests/TestCase/Model/Table/CreditLogsTableTest.php) | `testCreditDeductsTheOrderTotalFromTheLatestBalance` | CreditLogsTable::credit raw INSERT fails (UUID() is 36 chars, credit_logs.id is binary(16)) |
| [OrdersTableTest](../tests/TestCase/Model/Table/OrdersTableTest.php) | `testRulesAgentMustExist` | OrdersTable existsIn(agent_id, Agents) uses the belongsToMany bindingKey agent_user_id (queries Agents.agent_user_id) |
| [OrdersTableTest](../tests/TestCase/Model/Table/OrdersTableTest.php) | `testConvenienceFeeGrossUpRoundsUpToTheCent` | setConvenienceFee truncates 100/(100-rate) to 4 decimals, so the gross-up can be a cent low |
| [OrdersTableTest](../tests/TestCase/Model/Table/OrdersTableTest.php) | `testConvenienceFeeStripePercentageKeepsDecimals` | setConvenienceFee (Stripe) calls bcmul(subtotal, rate) without a scale, truncating subtotal*rate to an integer |
| [OrdersTableTest](../tests/TestCase/Model/Table/OrdersTableTest.php) | `testConvenienceFeeForCreditsIsZero` | setConvenienceFee for credits (no provider): Payments::getMarkup returns int 0, read as an array |
| [VouchersTableTest](../tests/TestCase/Model/Table/VouchersTableTest.php) | `testFlexiCancellableWhenAllChildrenUnused` | VouchersTable::isCancellable loads GeneratedCodes.ChildGeneratedCodes, an association that does not exist (flexi tickets throw) |
| [VouchersTableTest](../tests/TestCase/Model/Table/VouchersTableTest.php) | `testGetStatusLooksUpTheVoucherType` | VouchersTable::getStatus without a type queries vouchers.code, the column is code_id |
| [RoutesTest](../tests/TestCase/RoutesTest.php) | `testNotificationReceiverRouteWithoutProvider` | /orders/notification-receiver passes no provider, but OrdersController::notificationReceiver($provider) requires one (ArgumentCountError). |
| [CatMailerTest](../tests/TestCase/Util/CatMailerTest.php) | `testMailUsesTheGivenFromAddress` | CatMailer stores $from_email but never calls setFrom(); the mail profile's From is used. |

### Bugs already fixed (guard did not trigger, test passed)

These tests still contain a `BUG:` skip, but the bug no longer happens, so the test ran and passed. The skip guards can be removed.

| Test file | Test | Former bug |
|---|---|---|
| [Payment2C2PDbTest](../tests/TestCase/Lib/Payment2C2PDbTest.php) | `requestToken` | Payment2C2P::requestPaymentToken() passes ['fullBase' => true] as Router::url()'s bool $full (TypeError: 2C2P checkout cannot start). |
| [Payment2C2PDbTest](../tests/TestCase/Lib/Payment2C2PDbTest.php) | `testVerifyPaymentWithAmountMismatchMailsTheCompany` | CatMailer requires a string From, but Default.from_email is an [email => name] array (TypeError instead of the discrepancy mail). |
| [Payment2C2PTest](../tests/TestCase/Lib/Payment2C2PTest.php) | `testVerifyPaymentWithFailedResponseCodeMailsTheCompany` | CatMailer requires a string From, but Default.from_email is an [email => name] array (TypeError instead of the alert mail). |

### Other skips

| Test file | Test | Reason |
|---|---|---|
| [PaymentStripeTest](../tests/TestCase/Lib/PaymentStripeTest.php) | `testCompletedCheckoutReturnsOrderRefId` | Not testable from the CLI: needs a signed Stripe webhook body in php://input. |

## Test files

| Test file | What it covers | Tests | Passed | Skipped |
|---|---|---:|---:|---:|
| [ApplicationTest](../tests/TestCase/ApplicationTest.php) | CakePHP(tm) : Rapid Development Framework (https://cakephp.org) | 3 | 3 | 0 |
| [DeleteTempCommandTest](../tests/TestCase/Command/DeleteTempCommandTest.php) | `bin/cake delete_temp` deletes files older than two days under WWW_ROOT/files/pdf, | 1 | 1 | 0 |
| [GenerateReportCommandTest](../tests/TestCase/Command/GenerateReportCommandTest.php) | `bin/cake generate_report`: writes the sales report CSV of the given agent users' orders | 5 | 5 | 0 |
| [TwoFactorResetCommandTest](../tests/TestCase/Command/TwoFactorResetCommandTest.php) | `bin/cake two_factor reset <email>` removes an agent user's authenticator and backup codes. | 3 | 2 | 1 |
| [AgentTopupsControllerTest](../tests/TestCase/Controller/AgentTopupsControllerTest.php) | App\Controller\AgentTopupsController: top-up list/CSV, view and upload (masters only). | 9 | 9 | 0 |
| [AgentUsersControllerTest](../tests/TestCase/Controller/AgentUsersControllerTest.php) | App\Controller\AgentUsersController: login, logout, password flows, sub-user management. | 33 | 31 | 2 |
| [AgentsSignupsControllerTest](../tests/TestCase/Controller/AgentsSignupsControllerTest.php) | App\Controller\AgentsSignupsController: public signup form posting to the backend API. | 5 | 5 | 0 |
| [AttractionsControllerTest](../tests/TestCase/Controller/AttractionsControllerTest.php) | App\Controller\AttractionsController | 24 | 20 | 4 |
| [AuthenticationTest](../tests/TestCase/Controller/AuthenticationTest.php) | Guards the AuthComponent behaviour kept in the CakePHP 5 upgrade | 5 | 5 | 0 |
| [InputCleanerComponentTest](../tests/TestCase/Controller/Component/InputCleanerComponentTest.php) |  | 4 | 4 | 0 |
| [MailerComponentTest](../tests/TestCase/Controller/Component/MailerComponentTest.php) | Records each delivery (as a snapshot, the Mailer reuses its message) and can fail first. | 5 | 4 | 1 |
| [S3ComponentTest](../tests/TestCase/Controller/Component/S3ComponentTest.php) | S3 wrappers, with the AWS SDK's MockHandler instead of the network. | 13 | 12 | 1 |
| [ShoppingCartComponentTest](../tests/TestCase/Controller/Component/ShoppingCartComponentTest.php) | Session cart: `ShoppingCart.<ticket_id>.<date>` => qty. | 8 | 7 | 1 |
| [TicketsHelperComponentTest](../tests/TestCase/Controller/Component/TicketsHelperComponentTest.php) | Availability and reseller pricing helpers. | 12 | 12 | 0 |
| [CreditLogsControllerTest](../tests/TestCase/Controller/CreditLogsControllerTest.php) | App\Controller\CreditLogsController: the agent's balance history and its CSV export. | 5 | 5 | 0 |
| [OrdersControllerTest](../tests/TestCase/Controller/OrdersControllerTest.php) | App\Controller\OrdersController | 35 | 31 | 4 |
| [ProtectedRoutesTest](../tests/TestCase/Controller/ProtectedRoutesTest.php) | Every routed page except the public ones sends a logged-out visitor to the login page. | 49 | 49 | 0 |
| [ReportsControllerTest](../tests/TestCase/Controller/ReportsControllerTest.php) | App\Controller\ReportsController: report request form, generated report list and download. | 8 | 8 | 0 |
| [SecurityTest](../tests/TestCase/Controller/SecurityTest.php) | Regression tests for the security fixes (access control, OTP, redirects, | 11 | 11 | 0 |
| [TwoFactorLoginTest](../tests/TestCase/Controller/TwoFactorLoginTest.php) | Login with the second factor: authenticator app, emailed OTP, backup codes, | 14 | 14 | 0 |
| [VoucherMergingControllerTest](../tests/TestCase/Controller/VoucherMergingControllerTest.php) | App\Controller\VoucherMergingController::getStatus(): merge status of the agent's own orders only. | 3 | 3 | 0 |
| [VouchersControllerTest](../tests/TestCase/Controller/VouchersControllerTest.php) | App\Controller\VouchersController: signed download links, S3 views, merged downloads. | 13 | 12 | 1 |
| [FilesystemTest](../tests/TestCase/Filesystem/FilesystemTest.php) | The File / Folder shims that replace the removed Cake\Filesystem classes. | 3 | 3 | 0 |
| [CustomDatabaseSessionTest](../tests/TestCase/Http/Session/CustomDatabaseSessionTest.php) | Database session handler (agent_sessions). write() reads the logged-in user out of the | 7 | 7 | 0 |
| [ApiCallerTest](../tests/TestCase/Lib/ApiCallerTest.php) |  | 4 | 4 | 0 |
| [Payment2C2PDbTest](../tests/TestCase/Lib/Payment2C2PDbTest.php) | Payment2C2P paths that read or write orders: payment token request and payment verification. | 5 | 5 | 0 |
| [Payment2C2PTest](../tests/TestCase/Lib/Payment2C2PTest.php) | 2C2P JWT handling (firebase/php-jwt 7), markups, payment inquiry and verification | 12 | 12 | 0 |
| [PaymentPaypalDbTest](../tests/TestCase/Lib/PaymentPaypalDbTest.php) | PayPal paths that read or write orders: checkout link, order lookup, webhook capture. | 8 | 7 | 1 |
| [PaymentPaypalTest](../tests/TestCase/Lib/PaymentPaypalTest.php) | PayPal markups, OAuth token failures and webhook verification (no database; | 8 | 6 | 2 |
| [PaymentStripeTest](../tests/TestCase/Lib/PaymentStripeTest.php) | Stripe markups and webhook signature checks (no network, no database). | 6 | 5 | 1 |
| [PaymentsConfigTest](../tests/TestCase/Lib/PaymentsConfigTest.php) | Payments::config() loads the enabled payment methods, their gateways' settings and the | 4 | 3 | 1 |
| [PaymentsTest](../tests/TestCase/Lib/PaymentsTest.php) | Payments helpers that only read the `Payment.*` config (written by Payments::config(), see PaymentsConfigTest). | 6 | 5 | 1 |
| [QueueSenderTest](../tests/TestCase/Lib/QueueSenderTest.php) |  | 4 | 3 | 1 |
| [TranslatorTest](../tests/TestCase/Lib/TranslatorTest.php) | Translations of attractions, tickets and categories for the session language. | 7 | 7 | 0 |
| [TwoFactorTest](../tests/TestCase/Lib/TwoFactorTest.php) | TOTP codes, replay protection, backup codes and secret encryption. | 8 | 8 | 0 |
| [LoggableBehaviorTest](../tests/TestCase/Model/Behavior/LoggableBehaviorTest.php) | LoggableBehavior writes an audit row to `logs` after each add/edit/delete | 8 | 7 | 1 |
| [EntitiesTest](../tests/TestCase/Model/Entity/EntitiesTest.php) |  | 5 | 5 | 0 |
| [AdminsTableTest](../tests/TestCase/Model/Table/AdminsTableTest.php) |  | 11 | 11 | 0 |
| [AgentUsersTableTest](../tests/TestCase/Model/Table/AgentUsersTableTest.php) |  | 12 | 12 | 0 |
| [AgentsSignupsTableTest](../tests/TestCase/Model/Table/AgentsSignupsTableTest.php) |  | 16 | 15 | 1 |
| [AgentsTableTest](../tests/TestCase/Model/Table/AgentsTableTest.php) |  | 16 | 16 | 0 |
| [AttractionTranslationsTableTest](../tests/TestCase/Model/Table/AttractionTranslationsTableTest.php) |  | 9 | 9 | 0 |
| [AttractionsTableTest](../tests/TestCase/Model/Table/AttractionsTableTest.php) |  | 10 | 10 | 0 |
| [BarcodesTableTest](../tests/TestCase/Model/Table/BarcodesTableTest.php) | The `code` field has a validateUnique rule (queries the table), so every case needs the database. | 13 | 13 | 0 |
| [CategoryTranslationsTableTest](../tests/TestCase/Model/Table/CategoryTranslationsTableTest.php) |  | 8 | 8 | 0 |
| [CreditLogsTableTest](../tests/TestCase/Model/Table/CreditLogsTableTest.php) |  | 10 | 9 | 1 |
| [FavoriteAttractionsTableTest](../tests/TestCase/Model/Table/FavoriteAttractionsTableTest.php) |  | 7 | 7 | 0 |
| [InventoriesTableTest](../tests/TestCase/Model/Table/InventoriesTableTest.php) |  | 4 | 4 | 0 |
| [LastOtpIpsTableTest](../tests/TestCase/Model/Table/LastOtpIpsTableTest.php) | The last 3 IPs an agent user passed the emailed OTP from (trusted IPs). | 4 | 4 | 0 |
| [LoginsTableTest](../tests/TestCase/Model/Table/LoginsTableTest.php) | Login rate limiting: at most Login.max_count_per_max_minutes (5) attempts per IP + email | 11 | 11 | 0 |
| [MerchantsTableTest](../tests/TestCase/Model/Table/MerchantsTableTest.php) |  | 14 | 14 | 0 |
| [OptionsTableTest](../tests/TestCase/Model/Table/OptionsTableTest.php) |  | 1 | 1 | 0 |
| [OrderItemsTableTest](../tests/TestCase/Model/Table/OrderItemsTableTest.php) |  | 10 | 10 | 0 |
| [OrdersTableTest](../tests/TestCase/Model/Table/OrdersTableTest.php) |  | 27 | 23 | 4 |
| [PasswordResetsTableTest](../tests/TestCase/Model/Table/PasswordResetsTableTest.php) |  | 8 | 8 | 0 |
| [PaymentResponsesTableTest](../tests/TestCase/Model/Table/PaymentResponsesTableTest.php) |  | 8 | 8 | 0 |
| [SettlementsTableTest](../tests/TestCase/Model/Table/SettlementsTableTest.php) |  | 9 | 9 | 0 |
| [TicketTranslationsTableTest](../tests/TestCase/Model/Table/TicketTranslationsTableTest.php) |  | 7 | 7 | 0 |
| [TicketsTableTest](../tests/TestCase/Model/Table/TicketsTableTest.php) |  | 23 | 23 | 0 |
| [UsersIpaddressTableTest](../tests/TestCase/Model/Table/UsersIpaddressTableTest.php) |  | 7 | 7 | 0 |
| [VoucherMergingTableTest](../tests/TestCase/Model/Table/VoucherMergingTableTest.php) |  | 1 | 1 | 0 |
| [VouchersTableTest](../tests/TestCase/Model/Table/VouchersTableTest.php) |  | 23 | 18 | 5 |
| [RoutesTest](../tests/TestCase/RoutesTest.php) | Fallback routes are disabled, so config/routes.php is the full list of reachable actions. | 70 | 66 | 4 |
| [CatMailerTest](../tests/TestCase/Util/CatMailerTest.php) |  | 4 | 3 | 1 |
| [ClientIpTest](../tests/TestCase/Util/ClientIpTest.php) |  | 21 | 21 | 0 |
| [CsvTest](../tests/TestCase/Util/CsvTest.php) |  | 2 | 2 | 0 |
| [RounderTest](../tests/TestCase/Util/RounderTest.php) |  | 7 | 7 | 0 |
| [AjaxViewTest](../tests/TestCase/View/AjaxViewTest.php) |  | 1 | 1 | 0 |
| [QuestionGroupHelperTest](../tests/TestCase/View/Helper/QuestionGroupHelperTest.php) | Booking questions (question groups) rendered on the checkout page. | 7 | 7 | 0 |

## Line coverage

To get real line and branch numbers, install a coverage driver for the Laragon PHP 8.5 (NTS, x64, VS17) build:

1. Download `php_pcov-1.0.12-8.5-nts-vs17-x64.zip` from <https://downloads.php.net/~windows/pecl/releases/pcov/1.0.12/> and copy `php_pcov.dll` into `C:\laragon\bin\php\php-8.5.12-nts-Win32-vs17-x64\ext`.
2. Add `extension=pcov` to that PHP's `php.ini` (only on dev machines, never in production).
3. Run:

```bash
php vendor/bin/phpunit --coverage-text --coverage-html tmp/coverage
```

`--coverage-text` prints per-class percentages; open `tmp/coverage/index.html` for line-by-line detail. The `<source>` block in `phpunit.xml.dist` already limits the report to `src/`.
