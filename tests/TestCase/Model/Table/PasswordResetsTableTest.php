<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PasswordResetsTable;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PasswordResetsTableTest extends TestCase
{
    use ValidationTestTrait;

    private PasswordResetsTable $PasswordResets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->PasswordResets = $this->getTableLocator()->get('PasswordResets');
    }

    protected function validData(): array
    {
        return ['email' => 'jane@example.com', 'reset_code' => str_repeat('c', 64), 'type' => 'agent'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'missing email' => [['email' => self::UNSET], ['email' => ['_required']]],
            'invalid email' => [['email' => 'nope'], ['email' => ['email']]],
            'missing reset_code' => [['reset_code' => self::UNSET], ['reset_code' => ['_required']]],
            'empty reset_code' => [['reset_code' => ''], ['reset_code' => ['_empty']]],
            'long reset_code' => [['reset_code' => str_repeat('c', 256)], ['reset_code' => ['maxLength']]],
            'missing type' => [['type' => self::UNSET], ['type' => ['_required']]],
            'non-integer id' => [['id' => 'abc'], ['id' => ['integer']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->PasswordResets, $changes, $expected);
    }
}
