<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AgentsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AgentsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    protected array $fixtures = self::USER_FIXTURES;

    private AgentsTable $Agents;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Agents = $this->getTableLocator()->get('Agents');
    }

    protected function validData(): array
    {
        return [
            'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com', 'company_name' => 'ACME',
            'general_category' => 'Travel', 'country' => 'Singapore', 'gender' => 'female',
        ];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'valid optional fields' => [['password' => '', 'credits' => '10.50', 'enable_generation_confirmation' => '1', 'contact_no' => ''], []],
            'missing first_name' => [['first_name' => self::UNSET], ['first_name' => ['_required']]],
            'long first_name' => [['first_name' => str_repeat('a', 51)], ['first_name' => ['maxLength']]],
            'empty last_name' => [['last_name' => ''], ['last_name' => ['_empty']]],
            'invalid email' => [['email' => 'nope'], ['email' => ['email']]],
            'missing company_name' => [['company_name' => self::UNSET], ['company_name' => ['_required']]],
            'long company_name' => [['company_name' => str_repeat('a', 101)], ['company_name' => ['maxLength']]],
            'missing general_category' => [['general_category' => self::UNSET], ['general_category' => ['_required']]],
            'missing country' => [['country' => self::UNSET], ['country' => ['_required']]],
            'missing gender' => [['gender' => self::UNSET], ['gender' => ['_required']]],
            'long password' => [['password' => str_repeat('a', 65)], ['password' => ['maxLength']]],
            'non-numeric credits' => [['credits' => 'abc'], ['credits' => ['numeric']]],
            'non-boolean flag' => [['enable_generation_confirmation' => 'maybe'], ['enable_generation_confirmation' => ['boolean']]],
            'long contact_no' => [['contact_no' => str_repeat('1', 51)], ['contact_no' => ['maxLength']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->Agents, $changes, $expected);
    }

    public function testRulesUniqueEmail(): void
    {
        $this->createAgent(self::AGENT_A, ['email' => 'taken@example.com']);

        $agent = $this->Agents->newEntity(['email' => 'free@example.com'] + $this->validData());
        $this->assertTrue($this->Agents->checkRules($agent));

        $duplicate = $this->Agents->newEntity(['email' => 'taken@example.com'] + $this->validData());
        $this->assertFalse($this->Agents->checkRules($duplicate));
        $this->assertArrayHasKey('_isUnique', $duplicate->getError('email'));
    }
}
