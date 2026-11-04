<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SettlementsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SettlementsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000001001';
    private const TICKET = '00000000-0000-4000-8000-000000001002';

    protected array $fixtures = [...self::CATALOGUE_FIXTURES];

    private SettlementsTable $Settlements;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Settlements = $this->getTableLocator()->get('Settlements');
    }

    protected function validData(): array
    {
        return ['settlement_group' => 1, 'rate' => '25.00', 'rebate' => '0'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'missing settlement_group' => [['settlement_group' => self::UNSET], ['settlement_group' => ['_required']]],
            'empty settlement_group' => [['settlement_group' => ''], ['settlement_group' => ['_empty']]],
            'missing rate' => [['rate' => self::UNSET], ['rate' => ['_required']]],
            'non-numeric rate' => [['rate' => 'cheap'], ['rate' => ['numeric']]],
            'missing rebate' => [['rebate' => self::UNSET], ['rebate' => ['_required']]],
            'non-numeric rebate' => [['rebate' => 'x'], ['rebate' => ['numeric']]],
            'non-integer id' => [['id' => 'x'], ['id' => ['integer']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->Settlements, $changes, $expected);
    }

    public function testRulesTicketMustExist(): void
    {
        $this->createAttraction(self::ATTRACTION);
        $this->createTicket(self::TICKET, self::ATTRACTION);

        $this->assertTrue($this->Settlements->checkRules($this->Settlements->newEntity(['ticket_id' => self::TICKET] + $this->validData())));

        $orphan = $this->Settlements->newEntity(['ticket_id' => '00000000-0000-4000-8000-0000000000ff'] + $this->validData());
        $this->assertFalse($this->Settlements->checkRules($orphan));
        $this->assertArrayHasKey('_existsIn', $orphan->getError('ticket_id'));
    }
}
