<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\TicketsHelperComponent;
use App\Model\Entity\Ticket;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * Availability and reseller pricing helpers.
 */
class TicketsHelperComponentTest extends TestCase
{
    use AppDataTrait;

    private const ATTRACTION = '00000000-0000-4000-8000-000000000601';
    private const TICKET = '00000000-0000-4000-8000-000000000611';
    private const ALLOTMENT = '00000000-0000-4000-8000-000000000621';

    protected array $fixtures = [
        'app.Attractions',
        'app.Tickets',
        'app.Fixeddates',
        'app.Settlements',
        'app.SettlementAgents',
        'app.Fdatesettlements',
        'app.FdatesettlementsAgents',
        'app.FdateOverrideRates',
        'app.Allotments',
        'app.Slots',
        'app.Pdfs',
        'app.Barcodes',
    ];

    private TicketsHelperComponent $helper;
    private mixed $allotmentFeature;

    protected function setUp(): void
    {
        parent::setUp();
        $this->allotmentFeature = Configure::read('Features.allotment');
        Configure::write('Features.allotment', false);
        Configure::write('Default.max_order_quantity', 20);
        $this->helper = new TicketsHelperComponent(new ComponentRegistry(new Controller(new ServerRequest())));
    }

    protected function tearDown(): void
    {
        Configure::write('Features.allotment', $this->allotmentFeature);
        parent::tearDown();
    }

    private function makeTicket(string $pricingType = 'settlement', array $ticketValues = []): Ticket
    {
        $this->createAttraction(self::ATTRACTION, ['pricing_type' => $pricingType]);
        $this->createTicket(self::TICKET, self::ATTRACTION, $ticketValues + ['pricing_type' => $pricingType, 'price' => '40.00']);

        return $this->getTableLocator()->get('Tickets')->get(self::TICKET, contain: ['Attractions']);
    }

    private function createFixeddate(string $date, ?string $time, ?int $stock): int
    {
        $this->insertRow('fixeddates', [
            'ticket_id' => self::TICKET, 'date' => $date, 'time' => $time, 'rate' => 35, 'stock' => $stock,
            'created' => new DateTime(), 'modified' => new DateTime(),
        ]);

        return (int)$this->db()->execute('SELECT MAX(id) AS id FROM fixeddates')->fetch('assoc')['id'];
    }

    private function createFdatesettlement(string $agentId, float $rate): int
    {
        $this->insertRow('fdatesettlements', ['ticket_id' => self::TICKET, 'label' => 'Group', 'rate' => $rate, 'rebate' => 0]);
        $id = (int)$this->db()->execute('SELECT MAX(id) AS id FROM fdatesettlements')->fetch('assoc')['id'];
        $this->insertRow('fdatesettlements_agents', ['ticket_id' => self::TICKET, 'fdatesettlement_id' => $id, 'agent_id' => $agentId]);

        return $id;
    }

    // ---- getTicketAvailableQty ----

    public function testSettlementTicketWithoutStockAllowsMaxOrderQuantity(): void
    {
        $this->assertSame(20, $this->helper->getTicketAvailableQty($this->makeTicket()));
    }

    public function testSettlementTicketIsLimitedByStock(): void
    {
        $this->assertSame(5, $this->helper->getTicketAvailableQty($this->makeTicket('settlement', ['stock' => 5])));
    }

    public function testFixedDateTicketUsesStockOfThatDate(): void
    {
        $ticket = $this->makeTicket('fdate');
        $this->createFixeddate('2026-12-01', null, 3);
        $this->createFixeddate('2026-12-03', null, null);
        $this->createFixeddate('2026-12-04', null, 500);

        $this->assertSame(3, $this->helper->getTicketAvailableQty($ticket, '01 Dec 2026'));
        $this->assertSame(0, $this->helper->getTicketAvailableQty($ticket, '02 Dec 2026'), 'no fixed date => sold out');
        $this->assertSame(20, $this->helper->getTicketAvailableQty($ticket, '03 Dec 2026'), 'no stock => max order quantity');
        $this->assertSame(20, $this->helper->getTicketAvailableQty($ticket, '04 Dec 2026'), 'never above max order quantity');
    }

    public function testFixedDateTimeTicketMatchesTheTime(): void
    {
        $ticket = $this->makeTicket('fdatetime');
        $this->createFixeddate('2026-12-01', '10:00:00', 7);

        $this->assertSame(7, $this->helper->getTicketAvailableQty($ticket, '01 Dec 2026 10:00'));
        $this->assertSame(0, $this->helper->getTicketAvailableQty($ticket, '01 Dec 2026 11:00'));
    }

    // ---- getOpenDateAvailableQuantity ----

