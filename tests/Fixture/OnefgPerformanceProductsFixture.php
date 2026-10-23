<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OnefgPerformanceProductsFixture
 */
class OnefgPerformanceProductsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'product_ak' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'product_name' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'product_description' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'performance_ak' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'performance_date' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'performance_time' => ['type' => 'time', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'seat_category_description' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'availability' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'price' => ['type' => 'float', 'length' => 10, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'idx' => ['type' => 'unique', 'columns' => ['product_ak', 'performance_ak', 'performance_date', 'performance_time'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'latin1_swedish_ci'
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
                'id' => 1,
                'product_ak' => 'Lorem ipsum dolor sit amet',
                'product_name' => 'Lorem ipsum dolor sit amet',
                'product_description' => 'Lorem ipsum dolor sit amet',
                'performance_ak' => 'Lorem ipsum dolor sit amet',
                'performance_date' => '2022-02-22',
                'performance_time' => '04:18:53',
                'seat_category_description' => 'Lorem ipsum dolor sit amet',
                'availability' => 1,
                'price' => 1,
                'created' => '2022-02-22 04:18:53',
                'modified' => '2022-02-22 04:18:53'
            ],
        ];
        parent::init();
    }
}
