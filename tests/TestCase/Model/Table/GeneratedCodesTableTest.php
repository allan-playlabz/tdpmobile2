<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\GeneratedCodesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\GeneratedCodesTable Test Case
 */
class GeneratedCodesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\GeneratedCodesTable
     */
    public $GeneratedCodes;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.GeneratedCodes',
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
        $config = TableRegistry::getTableLocator()->exists('GeneratedCodes') ? [] : ['className' => GeneratedCodesTable::class];
        $this->GeneratedCodes = TableRegistry::getTableLocator()->get('GeneratedCodes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->GeneratedCodes);

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
