<?php
namespace Psalm\Example\Plugin;

use PhpParser;
use Psalm\Checker;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Plugin\Hook\AfterExpressionAnalysisInterface;
use Psalm\StatementsSource;

/**
 * Prevents any assignment to a float value
 */
class PreventFloatAssignmentChecker implements AfterExpressionAnalysisInterface
{
    /**
     * Called after an expression has been checked
     *
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterExpressionAnalysis(
        PhpParser\Node\Expr $expr,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = []
    ): ?bool {
        if ($expr instanceof PhpParser\Node\Expr\Assign
            && ($expr_type = $statements_source->getNodeTypeProvider()->getType($expr->expr))
            && $expr_type->hasFloat()
        ) {
            if (\Psalm\IssueBuffer::accepts(
                new NoFloatAssignment(
                    'Don’t assign to floats',
                    new CodeLocation($statements_source, $expr)
                ),
                $statements_source->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        return null;
    }
}

class NoFloatAssignment extends \Psalm\Issue\PluginIssue {
}
