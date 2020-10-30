<?php
namespace Psalm\Internal\Provider;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function in_array;
use function is_dir;
use function strtolower;

class FileProvider
{
    /**
     * @var array<string, string>
     */
    protected $temp_files = [];

    /**
     * @var array<string, string>
     */
    protected $open_files = [];

    public function getContents(string $file_path, bool $go_to_source = false): string
    {
        if (!$go_to_source && isset($this->temp_files[strtolower($file_path)])) {
            return $this->temp_files[strtolower($file_path)];
        }

        if (isset($this->open_files[strtolower($file_path)])) {
            return $this->open_files[strtolower($file_path)];
        }

        if (!file_exists($file_path)) {
            throw new \UnexpectedValueException('File ' . $file_path . ' should exist to get contents');
        }

        if (is_dir($file_path)) {
            throw new \UnexpectedValueException('File ' . $file_path . ' is a directory');
        }

        return (string)file_get_contents($file_path);
    }

    public function setContents(string $file_path, string $file_contents): void
    {
        if (isset($this->open_files[strtolower($file_path)])) {
            $this->open_files[strtolower($file_path)] = $file_contents;
        }

        if (isset($this->temp_files[strtolower($file_path)])) {
            $this->temp_files[strtolower($file_path)] = $file_contents;
        }

        file_put_contents($file_path, $file_contents);
    }

    public function setOpenContents(string $file_path, string $file_contents): void
    {
        if (isset($this->open_files[strtolower($file_path)])) {
            $this->open_files[strtolower($file_path)] = $file_contents;
        }
    }

    public function getModifiedTime(string $file_path): int
    {
        if (!file_exists($file_path)) {
            throw new \UnexpectedValueException('File should exist to get modified time');
        }

        return (int)filemtime($file_path);
    }

    public function addTemporaryFileChanges(string $file_path, string $new_content): void
    {
        $this->temp_files[strtolower($file_path)] = $new_content;
    }

    public function removeTemporaryFileChanges(string $file_path): void
    {
        unset($this->temp_files[strtolower($file_path)]);
    }

    public function openFile(string $file_path): void
    {
        $this->open_files[strtolower($file_path)] = $this->getContents($file_path, true);
    }

    public function isOpen(string $file_path): bool
    {
        return isset($this->temp_files[strtolower($file_path)]) || isset($this->open_files[strtolower($file_path)]);
    }

    public function closeFile(string $file_path): void
    {
        unset($this->temp_files[strtolower($file_path)], $this->open_files[strtolower($file_path)]);
    }

    public function fileExists(string $file_path): bool
    {
        return file_exists($file_path);
    }

    /**
     * @param array<string> $file_extensions
     *
     * @return list<string>
     */
    public function getFilesInDir(string $dir_path, array $file_extensions): array
    {
        $file_paths = [];

        /** @var \RecursiveDirectoryIterator */
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir_path));
        $iterator->rewind();

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions, true)) {
                    $file_paths[] = (string)$iterator->getRealPath();
                }
            }

            $iterator->next();
        }

        return $file_paths;
    }
}
