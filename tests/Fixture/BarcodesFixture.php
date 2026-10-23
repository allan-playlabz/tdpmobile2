<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BarcodesFixture
 */
class BarcodesFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'binaryuuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'package_code' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'code' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'purchased_ticket_sku_id' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'ticket_sku_id' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'validity_start_date' => ['type' => 'date', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'validity_end_date' => ['type' => 'date', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'status' => ['type' => 'string', 'length' => null, 'null' => false, 'default' => 'new', 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'sold' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => '0', 'comment' => '', 'precision' => null],
        'redeemed_datetime' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'custom_data' => ['type' => 'string', 'length' => 500, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_indexes' => [
            'ticket_sku_idx' => ['type' => 'index', 'columns' => ['purchased_ticket_sku_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'code_UNIQUE' => ['type' => 'unique', 'columns' => ['code'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd
    /**
     * Init method
     *
     * @return void
     */
    public function init()
    {
        $this->records = [
            [
                'id' => '',
                'package_code' => 'Lorem ipsum dolor sit amet',
                'code' => 'Lorem ipsum dolor sit amet',
                'purchased_ticket_sku_id' => 'Lorem ipsum dolor sit amet',
                'ticket_sku_id' => 'Lorem ipsum dolor sit amet',
                'validity_start_date' => '2021-01-21',
                'validity_end_date' => '2021-01-21',
                'status' => 'Lorem ipsum dolor sit amet',
                'sold' => 1,
                'redeemed_datetime' => '2021-01-21 05:44:09',
                'custom_data' => 'Lorem ipsum dolor sit amet',
                'created' => '2021-01-21 05:44:09',
                'modified' => '2021-01-21 05:44:09'
            ],
        ];
        parent::init();
    }
}
