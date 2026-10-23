<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FavoriteAttractionsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\FavoriteAttractionsTable Test Case
 */
class FavoriteAttractionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\FavoriteAttractionsTable
     */
    public $FavoriteAttractions;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.FavoriteAttractions',
        'app.Attractions',
        'app.AgentUsers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('FavoriteAttractions') ? [] : ['className' => FavoriteAttractionsTable::class];
        $this->FavoriteAttractions = TableRegistry::getTableLocator()->get('FavoriteAttractions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->FavoriteAttractions);

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
