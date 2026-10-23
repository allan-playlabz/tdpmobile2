<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Log\Log;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

/**
 * Simple console wrapper around Psy\Shell.
 */
class DeleteTempShell extends Shell
{

    /**
     * Start the shell and interactive console.
     *
     * @return int|null
     */
    public function main()
    {
      $files_age = 172800;
      $folders = [WWW_ROOT.'files/pdf',TMP.'merged_pdf', TMP.'reports'];
      foreach ($folders as $key => $folder_name) {
        $dir = new Folder($folder_name);
        $files = $dir->findRecursive('.*');
        if (!empty($files) && is_array($files)) {
          foreach ($files as $key => $_file) {
            $file = new File($_file);
            $last_changed = $file->lastChange();
            if ($last_changed && (time() - $last_changed > $files_age)) {
              // $file->delete();
              $this->out($file->delete());
            }
            
          }
        }
      }
    }

}
