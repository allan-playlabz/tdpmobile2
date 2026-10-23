<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CategoryTranslationsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CategoryTranslationsTable Test Case
 */
class CategoryTranslationsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CategoryTranslationsTable
     */
    public $CategoryTranslations;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.CategoryTranslations',
        'app.Categories',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('CategoryTranslations') ? [] : ['className' => CategoryTranslationsTable::class];
        $this->CategoryTranslations = TableRegistry::getTableLocator()->get('CategoryTranslations', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->CategoryTranslations);

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
