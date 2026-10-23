<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AgentsSignupsFixture
 */
class AgentsSignupsFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'binaryuuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'first_name' => ['type' => 'string', 'length' => 50, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'last_name' => ['type' => 'string', 'length' => 50, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'email' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'password' => ['type' => 'string', 'length' => 64, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'verification_code' => ['type' => 'string', 'length' => 64, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'otp' => ['type' => 'smallinteger', 'length' => 6, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'otp_created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'company_name' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'image' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'general_category' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'country' => ['type' => 'string', 'length' => 100, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'issued_bank' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'account_no' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'contact_no' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'gender' => ['type' => 'string', 'length' => null, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'credits' => ['type' => 'float', 'length' => 10, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
        'rebates' => ['type' => 'float', 'length' => 10, 'precision' => 2, 'unsigned' => false, 'null' => false, 'default' => '0.00', 'comment' => ''],
        'role' => ['type' => 'string', 'length' => null, 'null' => true, 'default' => 'master', 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'master_agent_id' => ['type' => 'binaryuuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'enable_generation_confirmation' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => '0', 'comment' => '', 'precision' => null],
        'enable_api' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'api_key' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'secret_key' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'allowed_api_cancel' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => '0', 'comment' => '', 'precision' => null],
        'email_displayname' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'status' => ['type' => 'string', 'length' => null, 'null' => true, 'default' => 'active', 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'can_add_sub' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => '0', 'comment' => '', 'precision' => null],
        'last_activity' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
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
                'first_name' => 'Lorem ipsum dolor sit amet',
                'last_name' => 'Lorem ipsum dolor sit amet',
                'email' => 'Lorem ipsum dolor sit amet',
                'password' => 'Lorem ipsum dolor sit amet',
                'verification_code' => 'Lorem ipsum dolor sit amet',
                'otp' => 1,
                'otp_created' => '2022-12-13 16:31:27',
                'company_name' => 'Lorem ipsum dolor sit amet',
                'image' => 'Lorem ipsum dolor sit amet',
                'general_category' => 'Lorem ipsum dolor sit amet',
                'country' => 'Lorem ipsum dolor sit amet',
                'issued_bank' => 'Lorem ipsum dolor sit amet',
                'account_no' => 'Lorem ipsum dolor sit amet',
                'contact_no' => 'Lorem ipsum dolor sit amet',
                'gender' => 'Lorem ipsum dolor sit amet',
                'credits' => 1,
                'rebates' => 1,
                'role' => 'Lorem ipsum dolor sit amet',
                'master_agent_id' => '',
                'enable_generation_confirmation' => 1,
                'enable_api' => 1,
                'api_key' => 'Lorem ipsum dolor sit amet',
                'secret_key' => 'Lorem ipsum dolor sit amet',
                'allowed_api_cancel' => 1,
                'email_displayname' => 'Lorem ipsum dolor sit amet',
                'status' => 'Lorem ipsum dolor sit amet',
                'can_add_sub' => 1,
                'last_activity' => '2022-12-13 16:31:27',
                'created' => '2022-12-13 16:31:27',
                'modified' => '2022-12-13 16:31:27',
            ],
        ];
        parent::init();
    }
}
