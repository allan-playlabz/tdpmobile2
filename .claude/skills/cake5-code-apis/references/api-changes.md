# CakePHP 3.x → 5.x API changes (before → after)

Drawn from real 3.x → 5.x upgrades. Pattern ids match `cake5-audit/references/patterns.json`.

## Locating tables
| Before | After | Notes |
|---|---|---|
| `TableRegistry::get('Orders')` | `TableRegistry::getTableLocator()->get('Orders')` | Inside **Table** classes (no LocatorAwareTrait there). |
| `$this->loadModel('Orders'); $this->Orders->find()` | `$this->ordersTable = $this->fetchTable('Orders');` | Controllers/commands/components/cells. A common pattern: declare a property per table and assign it in `initialize()`. |
| `$this->Orders` (auto-loaded default table) | still works for the controller's own table; others need `fetchTable()` | |

## Finding & queries
| Before | After |
|---|---|
| `->order(['created' => 'DESC'])` | `->orderBy(['created' => 'DESC'])` |
| `->group(['agent_id'])` | `->groupBy(['agent_id'])` |
| `->find('all', ['conditions' => [...], 'contain' => [...], 'order' => [...]])` | `->find()->where([...])->contain([...])->orderBy([...])` |
| `->find('list', ['keyField' => 'id', 'valueField' => 'name'])` | `->find('list', keyField: 'id', valueField: 'name')` |
| `->get($id, ['contain' => ['Items']])` | `->get($id, contain: ['Items'])` |
| ❌ `->find('list', limit: 200)` / `order: [...]` / `conditions: [...]` (bad Rector-style conversion) | `->find('list')->limit(200)` / `->orderBy([...])` / `->where([...])` — only finder options are named args |
| `->where(['id IN' => $Table->find('list', valueField: 'id')])` | `->where(['id IN' => $Table->find()->select(['X.id'])])` — a list finder selects two columns (SQLSTATE[21000]) |
| `->hydrate(false)` | `->enableHydration(false)` / `->disableHydration()` |
| `$query->toList()` / `->indexBy()` / `->extract()` / `->combine()` | `$query->all()->toList()` etc. — queries aren't collections any more (`toArray()` still works) |
| `collection($array)` | `new \Cake\Collection\Collection($array)` |
| `->where(['col' => $maybeNull])` | throws on null: `$maybeNull === null ? ['col IS' => null] : ['col' => $maybeNull]` |
| `use Cake\ORM\Query;` type hints | `use Cake\ORM\Query\SelectQuery;` (`Query` is a deprecated alias) |
| `public function beforeFind(Event $event, Query $query, ...) { ...; return $query; }` | modify `$query` and return nothing |
| `newEntity()` (no data) | `newEmptyEntity()` |
| `$table->query()` | `$table->selectQuery()` / `updateQuery()` / `deleteQuery()` / `insertQuery()` |
| `Type::build('json')` / `Cake\Database\Type` | `Cake\Database\TypeFactory::build('json')` |
| `->epilog('FOR UPDATE')` | unchanged — keep inside `transactional()` |

## Tables, entities, behaviours (signatures)
```php
// Table
public function initialize(array $config): void
public function validationDefault(Validator $validator): Validator
public function buildRules(RulesChecker $rules): RulesChecker
public static function defaultConnectionName(): string   // was non-static/untyped
// Entity
protected array $_accessible = [...];
protected array $_hidden = [...];
protected array $_virtual = [...];
// Behavior
public function initialize(array $config): void
// Fixture
public string $table = 'orders';
```
Event callbacks take `Cake\Event\EventInterface $event` (not `Event`). `beforeSave`/`afterSave` may stay untyped, but must not narrow the parent signature.

