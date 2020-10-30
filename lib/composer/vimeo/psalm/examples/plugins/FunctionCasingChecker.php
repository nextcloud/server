<?php
namespace Psalm\Example\Plugin;

use PhpParser;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Psalm\Checker;
use Psalm\Checker\StatementsChecker;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Plugin\Hook\AfterFunctionCallAnalysisInterface;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Type\Union;

/**
 * Prevents any assignment to a float value
 */
class FunctionCasingChecker implements AfterFunctionCallAnalysisInterface, AfterMethodCallAnalysisInterface
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
    ): void {
        if (!$expr->name instanceof PhpParser\Node\Identifier) {
            return;
        }

        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $method_id = new \Psalm\Internal\MethodIdentifier(...explode('::', $declaring_method_id));
            $function_storage = $codebase->methods->getStorage($method_id);

            if ($function_storage->cased_name === '__call') {
                return;
            }

            if ($function_storage->cased_name === '__callStatic') {
                return;
            }

            if ($function_storage->cased_name !== (string)$expr->name) {
                if (\Psalm\IssueBuffer::accepts(
                    new IncorrectFunctionCasing(
                        'Function is incorrectly cased, expecting ' . $function_storage->cased_name,
                        new CodeLocation($statements_source, $expr->name)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        } catch (\Exception $e) {
            // can throw if storage is missing
        }
    }

    /**
     * @param non-empty-string $function_id
     * @param  FileManipulation[] $file_replacements
     */
    public static function afterFunctionCallAnalysis(
        FuncCall $expr,
        string $function_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        Union $return_type_candidate,
        array &$file_replacements
    ): void {
        if ($expr->name instanceof PhpParser\Node\Expr) {
            return;
        }

        try {
            $function_storage = $codebase->functions->getStorage(
                $statements_source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer
                    ? $statements_source
                    : null,
                strtolower($function_id)
            );

            if (!$function_storage->cased_name) {
                return;
            }

            $function_name_parts = explode('\\', $function_storage->cased_name);

            if (end($function_name_parts) !== end($expr->name->parts)) {
                if (\Psalm\IssueBuffer::accepts(
                    new IncorrectFunctionCasing(
                        'Function is incorrectly cased, expecting ' . $function_storage->cased_name,
                        new CodeLocation($statements_source, $expr->name)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        } catch (\Exception $e) {
            // can throw if storage is missing
        }
    }
}

class IncorrectFunctionCasing extends \Psalm\Issue\PluginIssue {
}
