<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MerchantsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MerchantsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    protected array $fixtures = ['app.Merchants', 'app.Agents'];

    private MerchantsTable $Merchants;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Merchants = $this->getTableLocator()->get('Merchants');
    }

    protected function validData(): array
    {
        return [
            'name' => 'Zoo Co', 'email' => 'zoo@example.com', 'business_description' => 'Zoo',
            'contact_person_name' => 'Zed', 'contact_person_designation' => 'Manager', 'contact_person_number' => '+65 1234',
        ];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'valid optional fields' => [['contact_number' => '', 'merchant_website' => 'https://zoo.example.com', 'online_only' => '1', 'multiple_redemption' => '0'], []],
            'missing name' => [['name' => self::UNSET], ['name' => ['_required']]],
            'long name' => [['name' => str_repeat('a', 101)], ['name' => ['maxLength']]],
            'invalid email' => [['email' => 'zoo'], ['email' => ['email']]],
            'long contact_number' => [['contact_number' => str_repeat('1', 16)], ['contact_number' => ['maxLength']]],
            'missing business_description' => [['business_description' => self::UNSET], ['business_description' => ['_required']]],
            'long business_description' => [['business_description' => str_repeat('a', 501)], ['business_description' => ['maxLength']]],
            'missing contact_person_name' => [['contact_person_name' => self::UNSET], ['contact_person_name' => ['_required']]],
            'missing contact_person_designation' => [['contact_person_designation' => self::UNSET], ['contact_person_designation' => ['_required']]],
            'long contact_person_number' => [['contact_person_number' => str_repeat('1', 46)], ['contact_person_number' => ['maxLength']]],
            'long voucher_template' => [['voucher_template' => str_repeat('v', 46)], ['voucher_template' => ['maxLength']]],
            'non-boolean online_only' => [['online_only' => 'often'], ['online_only' => ['boolean']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->Merchants, $changes, $expected);
    }

    public function testRulesUniqueEmail(): void
    {
        $this->insertRow('merchants', ['id' => '00000000-0000-4000-8000-000000000f02', 'image' => ''] + $this->validData());

        $this->assertTrue($this->Merchants->checkRules($this->Merchants->newEntity(['email' => 'other@example.com'] + $this->validData())));

        $duplicate = $this->Merchants->newEntity($this->validData());
        $this->assertFalse($this->Merchants->checkRules($duplicate));
        $this->assertArrayHasKey('_isUnique', $duplicate->getError('email'));
    }
}
