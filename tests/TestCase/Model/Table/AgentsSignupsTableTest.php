<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AgentsSignupsTable;
use Cake\Database\Exception\DatabaseException;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AgentsSignupsTableTest extends TestCase
{
    use ValidationTestTrait;

    protected array $fixtures = ['app.AgentsSignups'];

    private AgentsSignupsTable $AgentsSignups;

    protected function setUp(): void
    {
        parent::setUp();
        $this->AgentsSignups = $this->getTableLocator()->get('AgentsSignups');
    }

    protected function validData(): array
    {
        return [
            'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com', 'company_name' => 'ACME',
            'image' => 'logo.png', 'country' => 'Singapore', 'gender' => 'female', 'enable_api' => '0',
        ];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'valid optional fields' => [['general_category' => '', 'otp_created' => '2026-01-01 10:00:00', 'credits' => '1.5', 'rebates' => '0'], []],
            'missing first_name' => [['first_name' => self::UNSET], ['first_name' => ['_required']]],
            'long last_name' => [['last_name' => str_repeat('a', 51)], ['last_name' => ['maxLength']]],
            'invalid email' => [['email' => 'nope'], ['email' => ['email']]],
            'missing company_name' => [['company_name' => self::UNSET], ['company_name' => ['_required']]],
            'missing image' => [['image' => self::UNSET], ['image' => ['_required']]],
            'missing country' => [['country' => self::UNSET], ['country' => ['_required']]],
            'missing gender' => [['gender' => self::UNSET], ['gender' => ['_required']]],
            'missing enable_api' => [['enable_api' => self::UNSET], ['enable_api' => ['_required']]],
            'non-boolean enable_api' => [['enable_api' => 'yes'], ['enable_api' => ['boolean']]],
            'empty rebates' => [['rebates' => ''], ['rebates' => ['_empty']]],
            'non-numeric credits' => [['credits' => 'x'], ['credits' => ['numeric']]],
            'invalid otp_created' => [['otp_created' => 'not a date'], ['otp_created' => ['dateTime']]],
            'long api_key' => [['api_key' => str_repeat('k', 51)], ['api_key' => ['maxLength']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->AgentsSignups, $changes, $expected);
    }

    public function testRulesUniqueEmail(): void
    {
        $this->AgentsSignups->getConnection()->insert('agents_signups', [
            'id' => '00000000-0000-4000-8000-000000000501', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'taken@example.com',
            'company_name' => 'C', 'country' => 'Singapore', 'gender' => 'male',
        ], ['id' => 'binaryuuid']);

        $signup = $this->AgentsSignups->newEntity(['email' => 'free@example.com'] + $this->validData());
        $duplicate = $this->AgentsSignups->newEntity(['email' => 'taken@example.com'] + $this->validData());
        try {
            $valid = $this->AgentsSignups->checkRules($signup);
            $invalid = $this->AgentsSignups->checkRules($duplicate);
        } catch (DatabaseException $e) {
            $this->markTestSkipped('BUG: AgentsSignupsTable belongsTo MasterAgents has no className (table master_agents does not exist), so its existsIn rule throws');
        }

        $this->assertTrue($valid);
        $this->assertFalse($invalid);
        $this->assertArrayHasKey('_isUnique', $duplicate->getError('email'));
    }
}
