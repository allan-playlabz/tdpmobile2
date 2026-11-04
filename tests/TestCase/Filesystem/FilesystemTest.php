<?php
declare(strict_types=1);

namespace App\Test\TestCase\Filesystem;

use App\Filesystem\File;
use App\Filesystem\Folder;
use Cake\TestSuite\TestCase;

/**
 * The File / Folder shims that replace the removed Cake\Filesystem classes.
 */
class FilesystemTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = TMP . 'fs-test-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . DS . '{,*/}*', GLOB_BRACE) ?: [] as $path) {
            is_file($path) && unlink($path);
        }
        foreach (array_reverse(glob($this->dir . DS . '*', GLOB_ONLYDIR) ?: []) as $path) {
            rmdir($path);
        }
        is_dir($this->dir) && rmdir($this->dir);
        parent::tearDown();
    }

    public function testFolderIsOnlyCreatedWhenAsked(): void
    {
        $this->assertNull((new Folder($this->dir))->path);
        $this->assertDirectoryDoesNotExist($this->dir);

        $folder = new Folder($this->dir . DS . 'nested', true);
        $this->assertDirectoryExists($this->dir . DS . 'nested');
        $this->assertSame(realpath($this->dir . DS . 'nested'), $folder->path);
    }

    public function testFindMatchesWholeNameCaseInsensitivelyFilesOnly(): void
    {
        $folder = new Folder($this->dir, true);
        foreach (['b.PDF', 'a.pdf', 'c.txt', 'x.pdf.bak'] as $name) {
            touch($this->dir . DS . $name);
        }
        mkdir($this->dir . DS . 'd.pdf');

        $this->assertSame(['a.pdf', 'b.PDF'], $folder->find('.*\.pdf', true));
        $this->assertCount(4, $folder->find());
        $this->assertSame([], (new Folder($this->dir . DS . 'missing'))->find());
    }

    public function testFile(): void
    {
        new Folder($this->dir, true);
        $path = $this->dir . DS . 'voucher.final.txt';
        $file = new File($path);
        $this->assertFalse($file->exists());
        $this->assertFalse($file->lastChange());
        $this->assertFalse($file->mime());
        $this->assertFalse($file->delete());

        file_put_contents($path, 'hello');
        $this->assertTrue($file->exists());
        $this->assertSame('voucher.final', $file->name());
        $this->assertSame('text/plain', $file->mime());
        $this->assertIsInt($file->lastChange());
        $this->assertTrue($file->delete());
        $this->assertFileDoesNotExist($path);
    }
}
