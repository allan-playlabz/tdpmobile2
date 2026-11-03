<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AttractionsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AttractionsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    private const CATEGORY = '00000000-0000-4000-8000-000000000f01';
    private const MERCHANT = '00000000-0000-4000-8000-000000000f02';
    private const MISSING = '00000000-0000-4000-8000-0000000000ff';

    protected array $fixtures = [...self::CATALOGUE_FIXTURES];

    private AttractionsTable $Attractions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Attractions = $this->getTableLocator()->get('Attractions');
    }

    protected function validData(): array
    {
        return ['title' => 'Night Zoo', 'description' => 'Desc', 'terms_and_conditions' => 'T&C'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'valid optional fields' => [['featured_image' => '', 'print_as_qr_code' => '1', 'status' => 'published', 'type' => 'single'], []],
            'missing title' => [['title' => self::UNSET], ['title' => ['_required']]],
            'empty title' => [['title' => ''], ['title' => ['_empty']]],
            'long title' => [['title' => str_repeat('a', 101)], ['title' => ['maxLength']]],
            'missing description' => [['description' => self::UNSET], ['description' => ['_required']]],
            'missing terms' => [['terms_and_conditions' => self::UNSET], ['terms_and_conditions' => ['_required']]],
            'long featured_image' => [['featured_image' => str_repeat('a', 256)], ['featured_image' => ['maxLength']]],
            'non-boolean print_as_qr_code' => [['print_as_qr_code' => 'sometimes'], ['print_as_qr_code' => ['boolean']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->Attractions, $changes, $expected);
    }

    public function testRulesCategoryAndMerchantMustExist(): void
    {
        $this->insertRow('categories', ['id' => self::CATEGORY, 'name' => 'Zoos']);
        $this->insertRow('merchants', [
            'id' => self::MERCHANT, 'name' => 'Zoo Co', 'email' => 'zoo@example.com', 'business_description' => 'Zoo',
            'contact_person_name' => 'Z', 'contact_person_designation' => 'Boss', 'contact_person_number' => '123', 'image' => '',
        ]);

        $attraction = $this->Attractions->newEntity(['category_id' => self::CATEGORY, 'merchant_id' => self::MERCHANT] + $this->validData());
        $this->assertTrue($this->Attractions->checkRules($attraction));

        // merchant_id is optional
        $attraction = $this->Attractions->newEntity(['category_id' => self::CATEGORY] + $this->validData());
        $this->assertTrue($this->Attractions->checkRules($attraction));

        $attraction = $this->Attractions->newEntity(['category_id' => self::MISSING, 'merchant_id' => self::MERCHANT] + $this->validData());
        $this->assertFalse($this->Attractions->checkRules($attraction));
        $this->assertArrayHasKey('_existsIn', $attraction->getError('category_id'));

        $attraction = $this->Attractions->newEntity(['category_id' => self::CATEGORY, 'merchant_id' => self::MISSING] + $this->validData());
        $this->assertFalse($this->Attractions->checkRules($attraction));
        $this->assertArrayHasKey('_existsIn', $attraction->getError('merchant_id'));
    }
}
