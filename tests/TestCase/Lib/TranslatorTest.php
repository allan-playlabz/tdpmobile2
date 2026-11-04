<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\Translator;
use App\Model\Entity\Attraction;
use App\Model\Entity\Category;
use App\Model\Entity\Ticket;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\TestSuite\TestCase;

/**
 * Translations of attractions, tickets and categories for the session language.
 */
class TranslatorTest extends TestCase
{
    use AppDataTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000000701';
    private const TICKET = '00000000-0000-4000-8000-000000000711';
    private const CATEGORY = '00000000-0000-4000-8000-000000000721';
    private const CHILD_CATEGORY = '00000000-0000-4000-8000-000000000722';

    protected array $fixtures = [
        'app.AttractionTranslations',
        'app.TicketTranslations',
        'app.CategoryTranslations',
    ];

    private mixed $languages;

    protected function setUp(): void
    {
        parent::setUp();
        $this->languages = Configure::read('Translation.languages');
        Configure::write('Translation.languages', ['zh_CN' => '中文']);

        $this->insertRow('attraction_translations', [
            'id' => '00000000-0000-4000-8000-000000000731', 'attraction_id' => self::ATTRACTION, 'language' => 'zh_CN',
            'title' => '景点', 'description' => '描述', 'terms_and_conditions' => '条款',
        ]);
        $this->insertRow('ticket_translations', [
            'id' => '00000000-0000-4000-8000-000000000732', 'ticket_id' => self::TICKET, 'language' => 'zh_CN',
            'title' => '门票', 'description' => '门票描述', 'terms_and_conditions' => '门票条款',
        ]);
        $this->insertRow('category_translations', ['category_id' => self::CATEGORY, 'language' => 'zh_CN', 'name' => '类别']);
        $this->insertRow('category_translations', ['category_id' => self::CHILD_CATEGORY, 'language' => 'zh_CN', 'name' => '子类别']);
    }

    protected function tearDown(): void
    {
        Configure::write('Translation.languages', $this->languages);
        parent::tearDown();
    }

    private function request(?string $language): ServerRequest
    {
        $session = new Session();
        if ($language !== null) {
            $session->write('language', $language);
        }

        return new ServerRequest(['session' => $session]);
    }

    private function attraction(): Attraction
    {
        return new Attraction(['id' => self::ATTRACTION, 'title' => 'Attraction', 'description' => 'Description', 'terms_and_conditions' => 'Terms']);
    }

    public function testAttractionIsUnchangedWithoutSessionLanguage(): void
    {
        $attraction = Translator::translateAttraction($this->request(null), $this->attraction());

        $this->assertSame('Attraction', $attraction->title);
        $this->assertSame('Description', $attraction->description);
    }

    public function testAttractionIsUnchangedForUnsupportedLanguage(): void
    {
        $attraction = Translator::translateAttraction($this->request('fr_FR'), $this->attraction());

        $this->assertSame('Attraction', $attraction->title);
    }

    public function testAttractionIsTranslated(): void
    {
        $attraction = Translator::translateAttraction($this->request('zh_CN'), $this->attraction());

        $this->assertSame('景点', $attraction->title);
        $this->assertSame('描述', $attraction->description);
        $this->assertSame('条款', $attraction->terms_and_conditions);
    }

    public function testAttractionWithoutTranslationKeepsOriginal(): void
    {
        $other = new Attraction(['id' => '00000000-0000-4000-8000-0000000007ff', 'title' => 'Untranslated']);

        $this->assertSame('Untranslated', Translator::translateAttraction($this->request('zh_CN'), $other)->title);
    }

    public function testTicketAndItsAttractionAreTranslated(): void
    {
        $ticket = new Ticket([
            'id' => self::TICKET, 'name' => 'Ticket', 'description' => 'Ticket description', 'terms_and_conditions' => 'Ticket terms',
            'attraction' => $this->attraction(),
        ]);

        $ticket = Translator::translateTicket($this->request('zh_CN'), $ticket);

        $this->assertSame('门票', $ticket->name);
        $this->assertSame('门票描述', $ticket->description);
        $this->assertSame('门票条款', $ticket->terms_and_conditions);
        $this->assertSame('景点', $ticket->attraction->title);
    }

    public function testTicketIsUnchangedWithoutSessionLanguage(): void
    {
        $ticket = Translator::translateTicket($this->request(null), new Ticket(['id' => self::TICKET, 'name' => 'Ticket']));

        $this->assertSame('Ticket', $ticket->name);
    }

    public function testCategoryAndChildrenAreTranslated(): void
    {
        $category = new Category([
            'id' => self::CATEGORY, 'name' => 'Category',
            'child_categories' => [new Category(['id' => self::CHILD_CATEGORY, 'name' => 'Child'])],
        ]);

        $category = Translator::translateCategory($this->request('zh_CN'), $category);

        $this->assertSame('类别', $category->name);
        $this->assertSame('子类别', $category->child_categories[0]->name);
    }
}
