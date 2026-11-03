<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersIpaddressTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UsersIpaddressTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    protected array $fixtures = self::USER_FIXTURES;

    private UsersIpaddressTable $UsersIpaddress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->UsersIpaddress = $this->getTableLocator()->get('UsersIpaddress');
    }

    protected function validData(): array
    {
        return ['ip_address' => '10.0.0.1', 'type' => 'agent'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'missing ip_address' => [['ip_address' => self::UNSET], ['ip_address' => ['_required']]],
            'long ip_address' => [['ip_address' => str_repeat('1', 65)], ['ip_address' => ['maxLength']]],
            'missing type' => [['type' => self::UNSET], ['type' => ['_required']]],
            'long type' => [['type' => str_repeat('t', 46)], ['type' => ['maxLength']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->UsersIpaddress, $changes, $expected);
    }

    public function testAddOrUpdateCreatesThenTouchesTheSameRow(): void
    {
        $data = ['ip_address' => '10.0.0.1', 'user_id' => self::MASTER_A, 'type' => 'agent', 'ignored' => 'x'];

        $created = $this->UsersIpaddress->addOrUpdate($data);

        $this->assertFalse($created->isNew());
        $this->assertSame(1, $this->countRows('users_ipaddress'));

        $this->db()->execute('UPDATE users_ipaddress SET modified = ?', [(new DateTime('-10 days'))->format('Y-m-d H:i:s')]);
        $updated = $this->UsersIpaddress->addOrUpdate($data);

        $this->assertSame($created->id, $updated->id);
        $this->assertSame(1, $this->countRows('users_ipaddress'));
        $modified = $this->fetchRow('SELECT modified FROM users_ipaddress')['modified'];
        $this->assertGreaterThan((new DateTime('-1 day'))->format('Y-m-d H:i:s'), $modified);
    }

    public function testAddOrUpdateKeepsSeparateRowsPerIpUserAndType(): void
    {
        $this->UsersIpaddress->addOrUpdate(['ip_address' => '10.0.0.1', 'user_id' => self::MASTER_A, 'type' => 'agent']);
        $this->UsersIpaddress->addOrUpdate(['ip_address' => '10.0.0.2', 'user_id' => self::MASTER_A, 'type' => 'agent']);
        $this->UsersIpaddress->addOrUpdate(['ip_address' => '10.0.0.1', 'user_id' => self::NORMAL_A, 'type' => 'agent']);
        $this->UsersIpaddress->addOrUpdate(['ip_address' => '10.0.0.1', 'user_id' => self::MASTER_A, 'type' => 'admin']);

        $this->assertSame(4, $this->countRows('users_ipaddress'));
    }
}
