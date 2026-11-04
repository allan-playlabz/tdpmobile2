<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CategoryTranslationsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CategoryTranslationsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    private const CATEGORY = '00000000-0000-4000-8000-000000001501';
    private const OTHER_CATEGORY = '00000000-0000-4000-8000-000000001502';

    protected array $fixtures = ['app.CategoryTranslations', 'app.Categories', 'app.Agents'];

    private CategoryTranslationsTable $CategoryTranslations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CategoryTranslations = $this->getTableLocator()->get('CategoryTranslations');
    }

    protected function validData(): array
    {
        return ['language' => 'zh_CN', 'name' => '动物园'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'missing language' => [['language' => self::UNSET], ['language' => ['_required']]],
            'long language' => [['language' => 'zh_CN_extra'], ['language' => ['maxLength']]],
            'missing name' => [['name' => self::UNSET], ['name' => ['_required']]],
            'empty name' => [['name' => ''], ['name' => ['_empty']]],
            'long name' => [['name' => str_repeat('a', 101)], ['name' => ['maxLength']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->CategoryTranslations, $changes, $expected);
    }

    public function testFindTranslation(): void
    {
        $this->insertRow('categories', ['id' => self::CATEGORY, 'name' => 'Zoos']);
        $this->insertRow('categories', ['id' => self::OTHER_CATEGORY, 'name' => 'Cruises']);
        $this->insertRow('category_translations', ['category_id' => self::CATEGORY, 'language' => 'zh_CN', 'name' => '动物园']);
        $this->insertRow('category_translations', ['category_id' => self::OTHER_CATEGORY, 'language' => 'zh_CN', 'name' => '游船']);

        $translation = $this->CategoryTranslations->findTranslation(self::CATEGORY, 'zh_CN');

        $this->assertSame('动物园', $translation->name);
        $this->assertSame('游船', $this->CategoryTranslations->findTranslation(self::OTHER_CATEGORY, 'zh_CN')->name);
        $this->assertNull($this->CategoryTranslations->findTranslation(self::CATEGORY, 'en_US'));
    }

    public function testRulesCategoryMustExist(): void
    {
        $this->insertRow('categories', ['id' => self::CATEGORY, 'name' => 'Zoos']);

        $this->assertTrue($this->CategoryTranslations->checkRules($this->CategoryTranslations->newEntity(['category_id' => self::CATEGORY] + $this->validData())));
        $this->assertFalse($this->CategoryTranslations->checkRules($this->CategoryTranslations->newEntity(['category_id' => self::OTHER_CATEGORY] + $this->validData())));
    }
}
