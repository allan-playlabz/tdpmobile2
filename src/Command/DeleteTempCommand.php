<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Deletes generated PDFs and reports older than two days.
 */
class DeleteTempCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Delete generated PDF/report files older than 2 days.';
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
      $files_age = 172800;
      $folders = [WWW_ROOT . 'files/pdf', TMP . 'merged_pdf', TMP . 'reports'];
      foreach ($folders as $folder_name) {
        if (!is_dir($folder_name)) {
          continue;
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder_name, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($files as $_file) {
          if (!$_file->isFile()) {
            continue;
          }
          $last_changed = $_file->getMTime();
          if ($last_changed && (time() - $last_changed > $files_age)) {
            $io->out(unlink($_file->getPathname()) ? '1' : '');
          }
        }
      }

      return static::CODE_SUCCESS;
    }
}