    public function testOpenDateQuantityIsLimitedByStockAndAllotment(): void
    {
        $this->insertRow('allotments', [
            'id' => self::ALLOTMENT, 'merchant_id' => '00000000-0000-4000-8000-000000000631', 'name' => 'Pool', 'available' => 4,
            'pricing_type' => 'settlement', 'created' => new DateTime(), 'modified' => new DateTime(),
        ]);

        $this->assertSame(20, $this->helper->getOpenDateAvailableQuantity(new Ticket(['stock' => null, 'allotment_id' => null])));
        $this->assertSame(10, $this->helper->getOpenDateAvailableQuantity(new Ticket(['stock' => 10, 'allotment_id' => null])));
        $this->assertSame(4, $this->helper->getOpenDateAvailableQuantity(new Ticket(['stock' => 10, 'allotment_id' => self::ALLOTMENT])));
        $this->assertSame(3, $this->helper->getOpenDateAvailableQuantity(new Ticket(['stock' => 3, 'allotment_id' => self::ALLOTMENT])));
    }

    // ---- reseller prices ----

    public function testResellerPriceComesFromTheAgentsSettlement(): void
    {
        $ticket = $this->makeTicket();
        $this->createSettlement(self::AGENT_A, self::ATTRACTION, self::TICKET, '25.00');

        $priced = $this->helper->getTicketResellerPrice($ticket, $ticket->attraction, ['agent_id' => self::AGENT_A]);

        $this->assertNotNull($priced);
        $this->assertEqualsWithDelta(25.0, (float)$priced->price, 0.001);
        $this->assertEqualsWithDelta(40.0, (float)$priced->retail_price, 0.001);
    }

    public function testResellerPriceIsNullWithoutSettlement(): void
    {
        $ticket = $this->makeTicket();
        $this->createSettlement(self::AGENT_A, self::ATTRACTION, self::TICKET, '25.00');

        $this->assertNull($this->helper->getTicketResellerPrice($ticket, $ticket->attraction, ['agent_id' => self::AGENT_B]));
    }

    public function testFixeddatePriceUsesTheAgentsGroupRate(): void
    {
        $ticket = $this->makeTicket('fdate');
        $this->createFdatesettlement(self::AGENT_A, 30);

        $priced = $this->helper->getFixeddateResellerPrice($ticket, $ticket->attraction, ['agent_id' => self::AGENT_A]);

        $this->assertEqualsWithDelta(30.0, (float)$priced->price, 0.001);
        $this->assertEqualsWithDelta(40.0, (float)$priced->retail_price, 0.001);
    }

    public function testFixeddatePriceUsesPerDateOverride(): void
    {
        $ticket = $this->makeTicket('fdate');
        $groupId = $this->createFdatesettlement(self::AGENT_A, 30);
        $fdateId = $this->createFixeddate('2026-12-01', null, 10);
        $this->createFixeddate('2026-12-02', null, 10);
        $this->insertRow('fdate_override_rates', ['fdatesettlement_id' => $groupId, 'fdate_id' => $fdateId, 'rate' => 27, 'rebate' => 0]);

        $onOverrideDay = $this->helper->getFixeddateResellerPrice(clone $ticket, $ticket->attraction, ['agent_id' => self::AGENT_A], '01 Dec 2026');
        $otherDay = $this->helper->getFixeddateResellerPrice(clone $ticket, $ticket->attraction, ['agent_id' => self::AGENT_A], '02 Dec 2026');

        $this->assertEqualsWithDelta(27.0, (float)$onOverrideDay->price, 0.001);
        $this->assertEqualsWithDelta(30.0, (float)$otherDay->price, 0.001);
    }

    public function testFixeddatePriceOverrideForTimedTicket(): void
    {
        $ticket = $this->makeTicket('fdatetime');
        $groupId = $this->createFdatesettlement(self::AGENT_A, 30);
        $fdateId = $this->createFixeddate('2026-12-01', '10:00:00', 10);
        $this->insertRow('fdate_override_rates', ['fdatesettlement_id' => $groupId, 'fdate_id' => $fdateId, 'rate' => 22.5, 'rebate' => 0]);

        $priced = $this->helper->getFixeddateResellerPrice($ticket, $ticket->attraction, ['agent_id' => self::AGENT_A], '01 Dec 2026 10:00');

        $this->assertEqualsWithDelta(22.5, (float)$priced->price, 0.001);
    }

    public function testFixeddatePriceWithoutAgentGroupKeepsPrice(): void
    {
        $ticket = $this->makeTicket('fdate');
        $this->createFdatesettlement(self::AGENT_A, 30);

        $priced = $this->helper->getFixeddateResellerPrice($ticket, $ticket->attraction, ['agent_id' => self::AGENT_B]);

        $this->assertEqualsWithDelta(40.0, (float)$priced->price, 0.001);
        $this->assertEqualsWithDelta(40.0, (float)$priced->retail_price, 0.001);
    }

    // ---- getFixeddate ----

    public function testGetFixeddate(): void
    {
        $ticket = $this->makeTicket('fdatetime');
        $id = $this->createFixeddate('2026-12-01', '10:00:00', 10);

        $this->assertSame($id, $this->helper->getFixeddate($ticket, ['agent_id' => self::AGENT_A], '01 Dec 2026 10:00')?->id);
        $this->assertNull($this->helper->getFixeddate($ticket, ['agent_id' => self::AGENT_A], '01 Dec 2026 12:00'));
        $this->assertNull($this->helper->getFixeddate($ticket, ['agent_id' => self::AGENT_A]));
    }
}
