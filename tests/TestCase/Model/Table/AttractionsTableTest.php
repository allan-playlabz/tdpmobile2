<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AttractionsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AttractionsTable Test Case
 */
class AttractionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AttractionsTable
     */
    public $Attractions;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Attractions',
        'app.Merchants',
        'app.Categories',
        'app.Skus',
        'app.AttractionMedias',
        'app.AttractionTranslations',
        'app.SettlementAgents',
        'app.Tickets'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Attractions') ? [] : ['className' => AttractionsTable::class];
        $this->Attractions = TableRegistry::getTableLocator()->get('Attractions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Attractions);

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
