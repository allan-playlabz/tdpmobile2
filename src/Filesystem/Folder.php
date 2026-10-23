<?php
declare(strict_types=1);

namespace App\Filesystem;

/**
 * Minimal replacement for the removed Cake\Filesystem\Folder (CakePHP 3.x).
 *
 * Implements only what the app uses, with the 3.x semantics:
 * - `new Folder($path, $create, $mode)` creates the directory when asked;
 * - `$path` is null when the directory does not exist;
 * - `find($pattern)` returns matching file names (not paths), case-insensitive, unsorted.
 */
class Folder
{
    /**
     * Absolute path of the folder, or null when it doesn't exist.
     *
     * @var string|null
     */
    public ?string $path = null;

    /**
     * @param string|null $path Folder path.
     * @param bool $create Create the folder if it doesn't exist.
     * @param int|false $mode Mode (CHMOD) to apply when creating.
     */
    public function __construct(?string $path = null, bool $create = false, int|false $mode = false)
    {
        if ($path === null) {
            return;
        }
        if (!is_dir($path) && $create) {
            mkdir($path, $mode === false ? 0755 : $mode, true);
        }
        if (is_dir($path)) {
            $this->path = rtrim((string)realpath($path), DIRECTORY_SEPARATOR);
        }
    }

    /**
     * Returns the names of files in this folder matching the regular expression.
     *
     * @param string $regexpPattern Pattern without delimiters, matched against the whole file name.
     * @param bool $sort Sort the result by name.
     * @return list<string>
     */
    public function find(string $regexpPattern = '.*', bool $sort = false): array
    {
        if ($this->path === null) {
            return [];
        }
        $files = [];
        foreach (scandir($this->path) ?: [] as $item) {
            if ($item === '.' || $item === '..' || !is_file($this->path . DIRECTORY_SEPARATOR . $item)) {
                continue;
            }
            $files[] = $item;
        }
        if ($sort) {
            sort($files);
        }

        return array_values(preg_grep('/^' . $regexpPattern . '$/i', $files));
    }
}
