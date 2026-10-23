<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BarcodesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BarcodesTable Test Case
 */
class BarcodesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BarcodesTable
     */
    public $Barcodes;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.Barcodes',
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
        $config = TableRegistry::getTableLocator()->exists('Barcodes') ? [] : ['className' => BarcodesTable::class];
        $this->Barcodes = TableRegistry::getTableLocator()->get('Barcodes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Barcodes);

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
