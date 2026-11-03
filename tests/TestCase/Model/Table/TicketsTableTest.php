<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TicketsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TicketsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000000e01';
    private const TICKET = '00000000-0000-4000-8000-000000000e02';
    private const RETURN_TICKET = '00000000-0000-4000-8000-000000000e03';
    private const OTHER_TICKET = '00000000-0000-4000-8000-000000000e04';

    protected array $fixtures = [...self::CATALOGUE_FIXTURES];

    private TicketsTable $Tickets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Tickets = $this->getTableLocator()->get('Tickets');
    }

    protected function validData(): array
    {
        return [
            'name' => 'Adult', 'description' => 'Desc', 'terms_and_conditions' => 'T&C',
            'publish_start_date' => '2026-01-01 00:00:00', 'publish_end_date' => '2027-01-01 00:00:00',
            'redemption_start_date' => '2026-01-01 00:00:00', 'redemption_end_date' => '2027-01-01 00:00:00',
        ];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'valid optional fields' => [['price' => '12.50', 'fixed_date_ticket' => '1', 'instant_confirmation' => '0', 'type' => 'normal'], []],
            'missing name' => [['name' => self::UNSET], ['name' => ['_required']]],
            'long name' => [['name' => str_repeat('a', 101)], ['name' => ['maxLength']]],
            'missing description' => [['description' => self::UNSET], ['description' => ['_required']]],
            'missing terms' => [['terms_and_conditions' => self::UNSET], ['terms_and_conditions' => ['_required']]],
            'empty sequence' => [['sequence' => ''], ['sequence' => ['_empty']]],
            'missing publish_start_date' => [['publish_start_date' => self::UNSET], ['publish_start_date' => ['_required']]],
            'invalid publish_end_date' => [['publish_end_date' => 'soon'], ['publish_end_date' => ['dateTime']]],
            'empty redemption_start_date' => [['redemption_start_date' => ''], ['redemption_start_date' => ['_empty']]],
            'missing redemption_end_date' => [['redemption_end_date' => self::UNSET], ['redemption_end_date' => ['_required']]],
            'non-numeric price' => [['price' => 'abc'], ['price' => ['numeric']]],
            'non-boolean instant_confirmation' => [['instant_confirmation' => 'perhaps'], ['instant_confirmation' => ['boolean']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->Tickets, $changes, $expected);
    }

    public function testRulesAttractionMustExist(): void
    {
        $this->createAttraction(self::ATTRACTION);

        $ticket = $this->Tickets->newEntity(['attraction_id' => self::ATTRACTION] + $this->validData());
        $this->assertTrue($this->Tickets->checkRules($ticket));

        $ticket = $this->Tickets->newEntity(['attraction_id' => '00000000-0000-4000-8000-0000000000ff'] + $this->validData());
        $this->assertFalse($this->Tickets->checkRules($ticket));
        $this->assertArrayHasKey('_existsIn', $ticket->getError('attraction_id'));
    }

    // consume()

    private function ticketRow(): array
    {
        return $this->fetchRow('SELECT stock, quantity_sold FROM tickets WHERE id = ' . self::uuidSql(), [self::TICKET]);
    }

    private function createFixeddate(?string $time, ?int $stock, int $sold = 0): void
    {
        $this->insertRow('fixeddates', [
            'ticket_id' => self::TICKET, 'date' => '2027-03-01', 'time' => $time, 'rate' => 20, 'stock' => $stock, 'quantity_sold' => $sold,
            'created' => new DateTime('-1 day'), 'modified' => new DateTime('-1 day'),
        ]);
    }

    private function fixeddateRow(): array
    {
        return $this->fetchRow('SELECT stock, quantity_sold FROM fixeddates WHERE ticket_id = ' . self::uuidSql(), [self::TICKET]);
    }

    public function testConsumeDecrementsStock(): void
    {
        $this->createAttraction(self::ATTRACTION);
        $this->createTicket(self::TICKET, self::ATTRACTION, ['stock' => 10, 'quantity_sold' => 1]);

        $this->assertTrue($this->Tickets->consume(self::TICKET, 'generated', 'settlement', 10, 3));

        $this->assertEquals(['stock' => 7, 'quantity_sold' => 4], $this->ticketRow());
    }

    public function testConsumeUnlimitedStock(): void
    {
        $this->createAttraction(self::ATTRACTION);
        $this->createTicket(self::TICKET, self::ATTRACTION, ['stock' => null, 'quantity_sold' => 0]);

        $this->assertTrue($this->Tickets->consume(self::TICKET, 'generated', 'settlement', null, 5));

        $this->assertEquals(['stock' => null, 'quantity_sold' => 5], $this->ticketRow());
    }

    public function testConsumeRejectsInsufficientStock(): void
    {
        $this->createAttraction(self::ATTRACTION);
        $this->createTicket(self::TICKET, self::ATTRACTION, ['stock' => 2, 'quantity_sold' => 0]);

        $this->assertSame('Not enough ticket stock', $this->Tickets->consume(self::TICKET, 'generated', 'settlement', 2, 3));
        $this->assertEquals(['stock' => 2, 'quantity_sold' => 0], $this->ticketRow());
    }

    public function testConsumeUnknownTicket(): void
    {
        $this->assertSame('Not enough ticket stock', $this->Tickets->consume(self::TICKET, 'generated', 'settlement', 2, 1));
    }

    public function testConsumeFixedDate(): void
    {
        $this->createAttraction(self::ATTRACTION, ['pricing_type' => 'fdate']);
        $this->createTicket(self::TICKET, self::ATTRACTION, ['pricing_type' => 'fdate', 'stock' => null]);
        $this->createFixeddate(null, 5, 1);

        $this->assertTrue($this->Tickets->consume(self::TICKET, 'generated', 'fdate', null, 2, '2027-03-01'));

        $this->assertEquals(['stock' => 3, 'quantity_sold' => 3], $this->fixeddateRow());
        $this->assertEquals(['stock' => null, 'quantity_sold' => 2], $this->ticketRow());
    }

    public function testConsumeFixedDateRejectsInsufficientDateStock(): void
    {
        $this->createAttraction(self::ATTRACTION, ['pricing_type' => 'fdate']);
        $this->createTicket(self::TICKET, self::ATTRACTION, ['pricing_type' => 'fdate', 'stock' => null]);
        $this->createFixeddate(null, 1);

        $this->assertSame('Not enough ticket stock', $this->Tickets->consume(self::TICKET, 'generated', 'fdate', null, 2, '2027-03-01'));
        $this->assertEquals(['stock' => 1, 'quantity_sold' => 0], $this->fixeddateRow());
        $this->assertEquals(['stock' => null, 'quantity_sold' => 0], $this->ticketRow());
    }

    public function testConsumeFixedDateTime(): void
    {
        $this->createAttraction(self::ATTRACTION, ['pricing_type' => 'fdatetime']);
        $this->createTicket(self::TICKET, self::ATTRACTION, ['pricing_type' => 'fdatetime', 'stock' => null]);
        $this->createFixeddate('10:00:00', null, 4);

        $this->assertTrue($this->Tickets->consume(self::TICKET, 'generated', 'fdatetime', null, 2, '2027-03-01', '10:00:00'));

        $this->assertEquals(['stock' => null, 'quantity_sold' => 6], $this->fixeddateRow());
        $this->assertEquals(['stock' => null, 'quantity_sold' => 2], $this->ticketRow());
    }

    // Return tickets (two-way trips)

    private function createTwoWayTickets(): void
    {
        $this->createAttraction(self::ATTRACTION, ['pricing_type' => 'two_way_fdatetime']);
        $this->createTicket(self::TICKET, self::ATTRACTION, ['pricing_type' => 'two_way_fdatetime']);
        $this->createTicket(self::RETURN_TICKET, self::ATTRACTION, ['pricing_type' => 'two_way_fdatetime']);
        $this->createTicket(self::OTHER_TICKET, self::ATTRACTION, ['pricing_type' => 'two_way_fdatetime']);
        $this->insertRow('ticket_trip_types', ['ticket_id' => self::TICKET, 'type' => 'depart', 'linked_ticket_id' => null]);
        $this->insertRow('ticket_trip_types', ['ticket_id' => self::RETURN_TICKET, 'type' => 'return', 'linked_ticket_id' => self::TICKET]);
    }

    public function testGetReturnTicket(): void
    {
        $this->createTwoWayTickets();

        $return = $this->Tickets->getReturnTicket(self::TICKET);

        $this->assertNotNull($return);
        $this->assertSame(self::RETURN_TICKET, $return->id);
        $this->assertSame('return', $return->ticket_trip_type->type);
        $this->assertNull($this->Tickets->getReturnTicket(self::RETURN_TICKET));
        $this->assertNull($this->Tickets->getReturnTicket(self::OTHER_TICKET));
    }

    public function testIsValidReturnTicket(): void
    {
        $this->createTwoWayTickets();

        $this->assertTrue($this->Tickets->isValidReturnTicket(self::TICKET, self::RETURN_TICKET));
        $this->assertFalse($this->Tickets->isValidReturnTicket(self::TICKET, self::OTHER_TICKET));
        $this->assertFalse($this->Tickets->isValidReturnTicket(self::RETURN_TICKET, self::TICKET));
        $this->assertFalse($this->Tickets->isValidReturnTicket(self::OTHER_TICKET, self::RETURN_TICKET));
    }
}
