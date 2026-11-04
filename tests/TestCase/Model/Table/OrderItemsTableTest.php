<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\OrderItemsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class OrderItemsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000000d01';
    private const TICKET = '00000000-0000-4000-8000-000000000d02';
    private const ORDER = '00000000-0000-4000-8000-000000000d03';

    protected array $fixtures = [...self::USER_FIXTURES, ...self::CATALOGUE_FIXTURES, ...self::ORDER_FIXTURES];

    private OrderItemsTable $OrderItems;

    protected function setUp(): void
    {
        parent::setUp();
        $this->OrderItems = $this->getTableLocator()->get('OrderItems');
    }

    protected function validData(): array
    {
        return ['qty' => 2, 'price' => '25.00', 'total' => '50.00'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'valid visit date' => [['date_of_visit' => '2027-01-01', 'time_of_visit' => '10:00', 'order_data' => '{}'], []],
            'missing qty' => [['qty' => self::UNSET], ['qty' => ['_required']]],
            'empty qty' => [['qty' => ''], ['qty' => ['_empty']]],
            'missing price' => [['price' => self::UNSET], ['price' => ['_required']]],
            'non-numeric price' => [['price' => 'free'], ['price' => ['numeric']]],
            'missing total' => [['total' => self::UNSET], ['total' => ['_required']]],
            'non-numeric total' => [['total' => 'x'], ['total' => ['numeric']]],
            'invalid date_of_visit' => [['date_of_visit' => '2027-13-45'], ['date_of_visit' => ['date']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->OrderItems, $changes, $expected);
    }

    public function testRulesOrderAndTicketMustExist(): void
    {
        $this->createAgentsAndUsers();
        $this->createAttraction(self::ATTRACTION);
        $this->createTicket(self::TICKET, self::ATTRACTION);
        $this->createOrder(self::ORDER, self::MASTER_A, self::AGENT_A);
        $missing = '00000000-0000-4000-8000-0000000000ff';

        $item = $this->OrderItems->newEntity(['order_id' => self::ORDER, 'ticket_id' => self::TICKET] + $this->validData());
        $this->assertTrue($this->OrderItems->checkRules($item));

        $item = $this->OrderItems->newEntity(['order_id' => $missing, 'ticket_id' => self::TICKET] + $this->validData());
        $this->assertFalse($this->OrderItems->checkRules($item));
        $this->assertArrayHasKey('_existsIn', $item->getError('order_id'));

        $item = $this->OrderItems->newEntity(['order_id' => self::ORDER, 'ticket_id' => $missing] + $this->validData());
        $this->assertFalse($this->OrderItems->checkRules($item));
        $this->assertArrayHasKey('_existsIn', $item->getError('ticket_id'));
    }
}
