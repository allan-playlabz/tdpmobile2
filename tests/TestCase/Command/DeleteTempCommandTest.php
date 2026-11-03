<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * `bin/cake delete_temp` deletes files older than two days under WWW_ROOT/files/pdf,
 * TMP/merged_pdf and TMP/reports.
 *
 * Running the command also cleans up any real stale files in those folders (that's its job);
 * the test only creates and checks its own uniquely named directory under TMP/reports.
 */
class DeleteTempCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = TMP . 'reports' . DS . 'phpunit-delete-temp-' . bin2hex(random_bytes(6));
        mkdir($this->dir . DS . 'nested', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (['old.csv', 'recent.csv', 'just-under.csv', 'nested' . DS . 'old.pdf', 'nested' . DS . 'new.pdf'] as $file) {
            if (is_file($this->dir . DS . $file)) {
                unlink($this->dir . DS . $file);
            }
        }
        foreach ([$this->dir . DS . 'nested', $this->dir] as $dir) {
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
        parent::tearDown();
    }

    private function createFile(string $name, int $ageSeconds): string
    {
        $path = $this->dir . DS . $name;
        file_put_contents($path, 'x');
        touch($path, time() - $ageSeconds);
        clearstatcache(true, $path);

        return $path;
    }

    public function testDeletesFilesOlderThanTwoDays(): void
    {
        $old = $this->createFile('old.csv', 3 * 86400);
        $oldNested = $this->createFile('nested' . DS . 'old.pdf', 10 * 86400);
        $recent = $this->createFile('recent.csv', 3600);
        $justUnder = $this->createFile('just-under.csv', 2 * 86400 - 600);
        $newNested = $this->createFile('nested' . DS . 'new.pdf', 0);

        $this->exec('delete_temp');

        $this->assertExitCode(Command::CODE_SUCCESS);
        clearstatcache();
        $this->assertFileDoesNotExist($old);
        $this->assertFileDoesNotExist($oldNested);
        $this->assertFileExists($recent);
        $this->assertFileExists($justUnder);
        $this->assertFileExists($newNested);
        // Directories are kept.
        $this->assertDirectoryExists($this->dir . DS . 'nested');
    }
}
