<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BarcodesTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The `code` field has a validateUnique rule (queries the table), so every case needs the database.
 */
class BarcodesTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    protected array $fixtures = ['app.Barcodes', 'app.Agents'];

    private BarcodesTable $Barcodes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Barcodes = $this->getTableLocator()->get('Barcodes');
        $this->insertRow('barcodes', [
            'id' => '00000000-0000-4000-8000-000000000b01', 'code' => 'TAKEN-001', 'ticket_sku_id' => 'SKU-1',
            'validity_start_date' => '2026-01-01', 'validity_end_date' => '2027-01-01', 'status' => 'new',
        ]);
    }

    protected function validData(): array
    {
        return ['code' => 'NEW-001', 'validity_start_date' => '2026-01-01', 'validity_end_date' => '2027-01-01', 'status' => 'new'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'valid optional fields' => [['package_code' => 'PKG', 'sold' => '1', 'redeemed_datetime' => '2026-02-01 10:00:00', 'custom_data' => '{}'], []],
            'missing code' => [['code' => self::UNSET], ['code' => ['_required']]],
            'empty code' => [['code' => ''], ['code' => ['_empty']]],
            'long code' => [['code' => str_repeat('c', 256)], ['code' => ['maxLength']]],
            'duplicate code' => [['code' => 'TAKEN-001'], ['code' => ['unique']]],
            'missing validity_start_date' => [['validity_start_date' => self::UNSET], ['validity_start_date' => ['_required']]],
            'invalid validity_end_date' => [['validity_end_date' => '2027-02-30'], ['validity_end_date' => ['date']]],
            'empty status' => [['status' => ''], ['status' => ['_empty']]],
            'long package_code' => [['package_code' => str_repeat('p', 101)], ['package_code' => ['maxLength']]],
            'long custom_data' => [['custom_data' => str_repeat('d', 501)], ['custom_data' => ['maxLength']]],
            'invalid redeemed_datetime' => [['redeemed_datetime' => 'later'], ['redeemed_datetime' => ['dateTime']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->Barcodes, $changes, $expected);
    }

    public function testRulesUniqueCode(): void
    {
        $this->assertTrue($this->Barcodes->checkRules($this->Barcodes->newEntity($this->validData())));

        $duplicate = $this->Barcodes->newEntity(['code' => 'TAKEN-001'] + $this->validData(), ['validate' => false]);
        $this->assertFalse($this->Barcodes->checkRules($duplicate));
        $this->assertArrayHasKey('_isUnique', $duplicate->getError('code'));
    }
}
