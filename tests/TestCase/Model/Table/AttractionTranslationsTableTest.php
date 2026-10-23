<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AttractionTranslationsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AttractionTranslationsTable Test Case
 */
class AttractionTranslationsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AttractionTranslationsTable
     */
    public $AttractionTranslations;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.AttractionTranslations',
        'app.Attractions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('AttractionTranslations') ? [] : ['className' => AttractionTranslationsTable::class];
        $this->AttractionTranslations = TableRegistry::getTableLocator()->get('AttractionTranslations', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AttractionTranslations);

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
