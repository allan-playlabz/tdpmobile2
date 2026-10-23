<?php
declare(strict_types=1);

namespace App\Filesystem;

use finfo;

/**
 * Minimal replacement for the removed Cake\Filesystem\File (CakePHP 3.x).
 *
 * Implements only what the app uses, with the 3.x semantics (methods return
 * false when the file doesn't exist; relative paths resolve against the
 * current working directory).
 */
class File
{
    /**
     * @param string $path Path to the file.
     */
    public function __construct(public string $path)
    {
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return is_file($this->path);
    }

    /**
     * File name without extension.
     *
     * @return string
     */
    public function name(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Last modification time.
     *
     * @return int|false
     */
    public function lastChange(): int|false
    {
        return $this->exists() ? filemtime($this->path) : false;
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        return $this->exists() && unlink($this->path);
    }

    /**
     * Mime type of the file.
     *
     * @return string|false
     */
    public function mime(): string|false
    {
        if (!$this->exists()) {
            return false;
        }

        return (new finfo(FILEINFO_MIME_TYPE))->file($this->path);
    }
}
