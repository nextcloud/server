<?php
namespace Psalm\Examples\Template;

use PhpParser;
use Psalm;
use Psalm\Checker\CommentChecker;
use Psalm\Codebase;
use Psalm\DocComment;
use Psalm\Progress\Progress;
use Psalm\Storage\FileStorage;

class TemplateScanner extends Psalm\Internal\Scanner\FileScanner
{
    const VIEW_CLASS = 'Your\\View\\Class';

    /**
     * @param bool $storage_from_cache
     */
    public function scan(
        Codebase $codebase,
        FileStorage $file_storage,
        $storage_from_cache = false,
        ?Progress $progress = null
    ): void {
        $stmts = $codebase->statements_provider->getStatementsForFile(
            $file_storage->file_path,
            '7.4',
            $progress
        );

        if ($stmts === []) {
            return;
        }

        $first_stmt = $stmts[0];

        if (($first_stmt instanceof PhpParser\Node\Stmt\Nop) && ($doc_comment = $first_stmt->getDocComment())) {
            $comment_block = DocComment::parsePreservingLength($doc_comment);

            if (isset($comment_block->tags['variablesfrom'])) {
                $variables_from = trim($comment_block->tags['variablesfrom'][0]);

                $first_line_regex = '/([A-Za-z\\\0-9]+::[a-z_A-Z]+)(\s+weak)?/';

                $matches = [];

                if (!preg_match($first_line_regex, $variables_from, $matches)) {
                    throw new \InvalidArgumentException('Could not interpret doc comment correctly');
                }

                [$fq_class_name] = explode('::', $matches[1]);

                $codebase->scanner->queueClassLikeForScanning(
                    $fq_class_name,
                    true
                );
            }
        }

        $codebase->scanner->queueClassLikeForScanning(self::VIEW_CLASS);

        parent::scan($codebase, $file_storage, $storage_from_cache, $progress);
    }
}
