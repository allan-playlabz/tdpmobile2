<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\PaymentResponse;
use App\Model\Table\PaymentResponsesTable;
use App\Test\TestCase\Support\AppDataTrait;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PaymentResponsesTableTest extends TestCase
{
    use AppDataTrait;
    use ValidationTestTrait;

    protected array $fixtures = ['app.PaymentResponses', 'app.Agents'];

    private PaymentResponsesTable $PaymentResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->PaymentResponses = $this->getTableLocator()->get('PaymentResponses');
        $this->errorLog()->clear();
    }

    private function errorLog(): ArrayLog
    {
        /** @var \Cake\Log\Engine\ArrayLog $engine */
        $engine = Log::engine('error');

        return $engine;
    }

    protected function validData(): array
    {
        return ['provider' => '2C2P', 'data' => '{"a":1}'];
    }

    public static function validationProvider(): array
    {
        return [
            'valid' => [[], []],
            'missing provider' => [['provider' => self::UNSET], ['provider' => ['_required']]],
            'long provider' => [['provider' => str_repeat('p', 46)], ['provider' => ['maxLength']]],
            'missing data' => [['data' => self::UNSET], ['data' => ['_required']]],
            'empty data' => [['data' => ''], ['data' => ['_empty']]],
        ];
    }

    #[DataProvider('validationProvider')]
    public function testValidation(array $changes, array $expected): void
    {
        $this->assertValidation($this->PaymentResponses, $changes, $expected);
    }

    public function testCreateStoresJsonEncodedData(): void
    {
        $response = $this->PaymentResponses->create('Stripe', ['id' => 'evt_1', 'amount' => 1050]);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertNotEmpty($response->id);
        $row = $this->fetchRow('SELECT provider, data, order_ref_id FROM payment_responses WHERE id = ?', [$response->id]);
        $this->assertSame(['provider' => 'Stripe', 'data' => '{"id":"evt_1","amount":1050}', 'order_ref_id' => null], $row);
    }

    public function testCreateInvalidLogsErrorsAndReturnsFalse(): void
    {
        $this->assertFalse($this->PaymentResponses->create('', ['id' => 'evt_1']));

        $this->assertSame(0, $this->countRows('payment_responses'));
        $logs = implode("\n", $this->errorLog()->read());
        $this->assertStringContainsString('provider', $logs);
    }

    public function testSetOrderRefId(): void
    {
        $response = $this->PaymentResponses->create('Paypal', ['id' => 'PAY-1']);

        $this->assertTrue($this->PaymentResponses->setOrderRefId($response, 'REF0000000000001'));

        $this->assertSame('REF0000000000001', $this->fetchRow('SELECT order_ref_id FROM payment_responses WHERE id = ?', [$response->id])['order_ref_id']);
    }
}
