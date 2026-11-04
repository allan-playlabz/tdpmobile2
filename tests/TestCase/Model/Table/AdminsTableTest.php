<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AdminsTable;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AdminsTableTest extends TestCase
{
    use ValidationTestTrait;

    protected array $fixtures = ['app.Admins'];

    private AdminsTable $Admins;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Admins = $this->getTableLocator()->get('Admins');
    }

    protected function validData(): array
    {
        return ['first_name' => 'Ann', 'last_name' => 'Admin', 'email' => 'ann@example.com', 'password' => 'Secret#1'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'valid optional fields' => [['otp' => '123456', 'otp_created' => '2026-01-01 10:00:00', 'role' => 'master', 'status' => ''], []],
            'missing first_name' => [['first_name' => self::UNSET], ['first_name' => ['_required']]],
            'long first_name' => [['first_name' => str_repeat('a', 46)], ['first_name' => ['maxLength']]],
            'long last_name' => [['last_name' => str_repeat('a', 46)], ['last_name' => ['maxLength']]],
            'invalid email' => [['email' => 'nope'], ['email' => ['email']]],
            'missing password' => [['password' => self::UNSET], ['password' => ['_required']]],
            'empty password' => [['password' => ''], ['password' => ['_empty']]],
            'long otp' => [['otp' => '1234567'], ['otp' => ['maxLength']]],
            'invalid otp_created' => [['otp_created' => 'yesterday-ish'], ['otp_created' => ['dateTime']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->Admins, $changes, $expected);
    }

    public function testRulesUniqueEmail(): void
    {
        $this->Admins->getConnection()->insert('admins', [
            'id' => '00000000-0000-4000-8000-000000000601', 'name' => 'Ann', 'email' => 'taken@example.com', 'password' => 'x',
        ], ['id' => 'binaryuuid']);

        $this->assertTrue($this->Admins->checkRules($this->Admins->newEntity(['email' => 'free@example.com'] + $this->validData())));

        $duplicate = $this->Admins->newEntity(['email' => 'taken@example.com'] + $this->validData());
        $this->assertFalse($this->Admins->checkRules($duplicate));
        $this->assertArrayHasKey('_isUnique', $duplicate->getError('email'));
    }
}
