<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AgentUsersTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AgentUsersTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    protected array $fixtures = self::USER_FIXTURES;

    private AgentUsersTable $AgentUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->AgentUsers = $this->getTableLocator()->get('AgentUsers');
    }

    protected function validData(): array
    {
        return ['role' => 'normal', 'name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'Secret#1', 'status' => 'active'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'missing name' => [['name' => self::UNSET], ['name' => ['_required']]],
            'empty name' => [['name' => ''], ['name' => ['_empty']]],
            'long name' => [['name' => str_repeat('a', 101)], ['name' => ['maxLength']]],
            'missing email' => [['email' => self::UNSET], ['email' => ['_required']]],
            'invalid email' => [['email' => 'not-an-email'], ['email' => ['email']]],
            'missing password' => [['password' => self::UNSET], ['password' => ['_required']]],
            'long password' => [['password' => str_repeat('a', 65)], ['password' => ['maxLength']]],
            'empty role' => [['role' => ''], ['role' => ['_empty']]],
            'empty status' => [['status' => ''], ['status' => ['_empty']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->AgentUsers, $changes, $expected);
    }

    public function testUpdateDoesNotRequireFields(): void
    {
        $this->assertSame([], $this->validationErrors($this->AgentUsers, ['name' => 'New name'], false));
    }

    public function testRulesUniqueEmailAndExistingAgent(): void
    {
        $this->createAgentsAndUsers();

        $user = $this->AgentUsers->newEntity($this->validData());
        $user->agent_id = self::AGENT_A;
        $this->assertTrue($this->AgentUsers->checkRules($user));

        $duplicate = $this->AgentUsers->newEntity(['email' => 'master.a@example.com'] + $this->validData());
        $duplicate->agent_id = self::AGENT_A;
        $this->assertFalse($this->AgentUsers->checkRules($duplicate));
        $this->assertArrayHasKey('_isUnique', $duplicate->getError('email'));

        $orphan = $this->AgentUsers->newEntity($this->validData());
        $orphan->agent_id = '00000000-0000-4000-8000-0000000000ff';
        $this->assertFalse($this->AgentUsers->checkRules($orphan));
        $this->assertArrayHasKey('_existsIn', $orphan->getError('agent_id'));
    }
}
