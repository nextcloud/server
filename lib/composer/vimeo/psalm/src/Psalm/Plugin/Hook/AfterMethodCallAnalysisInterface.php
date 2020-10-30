<?php
namespace Psalm\Plugin\Hook;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Type\Union;

interface AfterMethodCallAnalysisInterface
{
    /**
     * @param  MethodCall|StaticCall $expr
     * @param  FileManipulation[] $file_replacements
     */
    public static function afterMethodCallAnalysis(
        Expr $expr,
        string $method_id,
        string $appearing_method_id,
        string $declaring_method_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = [],
        Union &$return_type_candidate = null
    ): void;
}
