<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PackagesTicketsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PackagesTicketsTable Test Case
 */
class PackagesTicketsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PackagesTicketsTable
     */
    public $PackagesTickets;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.PackagesTickets',
        'app.Tickets',
        'app.PackageTickets'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('PackagesTickets') ? [] : ['className' => PackagesTicketsTable::class];
        $this->PackagesTickets = TableRegistry::getTableLocator()->get('PackagesTickets', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->PackagesTickets);

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
