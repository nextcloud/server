<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function strtolower;
use function strlen;

/**
 * @internal
 */
class ConcatAnalyzer
{
    /**
     * @param  Type\Union|null       &$result_type
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        Context $context,
        Type\Union &$result_type = null
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $left_type = $statements_analyzer->node_data->getType($left);
        $right_type = $statements_analyzer->node_data->getType($right);
        $config = Config::getInstance();

        if ($left_type && $right_type) {
            $result_type = Type::getString();

            if ($left_type->hasMixed() || $right_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                if ($left_type->hasMixed()) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Right operand cannot be mixed',
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                return;
            }

            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
            }

            if ($left_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $left_type,
                        new CodeLocation($statements_analyzer->getSource(), $left)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullOperand(
                        'Cannot concatenate with a ' . $right_type,
                        new CodeLocation($statements_analyzer->getSource(), $right)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isFalse()) {
                if (IssueBuffer::accepts(
                    new FalseOperand(
                        'Cannot concatenate with a ' . $left_type,
                        new CodeLocation($statements_analyzer->getSource(), $left)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isFalse()) {
                if (IssueBuffer::accepts(
                    new FalseOperand(
                        'Cannot concatenate with a ' . $right_type,
                        new CodeLocation($statements_analyzer->getSource(), $right)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isNullable() && !$left_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Cannot concatenate with a possibly null ' . $left_type,
                        new CodeLocation($statements_analyzer->getSource(), $left)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isNullable() && !$right_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Cannot concatenate with a possibly null ' . $right_type,
                        new CodeLocation($statements_analyzer->getSource(), $right)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($left_type->isFalsable() && !$left_type->ignore_falsable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Cannot concatenate with a possibly false ' . $left_type,
                        new CodeLocation($statements_analyzer->getSource(), $left)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isFalsable() && !$right_type->ignore_falsable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Cannot concatenate with a possibly false ' . $right_type,
                        new CodeLocation($statements_analyzer->getSource(), $right)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $left_type_match = true;
            $right_type_match = true;

            $has_valid_left_operand = false;
            $has_valid_right_operand = false;

            $left_comparison_result = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();
            $right_comparison_result = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

            foreach ($left_type->getAtomicTypes() as $left_type_part) {
                if ($left_type_part instanceof Type\Atomic\TTemplateParam) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }

                if ($left_type_part instanceof Type\Atomic\TNull || $left_type_part instanceof Type\Atomic\TFalse) {
                    continue;
                }

                $left_type_part_match = AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $left_type_part,
                    new Type\Atomic\TString,
                    false,
                    false,
                    $left_comparison_result
                );

                $left_type_match = $left_type_match && $left_type_part_match;

                $has_valid_left_operand = $has_valid_left_operand || $left_type_part_match;

                if ($left_comparison_result->to_string_cast && $config->strict_binary_operands) {
                    if (IssueBuffer::accepts(
                        new ImplicitToStringCast(
                            'Left side of concat op expects string, '
                                . '\'' . $left_type . '\' provided with a __toString method',
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                foreach ($left_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNamedObject) {
                        $to_string_method_id = new \Psalm\Internal\MethodIdentifier(
                            $atomic_type->value,
                            '__tostring'
                        );

                        if ($codebase->methods->methodExists(
                            $to_string_method_id,
                            $context->calling_method_id,
                            $codebase->collect_locations
                                ? new CodeLocation($statements_analyzer->getSource(), $left)
                                : null,
                            !$context->collect_initializations
                                && !$context->collect_mutations
                                ? $statements_analyzer
                                : null,
                            $statements_analyzer->getFilePath()
                        )) {
                            try {
                                $storage = $codebase->methods->getStorage($to_string_method_id);
                            } catch (\UnexpectedValueException $e) {
                                continue;
                            }

                            if ($context->mutation_free && !$storage->mutation_free) {
                                if (IssueBuffer::accepts(
                                    new ImpureMethodCall(
                                        'Cannot call a possibly-mutating method '
                                            . $atomic_type->value . '::__toString from a pure context',
                                        new CodeLocation($statements_analyzer, $left)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            } elseif ($statements_analyzer->getSource()
                                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                                && $statements_analyzer->getSource()->track_mutations
                            ) {
                                $statements_analyzer->getSource()->inferred_has_mutation = true;
                                $statements_analyzer->getSource()->inferred_impure = true;
                            }
                        }
                    }
                }
            }

            foreach ($right_type->getAtomicTypes() as $right_type_part) {
                if ($right_type_part instanceof Type\Atomic\TTemplateParam) {
                    if (IssueBuffer::accepts(
                        new MixedOperand(
                            'Right operand cannot be a template param',
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }

                if ($right_type_part instanceof Type\Atomic\TNull || $right_type_part instanceof Type\Atomic\TFalse) {
                    continue;
                }

                $right_type_part_match = AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $right_type_part,
                    new Type\Atomic\TString,
                    false,
                    false,
                    $right_comparison_result
                );

                $right_type_match = $right_type_match && $right_type_part_match;

                $has_valid_right_operand = $has_valid_right_operand || $right_type_part_match;

                if ($right_comparison_result->to_string_cast && $config->strict_binary_operands) {
                    if (IssueBuffer::accepts(
                        new ImplicitToStringCast(
                            'Right side of concat op expects string, '
                                . '\'' . $right_type . '\' provided with a __toString method',
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                foreach ($right_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNamedObject) {
                        $to_string_method_id = new \Psalm\Internal\MethodIdentifier(
                            $atomic_type->value,
                            '__tostring'
                        );

                        if ($codebase->methods->methodExists(
                            $to_string_method_id,
                            $context->calling_method_id,
                            $codebase->collect_locations
                                ? new CodeLocation($statements_analyzer->getSource(), $right)
                                : null,
                            !$context->collect_initializations
                                && !$context->collect_mutations
                                ? $statements_analyzer
                                : null,
                            $statements_analyzer->getFilePath()
                        )) {
                            try {
                                $storage = $codebase->methods->getStorage($to_string_method_id);
                            } catch (\UnexpectedValueException $e) {
                                continue;
                            }

                            if ($context->mutation_free && !$storage->mutation_free) {
                                if (IssueBuffer::accepts(
                                    new ImpureMethodCall(
                                        'Cannot call a possibly-mutating method '
                                            . $atomic_type->value . '::__toString from a pure context',
                                        new CodeLocation($statements_analyzer, $right)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            } elseif ($statements_analyzer->getSource()
                                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                                && $statements_analyzer->getSource()->track_mutations
                            ) {
                                $statements_analyzer->getSource()->inferred_has_mutation = true;
                                $statements_analyzer->getSource()->inferred_impure = true;
                            }
                        }
                    }
                }
            }

            if (!$left_type_match
                && (!$left_comparison_result->scalar_type_match_found || $config->strict_binary_operands)
            ) {
                if ($has_valid_left_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            'Cannot concatenate with a ' . $left_type,
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot concatenate with a ' . $left_type,
                            new CodeLocation($statements_analyzer->getSource(), $left)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if (!$right_type_match
                && (!$right_comparison_result->scalar_type_match_found || $config->strict_binary_operands)
            ) {
                if ($has_valid_right_operand) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidOperand(
                            'Cannot concatenate with a ' . $right_type,
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot concatenate with a ' . $right_type,
                            new CodeLocation($statements_analyzer->getSource(), $right)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        // When concatenating two known string literals (with only one possibility),
        // put the concatenated string into $result_type
        if ($left_type && $right_type && $left_type->isSingleStringLiteral() && $right_type->isSingleStringLiteral()) {
            $literal = $left_type->getSingleStringLiteral()->value . $right_type->getSingleStringLiteral()->value;
            if (strlen($literal) <= 1000) {
                // Limit these to 10000 bytes to avoid extremely large union types from repeated concatenations, etc
                $result_type = Type::getString($literal);
            }
        } else {
            if ($left_type
                && $right_type
            ) {
                $left_type_literal_value = $left_type->isSingleStringLiteral()
                    ? $left_type->getSingleStringLiteral()->value
                    : null;

                $right_type_literal_value = $right_type->isSingleStringLiteral()
                    ? $right_type->getSingleStringLiteral()->value
                    : null;

                if (($left_type->getId() === 'lowercase-string'
                        || $left_type->getId() === 'non-empty-lowercase-string'
                        || $left_type->isInt()
                        || ($left_type_literal_value !== null
                            && strtolower($left_type_literal_value) === $left_type_literal_value))
                    && ($right_type->getId() === 'lowercase-string'
                        || $right_type->getId() === 'non-empty-lowercase-string'
                        || $right_type->isInt()
                        || ($right_type_literal_value !== null
                            && strtolower($right_type_literal_value) === $right_type_literal_value))
                ) {
                    if ($left_type->getId() === 'non-empty-lowercase-string'
                        || $left_type->isInt()
                        || ($left_type_literal_value !== null
                            && strtolower($left_type_literal_value) === $left_type_literal_value)
                        || $right_type->getId() === 'non-empty-lowercase-string'
                        || $right_type->isInt()
                        || ($right_type_literal_value !== null
                            && strtolower($right_type_literal_value) === $right_type_literal_value)
                    ) {
                        $result_type = new Type\Union([new Type\Atomic\TNonEmptyLowercaseString()]);
                    } else {
                        $result_type = new Type\Union([new Type\Atomic\TLowercaseString()]);
                    }
                } elseif ($left_type->getId() === 'non-empty-string'
                    || $right_type->getId() === 'non-empty-string'
                    || $left_type_literal_value
                    || $right_type_literal_value
                ) {
                    $result_type = new Type\Union([new Type\Atomic\TNonEmptyString()]);
                }
            }
        }
    }
}
