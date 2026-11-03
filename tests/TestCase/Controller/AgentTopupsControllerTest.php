<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\AppDataTrait;
use Aws\MockHandler;
use Aws\Result;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

/**
 * App\Controller\AgentTopupsController: top-up list/CSV, view and upload (masters only).
 */
class AgentTopupsControllerTest extends TestCase
{
    use AppDataTrait;
    use IntegrationTestTrait;

    private const TOPUP_A = '00000000-0000-4000-8000-0000000001a1';
    private const TOPUP_B = '00000000-0000-4000-8000-0000000001b1';

    /** A 1x1 transparent PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected array $fixtures = [
        'app.Agents',
        'app.AgentUsers',
        'app.Announcements',
        'app.Logs',
        'app.AgentTopups',
        'app.Admins',
    ];

    private array $awsBackup = [];

    /** @var list<string> */
    private array $tmpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAgentsAndUsers();
        $this->enableRetainFlashMessages();
        $this->awsBackup = (array)Configure::read('AWS');
        Configure::write('AWS.prefix', '');
    }

    protected function tearDown(): void
    {
        Configure::write('AWS', $this->awsBackup);
        foreach ($this->tmpFiles as $file) {
            if (is_file($file)) {
                @unlink($file); // the SDK may still hold the upload open on Windows
            }
        }
        foreach (glob(TMP . 'reports' . DS . '*Top Up Report*.csv') ?: [] as $csv) {
            @unlink($csv);
        }
        parent::tearDown();
    }

    private function createTopup(string $id, string $agentId, array $values = []): void
    {
        $this->insertRow('agent_topups', $values + [
            'id' => $id, 'agent_id' => $agentId, 'amount' => '100.00', 'image' => '', 'topup_date' => date('Y-m-d'),
            'remarks' => 'Bank transfer', 'status' => 'pending', 'created' => new DateTime(), 'modified' => new DateTime(),
        ]);
    }

    private function tmpFile(string $contents, string $ext): string
    {
        $path = tempnam(sys_get_temp_dir(), 'topup');
        file_put_contents($path, $contents);
        $this->tmpFiles[] = $path;

        return $path;
    }

    private function upload(string $contents, string $name, string $type): UploadedFile
    {
        $path = $this->tmpFile($contents, pathinfo($name, PATHINFO_EXTENSION));

        return new UploadedFile($path, strlen($contents), UPLOAD_ERR_OK, $name, $type);
    }

    public function testNonMastersAreRedirected(): void
    {
        $this->createTopup(self::TOPUP_A, self::AGENT_A);
        foreach ([self::NORMAL_A, self::VIEWER_A] as $userId) {
            foreach (['/agent-topups', '/agent-topups/add', '/agent-topups/view/' . self::TOPUP_A] as $url) {
                $this->loginAs($userId);
                $this->get($url);
                $this->assertRedirect('/', $url);
            }
        }
    }

    public function testIndexListsOnlyOwnTopups(): void
    {
        $this->createTopup(self::TOPUP_A, self::AGENT_A);
        $this->createTopup(self::TOPUP_B, self::AGENT_B);
        $this->loginAs(self::MASTER_A);
        $this->get('/agent-topups');

        $this->assertResponseOk();
        $ids = [];
        foreach ($this->viewVariable('agentTopups') as $topup) {
            $ids[] = $topup->id;
        }
        $this->assertSame([self::TOPUP_A], $ids);
    }

    public function testViewOwnTopup(): void
    {
        $this->createTopup(self::TOPUP_A, self::AGENT_A);
        $this->loginAs(self::MASTER_A);
        $this->get('/agent-topups/view/' . self::TOPUP_A);

        $this->assertResponseOk();
        $this->assertSame(self::TOPUP_A, $this->viewVariable('agentTopup')->id);
    }

    public function testViewOfAnotherAgentsTopupIs404(): void
    {
        $this->createTopup(self::TOPUP_B, self::AGENT_B);
        $this->loginAs(self::MASTER_A);
        $this->get('/agent-topups/view/' . self::TOPUP_B);

        $this->assertResponseCode(404);
    }

    public function testAddWithoutImageSavesNothing(): void
    {
        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/agent-topups/add', ['amount' => '100']);

        $this->assertResponseOk();
        $this->assertFlashMessage('Please provide an image and try again.');
        $this->assertSame(0, $this->countRows('agent_topups'));
    }

    public function testAddWithNonImageSavesNothing(): void
    {
        // Must never reach S3.
        Configure::write('AWS.handler', new MockHandler([]));
        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/agent-topups/add', [
            'amount' => '100',
            'image' => $this->upload('<html><script>alert(1)</script></html>', 'proof.png', 'image/png'),
        ]);

        $this->assertResponseOk();
        $this->assertFlashMessage('Invalid image.');
        $this->assertSame(0, $this->countRows('agent_topups'));
    }

    public function testAddWithPngSavesTopupAndUploadsToS3(): void
    {
        $s3 = new MockHandler([new Result([])]);
        Configure::write('AWS.handler', $s3);
        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/agent-topups/add', [
            'amount' => '150.50',
            'remarks' => 'March top up',
            'status' => 'approved', // ignored: new top-ups are always pending
            'agent_id' => self::AGENT_B, // ignored: always the logged-in agent
            'image' => $this->upload(base64_decode(self::PNG), 'proof.exe', 'application/octet-stream'),
        ]);

        $this->assertRedirectContains('/agent-topups');
        $row = $this->fetchRow('SELECT amount, image, status, LOWER(HEX(agent_id)) AS agent_hex FROM agent_topups');
        $this->assertNotNull($row);
        $this->assertSame(str_replace('-', '', self::AGENT_A), $row['agent_hex']);
        $this->assertSame('pending', $row['status']);
        $this->assertEqualsWithDelta(150.50, (float)$row['amount'], 0.001);
        $this->assertMatchesRegularExpression(
            '#^agent-topups/topup-images/agenttopups/' . self::AGENT_A . '/[0-9a-f-]{36}\.png$#',
            $row['image'],
            'server-side name with the detected extension'
        );

        $this->assertCount(0, $s3, 'the S3 PutObject call was made');
        $command = $s3->getLastCommand();
        $this->assertSame('PutObject', $command->getName());
        $this->assertSame('test-bucket', $command['Bucket']);
        $this->assertSame(substr($row['image'], strlen('agent-topups/topup-images/')), $command['Key']);
        $this->assertSame('image/png', $command['ContentType']);
    }

    public function testIndexPostDownloadsCsv(): void
    {
        $this->createTopup(self::TOPUP_A, self::AGENT_A, ['amount' => '123.45', 'remarks' => 'First']);
        $this->createTopup(self::TOPUP_B, self::AGENT_B, ['amount' => '999.00', 'remarks' => 'Other agent']);
        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/agent-topups');

        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 'attachment');
        $this->assertHeaderContains('Content-Disposition', 'Top Up Report');
        $this->assertResponseContains('Top Up Report');
        $this->assertResponseContains('123.45');
        $this->assertResponseContains('First');
        $this->assertResponseNotContains('Other agent');
    }

    public function testIndexPostWithoutTopupsRedirects(): void
    {
        $this->createTopup(self::TOPUP_B, self::AGENT_B);
        $this->loginAs(self::MASTER_A);
        $this->enableCsrfToken();
        $this->post('/agent-topups');

        $this->assertRedirectContains('/agent-topups');
        $this->assertFlashMessage('No records found!');
    }
}
