<?php
namespace Psalm\Example\Plugin;

use PhpParser;
use Psalm\Checker;
use Psalm\Checker\StatementsChecker;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Plugin\Hook\AfterExpressionAnalysisInterface;
use Psalm\StatementsSource;

class StringChecker implements AfterExpressionAnalysisInterface
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
        if ($expr instanceof PhpParser\Node\Scalar\String_) {
            $class_or_class_method = '/^\\\?Psalm(\\\[A-Z][A-Za-z0-9]+)+(::[A-Za-z0-9]+)?$/';

            if (strpos($statements_source->getFileName(), 'base/DefinitionManager.php') === false
                && strpos($expr->value, 'TestController') === false
                && preg_match($class_or_class_method, $expr->value)
            ) {
                $absolute_class = preg_split('/[:]/', $expr->value)[0];

                if (\Psalm\IssueBuffer::accepts(
                    new \Psalm\Issue\InvalidClass(
                        'Use ::class constants when representing class names',
                        new CodeLocation($statements_source, $expr),
                        $absolute_class
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        } elseif ($expr instanceof PhpParser\Node\Expr\BinaryOp\Concat
            && $expr->left instanceof PhpParser\Node\Expr\ClassConstFetch
            && $expr->left->class instanceof PhpParser\Node\Name
            && $expr->left->name instanceof PhpParser\Node\Identifier
            && strtolower($expr->left->name->name) === 'class'
            && !in_array(strtolower($expr->left->class->parts[0]), ['self', 'static', 'parent'])
            && $expr->right instanceof PhpParser\Node\Scalar\String_
            && preg_match('/^::[A-Za-z0-9]+$/', $expr->right->value)
        ) {
            $method_id = ((string) $expr->left->class->getAttribute('resolvedName')) . $expr->right->value;

            $appearing_method_id = $codebase->getAppearingMethodId($method_id);

            if (!$appearing_method_id) {
                if (\Psalm\IssueBuffer::accepts(
                    new \Psalm\Issue\UndefinedMethod(
                        'Method ' . $method_id . ' does not exist',
                        new CodeLocation($statements_source, $expr),
                        $method_id
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    return false;
                }

                return null;
            }
        }

        return null;
    }
}
