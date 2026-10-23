<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrdersToCompleteFixture
 */
class OrdersToCompleteFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'orders_to_complete';
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
