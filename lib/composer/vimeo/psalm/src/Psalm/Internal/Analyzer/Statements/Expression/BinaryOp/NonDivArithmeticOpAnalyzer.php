<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\Issue\StringIncrement;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Internal\Type\TypeCombination;
use function array_diff_key;
use function array_values;
use function preg_match;
use function strtolower;

/**
 * @internal
 */
class NonDivArithmeticOpAnalyzer
{
    public static function analyze(
        ?StatementsSource $statements_source,
        \Psalm\Internal\Provider\NodeDataProvider $nodes,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        ?Type\Union &$result_type = null,
        ?Context $context = null
    ) : void {
        $codebase = $statements_source ? $statements_source->getCodebase() : null;

        $left_type = $nodes->getType($left);
        $right_type = $nodes->getType($right);
        $config = Config::getInstance();

        if ($left_type && $left_type->isEmpty()) {
            $left_type = $right_type;
        } elseif ($right_type && $right_type->isEmpty()) {
            $right_type = $left_type;
        }

        if ($left_type && $right_type) {
            if ($left_type->isNull()) {
                if ($statements_source && IssueBuffer::accepts(
                    new NullOperand(
                        'Left operand cannot be null',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isNullable() && !$left_type->ignore_nullable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Left operand cannot be nullable, got ' . $left_type,
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isNull()) {
                if ($statements_source && IssueBuffer::accepts(
                    new NullOperand(
                        'Right operand cannot be null',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isNullable() && !$right_type->ignore_nullable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Right operand cannot be nullable, got ' . $right_type,
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($left_type->isFalse()) {
                if ($statements_source && IssueBuffer::accepts(
                    new FalseOperand(
                        'Left operand cannot be false',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isFalsable() && !$left_type->ignore_falsable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Left operand cannot be falsable, got ' . $left_type,
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isFalse()) {
                if ($statements_source && IssueBuffer::accepts(
                    new FalseOperand(
                        'Right operand cannot be false',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isFalsable() && !$right_type->ignore_falsable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Right operand cannot be falsable, got ' . $right_type,
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $invalid_left_messages = [];
            $invalid_right_messages = [];
            $has_valid_left_operand = false;
            $has_valid_right_operand = false;
            $has_string_increment = false;

            foreach ($left_type->getAtomicTypes() as $left_type_part) {
                foreach ($right_type->getAtomicTypes() as $right_type_part) {
                    $candidate_result_type = self::analyzeNonDivOperands(
                        $statements_source,
                        $codebase,
                        $config,
                        $context,
                        $left,
                        $right,
                        $parent,
                        $left_type_part,
                        $right_type_part,
                        $invalid_left_messages,
                        $invalid_right_messages,
                        $has_valid_left_operand,
                        $has_valid_right_operand,
                        $has_string_increment,
                        $result_type
                    );

                    if ($candidate_result_type) {
                        $result_type = $candidate_result_type;
                        return;
                    }
                }
            }

            if ($invalid_left_messages && $statements_source) {
                $first_left_message = $invalid_left_messages[0];

                if ($has_valid_left_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            $first_left_message,
                            new CodeLocation($statements_source, $left)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            $first_left_message,
                            new CodeLocation($statements_source, $left)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($invalid_right_messages && $statements_source) {
                $first_right_message = $invalid_right_messages[0];

                if ($has_valid_right_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            $first_right_message,
                            new CodeLocation($statements_source, $right)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            $first_right_message,
                            new CodeLocation($statements_source, $right)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($has_string_increment && $statements_source) {
                if (IssueBuffer::accepts(
                    new StringIncrement(
                        'Possibly unintended string increment',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    /**
     * @param  string[]        &$invalid_left_messages
     * @param  string[]        &$invalid_right_messages
     */
    private static function analyzeNonDivOperands(
        ?StatementsSource $statements_source,
        ?\Psalm\Codebase $codebase,
        Config $config,
        ?Context $context,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        Type\Atomic $left_type_part,
        Type\Atomic $right_type_part,
        array &$invalid_left_messages,
        array &$invalid_right_messages,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        bool &$has_string_increment,
        Type\Union &$result_type = null
    ): ?Type\Union {
        if ($left_type_part instanceof TLiteralInt
            && $right_type_part instanceof TLiteralInt
            && ($left instanceof PhpParser\Node\Scalar || $left instanceof PhpParser\Node\Expr\ConstFetch)
            && ($right instanceof PhpParser\Node\Scalar || $right instanceof PhpParser\Node\Expr\ConstFetch)
        ) {
            // time for some arithmetic!

            $calculated_type = null;

            if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Plus) {
                $calculated_type = Type::getInt(false, $left_type_part->value + $right_type_part->value);
            } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\Minus) {
                $calculated_type = Type::getInt(false, $left_type_part->value - $right_type_part->value);
            } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                $calculated_type = Type::getInt(false, $left_type_part->value % $right_type_part->value);
            } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mul) {
                $calculated_type = Type::getInt(false, $left_type_part->value * $right_type_part->value);
            } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\Pow) {
                $calculated_type = Type::getInt(false, $left_type_part->value ^ $right_type_part->value);
            } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr) {
                $calculated_type = Type::getInt(false, $left_type_part->value | $right_type_part->value);
            } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd) {
                $calculated_type = Type::getInt(false, $left_type_part->value & $right_type_part->value);
            }

            if ($calculated_type) {
                if ($result_type) {
                    $result_type = Type::combineUnionTypes(
                        $calculated_type,
                        $result_type
                    );
                } else {
                    $result_type = $calculated_type;
                }

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;

                return null;
            }
        }

        if ($left_type_part instanceof TNull || $right_type_part instanceof TNull) {
            // null case is handled above
            return null;
        }

        if ($left_type_part instanceof TFalse || $right_type_part instanceof TFalse) {
            // null case is handled above
            return null;
        }

        if ($left_type_part instanceof Type\Atomic\TString
            && $right_type_part instanceof TInt
            && $parent instanceof PhpParser\Node\Expr\PostInc
        ) {
            $has_string_increment = true;

            if (!$result_type) {
                $result_type = Type::getString();
            } else {
                $result_type = Type::combineUnionTypes(Type::getString(), $result_type);
            }

            $has_valid_left_operand = true;
            $has_valid_right_operand = true;

            return null;
        }

        if ($left_type_part instanceof TTemplateParam
            && $right_type_part instanceof TTemplateParam
        ) {
            $combined_type = Type::combineUnionTypes(
                $left_type_part->as,
                $right_type_part->as
            );

            $combined_atomic_types = array_values($combined_type->getAtomicTypes());

            if (\count($combined_atomic_types) <= 2) {
                $left_type_part = $combined_atomic_types[0];
                $right_type_part = $combined_atomic_types[1] ?? $combined_atomic_types[0];
            }
        }

        if ($left_type_part instanceof TMixed
            || $right_type_part instanceof TMixed
            || $left_type_part instanceof TTemplateParam
            || $right_type_part instanceof TTemplateParam
        ) {
            if ($statements_source && $codebase && $context) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_source->getFilePath() === $statements_source->getRootFilePath()
                    && (!(($source = $statements_source->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_source->getFilePath());
                }
            }

            if ($left_type_part instanceof TMixed || $left_type_part instanceof TTemplateParam) {
                if ($statements_source && IssueBuffer::accepts(
                    new MixedOperand(
                        'Left operand cannot be mixed',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if ($statements_source && IssueBuffer::accepts(
                    new MixedOperand(
                        'Right operand cannot be mixed',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($left_type_part instanceof TMixed
                && $left_type_part->from_loop_isset
                && $parent instanceof PhpParser\Node\Expr\AssignOp\Plus
                && !$right_type_part instanceof TMixed
            ) {
                $result_type_member = new Type\Union([$right_type_part]);

                if (!$result_type) {
                    $result_type = $result_type_member;
                } else {
                    $result_type = Type::combineUnionTypes($result_type_member, $result_type);
                }

                return null;
            }

            $from_loop_isset = (!($left_type_part instanceof TMixed) || $left_type_part->from_loop_isset)
                && (!($right_type_part instanceof TMixed) || $right_type_part->from_loop_isset);

            $result_type = Type::getMixed($from_loop_isset);

            return $result_type;
        }

        if ($statements_source && $codebase && $context) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_source->getFilePath() === $statements_source->getRootFilePath()
                && (!(($parent_source = $statements_source->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_source->getFilePath());
            }
        }

        if ($left_type_part instanceof TArray
            || $right_type_part instanceof TArray
            || $left_type_part instanceof TKeyedArray
            || $right_type_part instanceof TKeyedArray
            || $left_type_part instanceof TList
            || $right_type_part instanceof TList
        ) {
            if ((!$right_type_part instanceof TArray
                    && !$right_type_part instanceof TKeyedArray
                    && !$right_type_part instanceof TList)
                || (!$left_type_part instanceof TArray
                    && !$left_type_part instanceof TKeyedArray
                    && !$left_type_part instanceof TList)
            ) {
                if (!$left_type_part instanceof TArray
                    && !$left_type_part instanceof TKeyedArray
                    && !$left_type_part instanceof TList
                ) {
                    $invalid_left_messages[] = 'Cannot add an array to a non-array ' . $left_type_part;
                } else {
                    $invalid_right_messages[] = 'Cannot add an array to a non-array ' . $right_type_part;
                }

                if ($left_type_part instanceof TArray
                    || $left_type_part instanceof TKeyedArray
                    || $left_type_part instanceof TList
                ) {
                    $has_valid_left_operand = true;
                } elseif ($right_type_part instanceof TArray
                    || $right_type_part instanceof TKeyedArray
                    || $right_type_part instanceof TList
                ) {
                    $has_valid_right_operand = true;
                }

                $result_type = Type::getArray();

                return null;
            }

            $has_valid_right_operand = true;
            $has_valid_left_operand = true;

            if ($left_type_part instanceof TKeyedArray
                && $right_type_part instanceof TKeyedArray
            ) {
                $definitely_existing_mixed_right_properties = array_diff_key(
                    $right_type_part->properties,
                    $left_type_part->properties
                );

                $properties = $left_type_part->properties;

                foreach ($right_type_part->properties as $key => $type) {
                    if (!isset($properties[$key])) {
                        $properties[$key] = $type;
                    } elseif ($properties[$key]->possibly_undefined) {
                        $properties[$key] = Type::combineUnionTypes(
                            $properties[$key],
                            $type,
                            $codebase
                        );

                        $properties[$key]->possibly_undefined = $type->possibly_undefined;
                    }
                }

                if (!$left_type_part->sealed) {
                    foreach ($definitely_existing_mixed_right_properties as $key => $type) {
                        $properties[$key] = Type::combineUnionTypes(Type::getMixed(), $type);
                    }
                }

                $result_type_member = new Type\Union([new TKeyedArray($properties)]);
            } else {
                $result_type_member = TypeCombination::combineTypes(
                    [$left_type_part, $right_type_part],
                    $codebase,
                    true
                );
            }

            if (!$result_type) {
                $result_type = $result_type_member;
            } else {
                $result_type = Type::combineUnionTypes($result_type_member, $result_type, $codebase, true);
            }

            if ($left instanceof PhpParser\Node\Expr\ArrayDimFetch
                && $context
                && $statements_source instanceof StatementsAnalyzer
            ) {
                ArrayAssignmentAnalyzer::updateArrayType(
                    $statements_source,
                    $left,
                    $right,
                    $result_type,
                    $context
                );
            }

            return null;
        }

        if (($left_type_part instanceof TNamedObject && strtolower($left_type_part->value) === 'gmp')
            || ($right_type_part instanceof TNamedObject && strtolower($right_type_part->value) === 'gmp')
        ) {
            if ((($left_type_part instanceof TNamedObject
                        && strtolower($left_type_part->value) === 'gmp')
                    && (($right_type_part instanceof TNamedObject
                            && strtolower($right_type_part->value) === 'gmp')
                        || ($right_type_part->isNumericType() || $right_type_part instanceof TMixed)))
                || (($right_type_part instanceof TNamedObject
                        && strtolower($right_type_part->value) === 'gmp')
                    && (($left_type_part instanceof TNamedObject
                            && strtolower($left_type_part->value) === 'gmp')
                        || ($left_type_part->isNumericType() || $left_type_part instanceof TMixed)))
            ) {
                if (!$result_type) {
                    $result_type = new Type\Union([new TNamedObject('GMP')]);
                } else {
                    $result_type = Type::combineUnionTypes(
                        new Type\Union([new TNamedObject('GMP')]),
                        $result_type
                    );
                }
            } else {
                if ($statements_source && IssueBuffer::accepts(
                    new InvalidOperand(
                        'Cannot add GMP to non-numeric type',
                        new CodeLocation($statements_source, $parent)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            return null;
        }

        if ($left_type_part instanceof Type\Atomic\TLiteralString) {
            if (preg_match('/^\-?\d+$/', $left_type_part->value)) {
                $left_type_part = new Type\Atomic\TLiteralInt((int) $left_type_part->value);
            } elseif (preg_match('/^\-?\d?\.\d+$/', $left_type_part->value)) {
                $left_type_part = new Type\Atomic\TLiteralFloat((float) $left_type_part->value);
            }
        }

        if ($right_type_part instanceof Type\Atomic\TLiteralString) {
            if (preg_match('/^\-?\d+$/', $right_type_part->value)) {
                $right_type_part = new Type\Atomic\TLiteralInt((int) $right_type_part->value);
            } elseif (preg_match('/^\-?\d?\.\d+$/', $right_type_part->value)) {
                $right_type_part = new Type\Atomic\TLiteralFloat((float) $right_type_part->value);
            }
        }

        if ($left_type_part->isNumericType() || $right_type_part->isNumericType()) {
            if (($left_type_part instanceof TNumeric || $right_type_part instanceof TNumeric)
                && ($left_type_part->isNumericType() && $right_type_part->isNumericType())
            ) {
                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } elseif (!$result_type) {
                    $result_type = Type::getNumeric();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getNumeric(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if ($left_type_part instanceof TInt && $right_type_part instanceof TInt) {
                $left_is_positive = $left_type_part instanceof TPositiveInt
                    || ($left_type_part instanceof TLiteralInt && $left_type_part->value > 0);

                $right_is_positive = $right_type_part instanceof TPositiveInt
                    || ($right_type_part instanceof TLiteralInt && $right_type_part->value > 0);

                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Minus) {
                    $always_positive = false;
                } elseif ($left_is_positive && $right_is_positive) {
                    $always_positive = true;
                } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\Plus
                    && ($left_type_part instanceof TLiteralInt && $left_type_part->value === 0)
                    && $right_is_positive
                ) {
                    $always_positive = true;
                } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\Plus
                    && ($right_type_part instanceof TLiteralInt && $right_type_part->value === 0)
                    && $left_is_positive
                ) {
                    $always_positive = true;
                } else {
                    $always_positive = false;
                }

                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = $always_positive
                        ? new Type\Union([new Type\Atomic\TPositiveInt(), new TLiteralInt(0)])
                        : Type::getInt();
                } elseif (!$result_type) {
                    $result_type = $always_positive ? Type::getPositiveInt(true) : Type::getInt(true);
                } else {
                    $result_type = Type::combineUnionTypes(
                        $always_positive ? Type::getPositiveInt(true) : Type::getInt(true),
                        $result_type
                    );
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if ($left_type_part instanceof TFloat && $right_type_part instanceof TFloat) {
                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } elseif (!$result_type) {
                    $result_type = Type::getFloat();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if (($left_type_part instanceof TFloat && $right_type_part instanceof TInt)
                || ($left_type_part instanceof TInt && $right_type_part instanceof TFloat)
            ) {
                if ($config->strict_binary_operands) {
                    if ($statements_source && IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot add ints to floats',
                            new CodeLocation($statements_source, $parent)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } elseif (!$result_type) {
                    $result_type = Type::getFloat();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if ($left_type_part->isNumericType() && $right_type_part->isNumericType()) {
                if ($config->strict_binary_operands) {
                    if ($statements_source && IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot add numeric types together, please cast explicitly',
                            new CodeLocation($statements_source, $parent)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } else {
                    $result_type = new Type\Union([new Type\Atomic\TInt, new Type\Atomic\TFloat]);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if (!$left_type_part->isNumericType()) {
                $invalid_left_messages[] = 'Cannot perform a numeric operation with a non-numeric type '
                    . $left_type_part;
                $has_valid_right_operand = true;
            } else {
                $invalid_right_messages[] = 'Cannot perform a numeric operation with a non-numeric type '
                    . $right_type_part;
                $has_valid_left_operand = true;
            }
        } else {
            $invalid_left_messages[] =
                'Cannot perform a numeric operation with non-numeric types ' . $left_type_part
                    . ' and ' . $right_type_part;
        }

        return null;
    }
}
