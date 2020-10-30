<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOpAnalyzer;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Atomic\TFloat;

/**
 * @internal
 */
class NonComparisonOpAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
        Context $context
    ) : void {
        $stmt_left_type = $statements_analyzer->node_data->getType($stmt->left);
        $stmt_right_type = $statements_analyzer->node_data->getType($stmt->right);

        if (!$stmt_left_type || !$stmt_right_type) {
            return;
        }

        if (($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
            )
            && $stmt_left_type->hasString()
            && $stmt_right_type->hasString()
        ) {
            $stmt_type = Type::getString();

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
            || (($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft
                    || $stmt instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight
                )
            )
        ) {
            NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->left,
                $stmt->right,
                $stmt,
                $result_type,
                $context
            );

            if (!$result_type) {
                $result_type = new Type\Union([new Type\Atomic\TInt(), new Type\Atomic\TFloat()]);
            }

            $statements_analyzer->node_data->setType($stmt, $result_type);

            BinaryOpAnalyzer::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->left,
                $stmt->right,
                'nondivop'
            );

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor) {
            if ($stmt_left_type->hasBool() || $stmt_right_type->hasBool()) {
                $statements_analyzer->node_data->setType($stmt, Type::getInt());
            }

            BinaryOpAnalyzer::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->left,
                $stmt->right,
                'xor'
            );

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor) {
            if ($stmt_left_type->hasBool() || $stmt_right_type->hasBool()) {
                $statements_analyzer->node_data->setType($stmt, Type::getBool());
            }

            BinaryOpAnalyzer::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->left,
                $stmt->right,
                'xor'
            );

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div) {
            NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->left,
                $stmt->right,
                $stmt,
                $result_type,
                $context
            );

            if (!$result_type) {
                $result_type = new Type\Union([new Type\Atomic\TInt(), new Type\Atomic\TFloat()]);
            } elseif ($result_type->hasInt()) {
                $result_type->addType(new TFloat);
            }

            $statements_analyzer->node_data->setType($stmt, $result_type);

            BinaryOpAnalyzer::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->left,
                $stmt->right,
                'div'
            );

            return;
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr) {
            NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->left,
                $stmt->right,
                $stmt,
                $result_type,
                $context
            );

            BinaryOpAnalyzer::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->left,
                $stmt->right,
                'or'
            );
        }
    }
}
