<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ApiCaller;
use Cake\Http\Client\Request;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;

class ApiCallerTest extends TestCase
{
    use HttpClientTrait;

    private const EMAIL = 'agent@example.com';
    private const USER_ID = '00000000-0000-4000-8000-000000000001';

    protected function setUp(): void
    {
        parent::setUp();
        Log::engine('debug')->clear();
    }

    /**
     * Checks the HMAC headers the backend API expects.
     */
    private function isSigned(Request $request): bool
    {
        $text = $request->getHeaderLine('X-CA-User') . "\n" . $request->getHeaderLine('Date') . "\n"
            . $request->getHeaderLine('X-Ca-SignatureMethod') . "\n" . $request->getHeaderLine('X-Ca-Nonce');
        $signature = base64_encode(hash_hmac('SHA256', $text, 'test-api-secret', true));

        return $request->getHeaderLine('Authorization') === 'Bearer ' . $signature
            && $request->getHeaderLine('X-CA-User') === self::EMAIL
            && $request->getHeaderLine('X-CA-UserSecret') === hash('SHA256', self::EMAIL . self::USER_ID)
            && $request->getHeaderLine('X-API-Version') === 'v1.10';
    }

    public function testGetIsSignedAndSendsQuery(): void
    {
        $this->mockClientGet('http://api.test/order/details/*', $this->newClientResponse(200, [], '{"status":"1000"}'), [
            'match' => fn (Request $request) => $this->isSigned($request)
                && str_contains((string)$request->getUri(), 'order_ref_id=ABC'),
        ]);

        $response = (new ApiCaller())->get('/order/details', ['order_ref_id' => 'ABC'], self::EMAIL, self::USER_ID);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => '1000'], $response->getJson());
    }

    public function testPostIsSigned(): void
    {
        $this->mockClientPost('http://api.test/order/create', $this->newClientResponse(201, [], '{}'), [
            'match' => fn (Request $request) => $this->isSigned($request),
        ]);

        $response = (new ApiCaller())->post('/order/create', ['a' => 1], self::EMAIL, self::USER_ID);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testSignedHeadersAreNotLogged(): void
    {
        $this->mockClientGet('http://api.test/ping/*', $this->newClientResponse(200, [], 'pong'));

        (new ApiCaller())->get('/ping', [], self::EMAIL, self::USER_ID);

        $log = implode("\n", Log::engine('debug')->read());
        $this->assertStringContainsString('[redacted]', $log);
        $this->assertStringNotContainsString('Bearer ', $log);
        $this->assertStringContainsString('pong', $log);
    }

    public function testUnreachableApiReturns500Response(): void
    {
        $response = (new ApiCaller())->get('/not-mocked', [], self::EMAIL, self::USER_ID);

        $this->assertSame(500, $response->getStatusCode());
    }
}
