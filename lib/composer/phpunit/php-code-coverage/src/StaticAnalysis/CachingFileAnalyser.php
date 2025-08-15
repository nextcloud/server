<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_file;
use function md5;
use function serialize;
use function unserialize;
use SebastianBergmann\CodeCoverage\Util\Filesystem;
use SebastianBergmann\FileIterator\Facade as FileIteratorFacade;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @psalm-import-type LinesOfCodeType from \SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser
 */
final class CachingFileAnalyser implements FileAnalyser
{
    private static ?string $cacheVersion = null;
    private readonly string $directory;
    private readonly FileAnalyser $analyser;
    private readonly bool $useAnnotationsForIgnoringCode;
    private readonly bool $ignoreDeprecatedCode;
    private array $cache = [];

    public function __construct(string $directory, FileAnalyser $analyser, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode)
    {
        Filesystem::createDirectory($directory);

        $this->analyser                      = $analyser;
        $this->directory                     = $directory;
        $this->useAnnotationsForIgnoringCode = $useAnnotationsForIgnoringCode;
        $this->ignoreDeprecatedCode          = $ignoreDeprecatedCode;
    }

    public function classesIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['classesIn'];
    }

    public function traitsIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['traitsIn'];
    }

    public function functionsIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['functionsIn'];
    }

    /**
     * @psalm-return LinesOfCodeType
     */
    public function linesOfCodeFor(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['linesOfCodeFor'];
    }

    public function executableLinesIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['executableLinesIn'];
    }

    public function ignoredLinesFor(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['ignoredLinesFor'];
    }

    public function process(string $filename): void
    {
        $cache = $this->read($filename);

        if ($cache !== false) {
            $this->cache[$filename] = $cache;

            return;
        }

        $this->cache[$filename] = [
            'classesIn'         => $this->analyser->classesIn($filename),
            'traitsIn'          => $this->analyser->traitsIn($filename),
            'functionsIn'       => $this->analyser->functionsIn($filename),
            'linesOfCodeFor'    => $this->analyser->linesOfCodeFor($filename),
            'ignoredLinesFor'   => $this->analyser->ignoredLinesFor($filename),
            'executableLinesIn' => $this->analyser->executableLinesIn($filename),
        ];

        $this->write($filename, $this->cache[$filename]);
    }

    private function read(string $filename): array|false
    {
        $cacheFile = $this->cacheFile($filename);

        if (!is_file($cacheFile)) {
            return false;
        }

        return unserialize(
            file_get_contents($cacheFile),
            ['allowed_classes' => false],
        );
    }

    private function write(string $filename, array $data): void
    {
        file_put_contents(
            $this->cacheFile($filename),
            serialize($data),
        );
    }

    private function cacheFile(string $filename): string
    {
        $cacheKey = md5(
            implode(
                "\0",
                [
                    $filename,
                    file_get_contents($filename),
                    self::cacheVersion(),
                    $this->useAnnotationsForIgnoringCode,
                    $this->ignoreDeprecatedCode,
                ],
            ),
        );

        return $this->directory . DIRECTORY_SEPARATOR . $cacheKey;
    }

    private static function cacheVersion(): string
    {
        if (self::$cacheVersion !== null) {
            return self::$cacheVersion;
        }

        $buffer = [];

        foreach ((new FileIteratorFacade)->getFilesAsArray(__DIR__, '.php') as $file) {
            $buffer[] = $file;
            $buffer[] = file_get_contents($file);
        }

        self::$cacheVersion = md5(implode("\0", $buffer));

        return self::$cacheVersion;
    }
}
