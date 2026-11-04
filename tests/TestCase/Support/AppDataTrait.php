<?php
declare(strict_types=1);

namespace App\Test\TestCase\Support;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Database\Connection;
use Cake\I18n\DateTime;
use Cake\Utility\Text;
use DateTimeInterface;

/**
 * Test data helpers.
 *
 * Rows are written with raw inserts: saving through the ORM runs LoggableBehavior, which
 * isn't the subject of most tests. UUID strings are stored as binary(16), DateTimes as datetime.
 *
 * Every table written here must be listed in the test's `$fixtures` so it is emptied between tests.
 */
trait AppDataTrait
{
    public const AGENT_A = '00000000-0000-4000-8000-00000000000a';
    public const AGENT_B = '00000000-0000-4000-8000-00000000000b';
    public const MASTER_A = '00000000-0000-4000-8000-0000000000a1';
    public const NORMAL_A = '00000000-0000-4000-8000-0000000000a2';
    public const VIEWER_A = '00000000-0000-4000-8000-0000000000a3';
    public const MASTER_B = '00000000-0000-4000-8000-0000000000b1';
    public const NORMAL_B = '00000000-0000-4000-8000-0000000000b2';
    public const PASSWORD = 'Secret#12345';

    /**
     * Fixtures for agents and their users (login, layout, logging).
     */
    public const USER_FIXTURES = [
        'app.Agents',
        'app.AgentUsers',
        'app.Announcements',
        'app.Logins',
        'app.Logs',
        'app.UsersIpaddress',
        'app.LastOtpIps',
    ];

    /**
     * Fixtures for the catalogue (attractions, tickets, settlement pricing).
     */
    public const CATALOGUE_FIXTURES = [
        'app.Attractions',
        'app.Tickets',
        'app.Settlements',
        'app.SettlementAgents',
        'app.Cities',
        'app.Categories',
        'app.Merchants',
        'app.FavoriteAttractions',
        'app.RecommendedAttractions',
        'app.Tags',
        'app.Fixeddates',
        'app.Fdatesettlements',
        'app.FdatesettlementsAgents',
        'app.FdateOverrideRates',
        'app.TicketTripTypes',
        'app.Connections',
        'app.Pdfs',
        'app.Barcodes',
        'app.GeneratedCodes',
        'app.Allotments',
    ];

    /**
     * Fixtures for orders and vouchers.
     */
    public const ORDER_FIXTURES = [
        'app.Orders',
        'app.OrderItems',
        'app.Vouchers',
        'app.VoucherMerging',
        'app.VoucherTemplates',
        'app.PaymentMethods',
        'app.PaymentGateways',
        'app.PaymentMarkups',
        'app.PaymentResponses',
        'app.CreditLogs',
    ];

    protected function db(): Connection
    {
        return $this->getTableLocator()->get('Agents')->getConnection();
    }

    /**
     * Inserts a row; UUID strings become binaryuuid, DateTimes become datetime.
     *
     * @param string $table Table name
     * @param array<string, mixed> $row Column values
     * @return array<string, mixed> The row as inserted
     */
    protected function insertRow(string $table, array $row): array
    {
        $types = [];
        foreach ($row as $column => $value) {
            if (is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
                $types[$column] = 'binaryuuid';
            } elseif ($value instanceof DateTimeInterface) {
                $types[$column] = 'datetime';
            } elseif (is_bool($value)) {
                $row[$column] = (int)$value;
            }
        }
        $this->db()->insert($table, $row, $types);

        return $row;
    }

    /**
     * Fetches one row as an array (binary uuid columns returned as hex), or null.
     */
    protected function fetchRow(string $sql, array $params = []): ?array
    {
        $row = $this->db()->execute($sql, $params)->fetch('assoc');

        return $row ?: null;
    }

    protected function countRows(string $table, string $where = '1=1', array $params = []): int
    {
        return (int)$this->db()->execute("SELECT COUNT(*) AS c FROM {$table} WHERE {$where}", $params)->fetch('assoc')['c'];
    }

    /**
     * SQL expression turning a uuid parameter into binary(16).
     */
    protected static function uuidSql(): string
    {
        return "UNHEX(REPLACE(?, '-', ''))";
    }

    protected function createAgent(string $id, array $values = []): array
    {
        return $this->insertRow('agents', $values + [
            'id' => $id, 'first_name' => 'Test', 'last_name' => 'Agent ' . substr($id, -1),
            'email' => 'agent-' . substr($id, -4) . '@example.com', 'company_name' => 'Company ' . substr($id, -1),
            'image' => '', 'country' => 'Singapore', 'gender' => 'male', 'enable_api' => 0,
            'status' => 'active', 'enable_agent_login' => 1, 'mobile_otp' => 0, 'credits' => '1000.00',
        ]);
    }

    protected function createAgentUser(string $id, string $agentId, string $role, string $email, array $values = []): array
    {
        return $this->insertRow('agent_users', $values + [
            'id' => $id, 'agent_id' => $agentId, 'name' => ucfirst($role) . ' User', 'email' => $email,
            'password' => (new DefaultPasswordHasher())->hash(self::PASSWORD), 'role' => $role, 'status' => 'active',
        ]);
    }

