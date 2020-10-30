<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\NewAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\Type;
use function in_array;
use function strtolower;
use function get_class;

/**
 * @internal
 */
class ExpressionAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
        bool $array_assignment = false,
        ?Context $global_context = null,
        bool $from_stmt = false
    ) : bool {
        $codebase = $statements_analyzer->getCodebase();

        if (self::handleExpression(
            $statements_analyzer,
            $stmt,
            $context,
            $array_assignment,
            $global_context,
            $from_stmt
        ) === false
        ) {
            return false;
        }

        if (!$context->inside_conditional
            && ($stmt instanceof PhpParser\Node\Expr\BinaryOp
                || $stmt instanceof PhpParser\Node\Expr\Instanceof_
                || $stmt instanceof PhpParser\Node\Expr\Assign
                || $stmt instanceof PhpParser\Node\Expr\BooleanNot
                || $stmt instanceof PhpParser\Node\Expr\Empty_
                || $stmt instanceof PhpParser\Node\Expr\Isset_
                || $stmt instanceof PhpParser\Node\Expr\FuncCall)
        ) {
            $assertions = $statements_analyzer->node_data->getAssertions($stmt);

            if ($assertions === null) {
                Expression\AssertionFinder::scrapeAssertions(
                    $stmt,
                    $context->self,
                    $statements_analyzer,
                    $codebase,
                    false,
                    true,
                    false
                );
            }
        }

        $plugin_classes = $codebase->config->after_expression_checks;

        if ($plugin_classes) {
            $file_manipulations = [];

            foreach ($plugin_classes as $plugin_fq_class_name) {
                if ($plugin_fq_class_name::afterExpressionAnalysis(
                    $stmt,
                    $context,
                    $statements_analyzer,
                    $codebase,
                    $file_manipulations
                ) === false) {
                    return false;
                }
            }

            if ($file_manipulations) {
                FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
            }
        }

        return true;
    }

    private static function handleExpression(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
        bool $array_assignment,
        ?Context $global_context,
        bool $from_stmt
    ) : bool {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            return Expression\Fetch\VariableFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                false,
                null,
                $array_assignment
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            $assignment_type = Expression\AssignmentAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $stmt->expr,
                null,
                $context,
                $stmt->getDocComment()
            );

            if ($assignment_type === false) {
                return false;
            }

            if (!$from_stmt) {
                $statements_analyzer->node_data->setType($stmt, $assignment_type);
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            return Expression\AssignmentAnalyzer::analyzeAssignmentOperation($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            return MethodCallAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            return StaticCallAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            Expression\Fetch\ConstFetchAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            $statements_analyzer->node_data->setType($stmt, Type::getString($stmt->value));

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            Expression\MagicConstAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $statements_analyzer->node_data->setType($stmt, Type::getInt(false, $stmt->value));

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $statements_analyzer->node_data->setType($stmt, Type::getFloat($stmt->value));

            return true;
        }


        if ($stmt instanceof PhpParser\Node\Expr\UnaryMinus || $stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            return Expression\UnaryPlusMinusAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            Expression\IssetAnalyzer::analyze($statements_analyzer, $stmt, $context);
            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            return Expression\Fetch\ClassConstFetchAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            return Expression\Fetch\InstancePropertyFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $array_assignment
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            return Expression\Fetch\StaticPropertyFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            return Expression\BitwiseNotAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            return Expression\BinaryOpAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                0,
                $from_stmt
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\PostInc
            || $stmt instanceof PhpParser\Node\Expr\PostDec
            || $stmt instanceof PhpParser\Node\Expr\PreInc
            || $stmt instanceof PhpParser\Node\Expr\PreDec
        ) {
            Expression\IncDecExpressionAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\New_) {
            return NewAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            return Expression\ArrayAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            return Expression\EncapsulatedStringAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            return FunctionCallAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            return Expression\TernaryAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            return Expression\BooleanNotAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            Expression\EmptyAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Closure
            || $stmt instanceof PhpParser\Node\Expr\ArrowFunction
        ) {
            ClosureAnalyzer::analyzeExpression($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return Expression\Fetch\ArrayFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast) {
            return Expression\CastAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            return Expression\CloneAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            return Expression\InstanceofAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            return Expression\ExitAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Include_) {
            return Expression\IncludeAnalyzer::analyze($statements_analyzer, $stmt, $context, $global_context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            Expression\EvalAnalyzer::analyze($statements_analyzer, $stmt, $context);
            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            return Expression\AssignmentAnalyzer::analyzeAssignmentRef($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            $context->error_suppressing = true;
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }
            $context->error_suppressing = false;

            $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($expr_type) {
                $statements_analyzer->node_data->setType($stmt, $expr_type);
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'Use of shell_exec',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // continue
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Print_) {
            $was_inside_call = $context->inside_call;
            $context->inside_call = true;
            if (Expression\PrintAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                return false;
            }
            $context->inside_call = $was_inside_call;

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            return Expression\YieldAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            return Expression\YieldFromAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Match_
            && $statements_analyzer->getCodebase()->php_major_version >= 8
        ) {
            return Expression\MatchAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Throw_
            && $statements_analyzer->getCodebase()->php_major_version >= 8
        ) {
            return ThrowAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if (($stmt instanceof PhpParser\Node\Expr\NullsafePropertyFetch
                || $stmt instanceof PhpParser\Node\Expr\NullsafeMethodCall)
            && $statements_analyzer->getCodebase()->php_major_version >= 8
        ) {
            return Expression\NullsafeAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Error) {
            // do nothing
            return true;
        }

        if (IssueBuffer::accepts(
            new UnrecognizedExpression(
                'Psalm does not understand ' . get_class($stmt),
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            ),
            $statements_analyzer->getSuppressedIssues()
        )) {
           // fall through
        }

        return false;
    }

    public static function isMock(string $fq_class_name): bool
    {
        return in_array(strtolower($fq_class_name), Config::getInstance()->getMockClasses(), true);
    }
}
