<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * GeneratedCodesFixture
 */
class GeneratedCodesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        // Baked 3.x placeholder rows (empty binary-UUID ids, 'Lorem ipsum' values) don't fit the
        // real schema; tests that need data should define realistic rows.
        $this->records = [];
        parent::init();
    }
}