## Controllers & request/response
| Before | After |
|---|---|
| `$this->request->data` / `->data('x')` | `$this->request->getData()` / `getData('x')` |
| `$this->request->query('x')` | `$this->request->getQuery('x')` |
| `$this->request->params['pass']` / `->param('x')` | `$this->request->getParam('pass')` |
| `$this->request->here()` | `$this->request->getRequestTarget()` |
| `$this->request->getParam('_csrfToken')` | `$this->request->getAttribute('csrfToken')` |
| `$this->request->env('X')` / `input()` | `getEnv('X')` / `getBody()` (or `getData()` with BodyParserMiddleware) |
| `$this->response->body($json); $this->response->type('json'); $this->response->statusCode(200);` | `$this->response = $this->response->withType('application/json')->withStringBody($json)->withStatus(200); return $this->response;` (immutable — assign!) |
| `$this->response->header('X', 'y')` | `$this->response = $this->response->withHeader('X', 'y')` |
| `$this->response->download('f.pdf')` | `$this->response->withDownload('f.pdf')` |
| `beforeFilter(Event $event) { ...; return $this->redirect(...); }` | `beforeFilter(EventInterface $event) { ...; $event->setResult($this->redirect(...)); $event->stopPropagation(); return; }` |
| `loadComponent('RequestHandler')` | removed — `$this->request->is('json')`, `addViewClasses([JsonView::class])` |
| `$this->Auth->user()` / `->user('id')` | `$this->request->getAttribute('identity')` — see `cake5-auth` (`getAuthUser()` helper) |
| `$this->Auth->allow(['x'])` | `$this->Authentication->addUnauthenticatedActions(['x'])` |
| `Cake\Auth\DefaultPasswordHasher` | `Authentication\PasswordHasher\DefaultPasswordHasher` |
| `$this->autoRender = false` | still works; `$this->disableAutoRender()` preferred |

## Pagination
| Before | After |
|---|---|
| `$this->paginate = ['conditions' => [...], 'contain' => [...], 'order' => [...], 'limit' => 50]; $this->paginate($this->Orders)` | `$this->paginate = ['order' => [...], 'limit' => 50]; $this->paginate($this->Orders->find()->where([...])->contain([...]))` |
| `'sortWhitelist' => [...]` | `'sortableFields' => [...]` (use the same keys the template sorts by, e.g. `Orders.created`) |
| manual `->limit()->page()->all()` + PaginatorHelper | `paginate($query)` — the helper needs a `PaginatedInterface` result |
| `loadInto()` on a paginated result | `contain()` on the query before paginating |
| `$this->Paginator->counter(['format' => '...'])` | `$this->Paginator->counter('Page {{page}} of {{pages}}')` |
| — | `$this->Paginator->setPaginated($results)` only when a view has several paginated results |

## Templates & helpers
| Before | After |
|---|---|
| `*.ctp` | `*.php` (moved by `cake5-structure`) |
| `$this->Form->create('')` | `$this->Form->create(null)` |
| `$this->Form->input('x')` | `$this->Form->control('x')` |
| `Form->button($t, ['escape' => false])` | `['escapeTitle' => false]` |
| `$this->Html->script('x', ['inline' => false])` | `['block' => true]` |
| `$this->Number->currency($maybeNull)` | `$this->Number->currency($v ?? 0)` — strict types reject null |
| `$this->Html->link($maybeNull, ...)` | guard null titles: `h($v ?? '')` |

## Dates (immutable; Chronos 3)
| Before | After |
|---|---|
| `use Cake\I18n\Time;` / `FrozenTime` | `use Cake\I18n\DateTime;` |
| `FrozenDate` / `Date` (mutable) | `Cake\I18n\Date` (immutable) |
| `$d->addDays(1);` (mutable in 3.x) | `$d = $d->addDays(1);` |
| `$d->modify('+1 week');` | `$d = $d->modify('+1 week');` |
| `$d->subDay()` / `addMonth()` | `$d->subDays(1)` / `addMonths(1)` |
| `$x instanceof Time` (meaning datetime) | `$x instanceof \DateTimeInterface` — `Cake\I18n\Time` is now time-of-day |
| `Time::setToStringFormat(...)` | `DateTime::setToStringFormat(...)` |
| `Type::build('datetime')->useImmutable()` | remove — always immutable |

## Mail
```php
// before
$email = new Email('default');
$email->setTemplate('order_failed')->setEmailFormat('html')->setViewVars(['data' => $d])
      ->setTo($to)->setSubject($s)->send();
// after
$mailer = new Mailer('default');
$mailer->viewBuilder()->setTemplate('order_failed');
$mailer->setEmailFormat('html')->setViewVars(['data' => $d])->setTo($to)->setSubject($s);
$mailer->deliver();
```
Templates: `templates/email/html/order_failed.php`. `Email::setConfig` → `Mailer::setConfig` in bootstrap.

## Filesystem, logging, misc
| Before | After |
|---|---|
| `use Cake\Filesystem\File;` / `Folder` | native PHP, or `App\Filesystem\File/Folder` shims (see gotchas) |
| `Zend\Diactoros\UploadedFile` | `Laminas\Diactoros\UploadedFile` |
| Custom log engine `log($level, $message, array $context = [])` returning bool | `log($level, Stringable\|string $message, array $context = []): void` |
| `env('X')` in namespaced code | `use function Cake\Core\env;` |
| `__('x')` in namespaced code | `use function Cake\I18n\__;` (or load global functions in bootstrap) |
