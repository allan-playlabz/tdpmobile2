<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TicketsInvchartsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TicketsInvchartsTable Test Case
 */
class TicketsInvchartsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TicketsInvchartsTable
     */
    public $TicketsInvcharts;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.TicketsInvcharts',
        'app.Tickets',
        'app.Invcharts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('TicketsInvcharts') ? [] : ['className' => TicketsInvchartsTable::class];
        $this->TicketsInvcharts = TableRegistry::getTableLocator()->get('TicketsInvcharts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TicketsInvcharts);

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
