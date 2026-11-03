<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\InventoriesTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class InventoriesTableTest extends TestCase
{
    use AppDataTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000001301';
    private const TICKET = '00000000-0000-4000-8000-000000001302';
    private const OTHER_TICKET = '00000000-0000-4000-8000-000000001303';
    private const ORDER = '00000000-0000-4000-8000-000000001304';

    protected array $fixtures = [...self::USER_FIXTURES, ...self::CATALOGUE_FIXTURES, 'app.Inventories'];

    private InventoriesTable $Inventories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Inventories = $this->getTableLocator()->get('Inventories');
        $this->createAttraction(self::ATTRACTION);
        $this->createTicket(self::TICKET, self::ATTRACTION);
        $this->createTicket(self::OTHER_TICKET, self::ATTRACTION);
    }

    private function addInventory(string $ticketId, ?int $currentQuantity, ?int $stock, ?int $sold, string $created): void
    {
        $this->insertRow('inventories', [
            'ticket_id' => $ticketId, 'user_id' => self::MASTER_A, 'type' => 'upload', 'quantity' => 10,
            'current_quantity' => $currentQuantity, 'stock' => $stock, 'sold' => $sold, 'created' => new DateTime($created),
        ]);
    }

    private function latestRow(): array
    {
        return $this->fetchRow(
            'SELECT type, quantity, current_quantity, sold, stock, LOWER(HEX(order_id)) AS order_id, LOWER(HEX(user_id)) AS user_id
             FROM inventories WHERE ticket_id = ' . self::uuidSql() . ' ORDER BY id DESC LIMIT 1',
            [self::TICKET],
        );
    }

    private static function hex(string $uuid): string
    {
        return str_replace('-', '', $uuid);
    }

    public function testPurchaseContinuesFromTheLatestInventory(): void
    {
        $this->addInventory(self::TICKET, 20, 15, 5, '-2 days');
        $this->addInventory(self::TICKET, 20, 12, 8, '-1 day');
        $this->addInventory(self::OTHER_TICKET, 99, 99, 0, '-1 hour');

        $this->assertTrue($this->Inventories->purchase(self::TICKET, 3, self::MASTER_A, self::ORDER));

        $this->assertEquals([
            'type' => 'sold', 'quantity' => -3, 'current_quantity' => 20, 'sold' => 11, 'stock' => 9,
            'order_id' => self::hex(self::ORDER), 'user_id' => self::hex(self::MASTER_A),
        ], $this->latestRow());
    }

    public function testPurchaseWithUnlimitedInventoryKeepsStockNull(): void
    {
        $this->addInventory(self::TICKET, null, null, 4, '-1 day');

        $this->assertTrue($this->Inventories->purchase(self::TICKET, 2, self::MASTER_A, self::ORDER));

        $row = $this->latestRow();
        $this->assertEquals(-2, $row['quantity']);
        $this->assertEquals(6, $row['sold']);
        $this->assertNull($row['stock']);
        $this->assertNull($row['current_quantity']);
    }

    public function testFirstPurchaseWithoutInventory(): void
    {
        $this->assertTrue($this->Inventories->purchase(self::TICKET, 1, self::MASTER_A, self::ORDER));

        $row = $this->latestRow();
        $this->assertSame('sold', $row['type']);
        $this->assertEquals(-1, $row['quantity']);
        $this->assertNull($row['sold']);
        $this->assertNull($row['stock']);
        $this->assertSame(1, $this->countRows('inventories'));
    }

    public function testPurchaseUnknownTicketFails(): void
    {
        $this->assertFalse($this->Inventories->purchase('00000000-0000-4000-8000-0000000000ff', 1, self::MASTER_A, self::ORDER));
        $this->assertSame(0, $this->countRows('inventories'));
    }
}
