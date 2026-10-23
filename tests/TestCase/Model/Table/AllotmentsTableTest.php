<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AllotmentsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AllotmentsTable Test Case
 */
class AllotmentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AllotmentsTable
     */
    public $Allotments;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Allotments',
        'app.Merchants',
        'app.Tickets',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Allotments') ? [] : ['className' => AllotmentsTable::class];
        $this->Allotments = TableRegistry::getTableLocator()->get('Allotments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Allotments);

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
