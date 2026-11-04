<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\FavoriteAttraction;
use App\Model\Table\FavoriteAttractionsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FavoriteAttractionsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000001201';
    private const MISSING = '00000000-0000-4000-8000-0000000000ff';

    protected array $fixtures = [...self::USER_FIXTURES, ...self::CATALOGUE_FIXTURES];

    private FavoriteAttractionsTable $FavoriteAttractions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->FavoriteAttractions = $this->getTableLocator()->get('FavoriteAttractions');
    }

    protected function validData(): array
    {
        return ['attraction_id' => self::ATTRACTION];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'empty attraction_id' => [['attraction_id' => ''], ['attraction_id' => ['_empty']]],
            'long attraction_id' => [['attraction_id' => str_repeat('a', 37)], ['attraction_id' => ['maxLength']]],
            'non-integer id' => [['id' => 'x'], ['id' => ['integer']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->FavoriteAttractions, $changes, $expected);
    }

    public function testRulesAttractionAndUserMustExist(): void
    {
        $this->createAgentsAndUsers();
        $this->createAttraction(self::ATTRACTION);

        $favorite = $this->FavoriteAttractions->newEntity(['attraction_id' => self::ATTRACTION, 'agent_user_id' => self::MASTER_A]);
        $this->assertTrue($this->FavoriteAttractions->checkRules($favorite));

        $favorite = $this->FavoriteAttractions->newEntity(['attraction_id' => self::MISSING, 'agent_user_id' => self::MASTER_A]);
        $this->assertFalse($this->FavoriteAttractions->checkRules($favorite));
        $this->assertArrayHasKey('_existsIn', $favorite->getError('attraction_id'));

        $favorite = $this->FavoriteAttractions->newEntity(['attraction_id' => self::ATTRACTION, 'agent_user_id' => self::MISSING]);
        $this->assertFalse($this->FavoriteAttractions->checkRules($favorite));
        $this->assertArrayHasKey('_existsIn', $favorite->getError('agent_user_id'));
    }

    public function testToggleAddsThenRemoves(): void
    {
        $this->createAgentsAndUsers();
        $this->createAttraction(self::ATTRACTION);

        $added = $this->FavoriteAttractions->toggle(self::ATTRACTION, self::MASTER_A);

        $this->assertInstanceOf(FavoriteAttraction::class, $added);
        $this->assertNotNull($added->id);
        $this->assertSame(1, $this->countRows('favorite_attractions'));

        // Another user's favourite is independent.
        $this->assertNotNull($this->FavoriteAttractions->toggle(self::ATTRACTION, self::NORMAL_A)?->id);
        $this->assertSame(2, $this->countRows('favorite_attractions'));

        $removed = $this->FavoriteAttractions->toggle(self::ATTRACTION, self::MASTER_A);

        $this->assertInstanceOf(FavoriteAttraction::class, $removed);
        $this->assertNull($removed->id);
        $this->assertSame(0, $this->countRows('favorite_attractions', 'agent_user_id = ' . self::uuidSql(), [self::MASTER_A]));
        $this->assertSame(1, $this->countRows('favorite_attractions'));
    }

    public function testToggleUnknownAttractionReturnsNull(): void
    {
        $this->createAgentsAndUsers();

        $this->assertNull($this->FavoriteAttractions->toggle(self::MISSING, self::MASTER_A));
        $this->assertSame(0, $this->countRows('favorite_attractions'));
    }
}
