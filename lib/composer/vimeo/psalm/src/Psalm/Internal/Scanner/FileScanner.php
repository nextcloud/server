<?php
namespace Psalm\Internal\Scanner;

use PhpParser;
use PhpParser\NodeTraverser;
use Psalm\Codebase;
use Psalm\FileSource;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Storage\FileStorage;
use Psalm\Internal\PhpVisitor\ReflectorVisitor;

/**
 * @internal
 * @psalm-consistent-constructor
 */
class FileScanner implements FileSource
{
    /**
     * @var string
     */
    public $file_path;

    /**
     * @var string
     */
    public $file_name;

    /**
     * @var bool
     */
    public $will_analyze;

    public function __construct(string $file_path, string $file_name, bool $will_analyze)
    {
        $this->file_path = $file_path;
        $this->file_name = $file_name;
        $this->will_analyze = $will_analyze;
    }

    public function scan(
        Codebase $codebase,
        FileStorage $file_storage,
        bool $storage_from_cache = false,
        ?Progress $progress = null
    ): void {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        if ((!$this->will_analyze || $file_storage->deep_scan)
            && $storage_from_cache
            && !$codebase->register_stub_files
        ) {
            return;
        }

        $stmts = $codebase->statements_provider->getStatementsForFile(
            $file_storage->file_path,
            $codebase->php_major_version . '.' . $codebase->php_minor_version,
            $progress
        );

        foreach ($stmts as $stmt) {
            if (!$stmt instanceof PhpParser\Node\Stmt\ClassLike
                && !$stmt instanceof PhpParser\Node\Stmt\Function_
                && !($stmt instanceof PhpParser\Node\Stmt\Expression
                    && $stmt->expr instanceof PhpParser\Node\Expr\Include_)
            ) {
                $file_storage->has_extra_statements = true;
                break;
            }
        }

        if ($this->will_analyze) {
            $progress->debug('Deep scanning ' . $file_storage->file_path . "\n");
        } else {
            $progress->debug('Scanning ' . $file_storage->file_path . "\n");
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new ReflectorVisitor($codebase, $file_storage, $this)
        );

        $traverser->traverse($stmts);

        $file_storage->deep_scan = $this->will_analyze;
    }

    public function getFilePath(): string
    {
        return $this->file_path;
    }

    public function getFileName(): string
    {
        return $this->file_name;
    }

    public function getRootFilePath(): string
    {
        return $this->file_path;
    }

    public function getRootFileName(): string
    {
        return $this->file_name;
    }

    public function getAliases(): \Psalm\Aliases
    {
        return new \Psalm\Aliases();
    }
}
