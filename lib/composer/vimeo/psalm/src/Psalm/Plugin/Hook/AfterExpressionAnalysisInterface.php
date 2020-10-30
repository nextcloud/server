<?php
namespace Psalm\Plugin\Hook;

use PhpParser\Node\Expr;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;

interface AfterExpressionAnalysisInterface
{
    /**
     * Called after an expression has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterExpressionAnalysis(
        Expr $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ): ?bool;
}
