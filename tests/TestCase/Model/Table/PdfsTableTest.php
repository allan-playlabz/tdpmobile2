<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PdfsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PdfsTable Test Case
 */
class PdfsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PdfsTable
     */
    public $Pdfs;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Pdfs',
        'app.PurchasedTicketSkus',
        'app.TicketSkus'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Pdfs') ? [] : ['className' => PdfsTable::class];
        $this->Pdfs = TableRegistry::getTableLocator()->get('Pdfs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Pdfs);

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
