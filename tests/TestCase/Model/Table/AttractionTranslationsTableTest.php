<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AttractionTranslationsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AttractionTranslationsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000001401';
    private const OTHER_ATTRACTION = '00000000-0000-4000-8000-000000001402';

    protected array $fixtures = ['app.AttractionTranslations', 'app.Attractions'];

    private AttractionTranslationsTable $AttractionTranslations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->AttractionTranslations = $this->getTableLocator()->get('AttractionTranslations');
    }

    protected function validData(): array
    {
        return ['language' => 'zh_CN', 'title' => '夜间动物园', 'description' => '描述', 'terms_and_conditions' => '条款'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'missing language' => [['language' => self::UNSET], ['language' => ['_required']]],
            'long language' => [['language' => 'zh_CN_extra'], ['language' => ['maxLength']]],
            'missing title' => [['title' => self::UNSET], ['title' => ['_required']]],
            'long title' => [['title' => str_repeat('a', 101)], ['title' => ['maxLength']]],
            'missing description' => [['description' => self::UNSET], ['description' => ['_required']]],
            'empty terms' => [['terms_and_conditions' => ''], ['terms_and_conditions' => ['_empty']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->AttractionTranslations, $changes, $expected);
    }

    public function testFindTranslation(): void
    {
        $this->createAttraction(self::ATTRACTION);
        $this->createAttraction(self::OTHER_ATTRACTION);
        foreach ([[self::ATTRACTION, 'zh_CN', '夜间动物园'], [self::ATTRACTION, 'ms_MY', 'Zoo Malam'], [self::OTHER_ATTRACTION, 'zh_CN', '河流']] as $i => [$attractionId, $language, $title]) {
            $this->insertRow('attraction_translations', [
                'id' => sprintf('00000000-0000-4000-8000-0000000014%02d', 10 + $i), 'attraction_id' => $attractionId,
                'language' => $language, 'title' => $title, 'description' => 'D', 'terms_and_conditions' => 'T',
            ]);
        }

        $translation = $this->AttractionTranslations->findTranslation(self::ATTRACTION, 'zh_CN');

        $this->assertSame('夜间动物园', $translation->title);
        $this->assertSame(self::ATTRACTION, $translation->attraction_id);
        $this->assertSame('Zoo Malam', $this->AttractionTranslations->findTranslation(self::ATTRACTION, 'ms_MY')->title);
        $this->assertNull($this->AttractionTranslations->findTranslation(self::ATTRACTION, 'ja_JP'));
        $this->assertNull($this->AttractionTranslations->findTranslation('00000000-0000-4000-8000-0000000000ff', 'zh_CN'));
    }

    public function testRulesAttractionMustExist(): void
    {
        $this->createAttraction(self::ATTRACTION);

        $this->assertTrue($this->AttractionTranslations->checkRules($this->AttractionTranslations->newEntity(['attraction_id' => self::ATTRACTION] + $this->validData())));
        $this->assertFalse($this->AttractionTranslations->checkRules($this->AttractionTranslations->newEntity(['attraction_id' => self::OTHER_ATTRACTION] + $this->validData())));
    }
}
