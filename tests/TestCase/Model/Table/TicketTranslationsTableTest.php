<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TicketTranslationsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TicketTranslationsTable Test Case
 */
class TicketTranslationsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TicketTranslationsTable
     */
    public $TicketTranslations;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.TicketTranslations',
        'app.Tickets',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('TicketTranslations') ? [] : ['className' => TicketTranslationsTable::class];
        $this->TicketTranslations = TableRegistry::getTableLocator()->get('TicketTranslations', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->TicketTranslations);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
