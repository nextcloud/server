<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;

class BitwiseNotAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BitwiseNot $stmt,
        Context $context
    ) : bool {
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if (!($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr))) {
            $statements_analyzer->node_data->setType($stmt, new Type\Union([new TInt(), new TString()]));
        } elseif ($stmt_expr_type->isMixed()) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        } else {
            $acceptable_types = [];
            $unacceptable_type = null;
            $has_valid_operand = false;

            foreach ($stmt_expr_type->getAtomicTypes() as $type_string => $type_part) {
                if ($type_part instanceof TInt || $type_part instanceof TString) {
                    if ($type_part instanceof Type\Atomic\TLiteralInt) {
                        $type_part->value = ~$type_part->value;
                    } elseif ($type_part instanceof Type\Atomic\TLiteralString) {
                        $type_part->value = ~$type_part->value;
                    }

                    $acceptable_types[] = $type_part;
                    $has_valid_operand = true;
                } elseif ($type_part instanceof TFloat) {
                    $type_part = ($type_part instanceof Type\Atomic\TLiteralFloat) ?
                        new Type\Atomic\TLiteralInt(~$type_part->value) :
                        new TInt;

                    $stmt_expr_type->removeType($type_string);
                    $stmt_expr_type->addType($type_part);

                    $acceptable_types[] = $type_part;
                    $has_valid_operand = true;
                } elseif (!$unacceptable_type) {
                    $unacceptable_type = $type_part;
                }
            }

            if ($unacceptable_type || !$acceptable_types) {
                $message = 'Cannot negate a non-numeric non-string type ' . $unacceptable_type;
                if ($has_valid_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            $message,
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            $message,
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            } else {
                $statements_analyzer->node_data->setType($stmt, new Type\Union($acceptable_types));
            }
        }

        return true;
    }
}
