<?php
namespace Psalm\Plugin\Hook;

use PhpParser\Node;
use Psalm\Codebase;
use Psalm\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;

interface AfterClassLikeAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(
        Node\Stmt\ClassLike $stmt,
        ClassLikeStorage $classlike_storage,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    );
}