    /**
     * Two agents: A with a master, a normal and a viewer user; B with a master and a normal user.
     * The second factor is off (agents.mobile_otp = 0).
     */
    protected function createAgentsAndUsers(): void
    {
        $this->createAgent(self::AGENT_A);
        $this->createAgent(self::AGENT_B);
        $this->createAgentUser(self::MASTER_A, self::AGENT_A, 'master', 'master.a@example.com');
        $this->createAgentUser(self::NORMAL_A, self::AGENT_A, 'normal', 'normal.a@example.com');
        $this->createAgentUser(self::VIEWER_A, self::AGENT_A, 'viewer', 'viewer.a@example.com');
        $this->createAgentUser(self::MASTER_B, self::AGENT_B, 'master', 'master.b@example.com');
        $this->createAgentUser(self::NORMAL_B, self::AGENT_B, 'normal', 'normal.b@example.com');
    }

    /**
     * Logs in by writing the session layout AgentUsersController::login() stores at `Auth.User`.
     */
    protected function loginAs(string $userId, array $extraSession = []): void
    {
        $users = [
            self::MASTER_A => [self::AGENT_A, 'master', 'master.a@example.com'],
            self::NORMAL_A => [self::AGENT_A, 'normal', 'normal.a@example.com'],
            self::VIEWER_A => [self::AGENT_A, 'viewer', 'viewer.a@example.com'],
            self::MASTER_B => [self::AGENT_B, 'master', 'master.b@example.com'],
            self::NORMAL_B => [self::AGENT_B, 'normal', 'normal.b@example.com'],
        ];
        [$agentId, $role, $email] = $users[$userId];
        $this->session(['Auth' => ['User' => [
            'id' => $userId, 'agent_id' => $agentId, 'role' => $role, 'email' => $email, 'name' => ucfirst($role) . ' User',
            'credits' => '1000.00',
            'agent' => [
                'id' => $agentId, 'mobile_otp' => false, 'low_credit_limit' => '0.00', 'first_name' => 'Test', 'last_name' => 'Agent',
                'company_name' => 'Company', 'enable_credit_logs_view' => true, 'allowed_api_cancel' => true,
            ],
        ]]] + $extraSession);
    }

    protected function createAttraction(string $id, array $values = []): array
    {
        return $this->insertRow('attractions', $values + [
            'id' => $id, 'category_id' => Text::uuid(), 'title' => 'Attraction ' . substr($id, -4),
            'sku_id' => 'ATT-' . substr($id, -4), 'description' => 'Description', 'terms_and_conditions' => 'Terms',
            'status' => 'published', 'product_type' => 'simple', 'pricing_type' => 'settlement',
            'created' => new DateTime('-1 day'), 'modified' => new DateTime('-1 day'),
        ]);
    }

    protected function createTicket(string $id, string $attractionId, array $values = []): array
    {
        return $this->insertRow('tickets', $values + [
            'id' => $id, 'attraction_id' => $attractionId, 'sku_id' => 'TKT-' . substr($id, -4),
            'name' => 'Ticket ' . substr($id, -4), 'description' => 'Ticket description', 'terms_and_conditions' => 'Ticket terms',
            'sequence' => 1, 'publish_start_date' => new DateTime('-30 days'), 'publish_end_date' => new DateTime('+365 days'),
            'redemption_start_date' => new DateTime('-30 days'), 'redemption_end_date' => new DateTime('+365 days'),
            'pricing_type' => 'settlement', 'type' => 'normal', 'voucher_type' => 'generated', 'cutoff_seconds' => 0,
            'created' => new DateTime('-1 day'), 'modified' => new DateTime('-1 day'),
        ]);
    }

    /**
     * Gives the agent a settlement price for the ticket; returns the settlement id.
     */
    protected function createSettlement(string $agentId, string $attractionId, string $ticketId, string $rate = '25.00'): int
    {
        $this->insertRow('settlements', [
            'ticket_id' => $ticketId, 'settlement_group' => 1, 'rate' => $rate, 'rebate' => '0.00',
        ]);
        $settlementId = (int)$this->db()->execute('SELECT MAX(id) AS id FROM settlements')->fetch('assoc')['id'];
        $this->insertRow('settlement_agents', [
            'attraction_id' => $attractionId, 'ticket_id' => $ticketId, 'settlement_id' => $settlementId, 'agent_id' => $agentId,
        ]);

        return $settlementId;
    }

    protected function createOrder(string $id, string $agentUserId, string $agentId, array $values = []): array
    {
        return $this->insertRow('orders', $values + [
            'id' => $id, 'order_ref_id' => 'REF' . strtoupper(substr(str_replace('-', '', $id), -10)),
            'agent_user_id' => $agentUserId, 'agent_id' => $agentId, 'customer_name' => 'Customer',
            'status' => 'done', 'subtotal' => '50.00', 'convinience_fee' => '0.00', 'total' => '50.00',
            'payment_method' => 'credits', 'created' => new DateTime('-1 day'), 'modified' => new DateTime('-1 day'),
        ]);
    }

    protected function createOrderItem(string $id, string $orderId, string $ticketId, array $values = []): array
    {
        return $this->insertRow('order_items', $values + [
            'id' => $id, 'order_id' => $orderId, 'ticket_id' => $ticketId, 'qty' => 2, 'price' => '25.00', 'total' => '50.00',
            'created' => new DateTime('-1 day'), 'modified' => new DateTime('-1 day'),
        ]);
    }

    protected function createVoucher(string $id, string $orderItemId, string $code, array $values = []): array
    {
        return $this->insertRow('vouchers', $values + [
            'id' => $id, 'voucher_type' => 'generated', 'code_id' => $code, 'order_item_id' => $orderItemId,
            'created' => new DateTime('-1 day'), 'modified' => new DateTime('-1 day'),
        ]);
    }
}
