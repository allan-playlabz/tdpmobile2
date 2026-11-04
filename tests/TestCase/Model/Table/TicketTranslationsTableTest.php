<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TicketTranslationsTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TicketTranslationsTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000001601';
    private const TICKET = '00000000-0000-4000-8000-000000001602';
    private const OTHER_TICKET = '00000000-0000-4000-8000-000000001603';

    protected array $fixtures = ['app.TicketTranslations', 'app.Tickets', 'app.Attractions'];

    private TicketTranslationsTable $TicketTranslations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->TicketTranslations = $this->getTableLocator()->get('TicketTranslations');
    }

    protected function validData(): array
    {
        return ['language' => 'zh_CN', 'title' => '成人', 'description' => '描述', 'terms_and_conditions' => '条款'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'missing language' => [['language' => self::UNSET], ['language' => ['_required']]],
            'missing title' => [['title' => self::UNSET], ['title' => ['_required']]],
            'long title' => [['title' => str_repeat('a', 101)], ['title' => ['maxLength']]],
            'empty description' => [['description' => ''], ['description' => ['_empty']]],
            'missing terms' => [['terms_and_conditions' => self::UNSET], ['terms_and_conditions' => ['_required']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->TicketTranslations, $changes, $expected);
    }

    public function testFindTranslation(): void
    {
        $this->createAttraction(self::ATTRACTION);
        $this->createTicket(self::TICKET, self::ATTRACTION);
        $this->createTicket(self::OTHER_TICKET, self::ATTRACTION);
        $this->insertRow('ticket_translations', [
            'id' => '00000000-0000-4000-8000-000000001611', 'ticket_id' => self::TICKET, 'language' => 'zh_CN',
            'title' => '成人', 'description' => 'D', 'terms_and_conditions' => 'T',
        ]);
        $this->insertRow('ticket_translations', [
            'id' => '00000000-0000-4000-8000-000000001612', 'ticket_id' => self::OTHER_TICKET, 'language' => 'zh_CN',
            'title' => '儿童', 'description' => 'D', 'terms_and_conditions' => 'T',
        ]);

        $translation = $this->TicketTranslations->findTranslation(self::TICKET, 'zh_CN');

        $this->assertSame('成人', $translation->title);
        $this->assertSame(self::TICKET, $translation->ticket_id);
        $this->assertSame('儿童', $this->TicketTranslations->findTranslation(self::OTHER_TICKET, 'zh_CN')->title);
        $this->assertNull($this->TicketTranslations->findTranslation(self::TICKET, 'en_US'));
    }
}
