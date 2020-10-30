<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\EmptyArrayAccess;
use Psalm\Issue\InvalidArrayAccess;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Issue\InvalidArrayOffset;
use Psalm\Issue\MixedArrayAccess;
use Psalm\Issue\MixedArrayAssignment;
use Psalm\Issue\MixedArrayOffset;
use Psalm\Issue\MixedStringOffsetAssignment;
use Psalm\Issue\MixedArrayTypeCoercion;
use Psalm\Issue\NullArrayAccess;
use Psalm\Issue\NullArrayOffset;
use Psalm\Issue\PossiblyInvalidArrayAccess;
use Psalm\Issue\PossiblyInvalidArrayAssignment;
use Psalm\Issue\PossiblyInvalidArrayOffset;
use Psalm\Issue\PossiblyNullArrayAccess;
use Psalm\Issue\PossiblyNullArrayAssignment;
use Psalm\Issue\PossiblyNullArrayOffset;
use Psalm\Issue\PossiblyUndefinedArrayOffset;
use Psalm\Issue\PossiblyUndefinedIntArrayOffset;
use Psalm\Issue\PossiblyUndefinedStringArrayOffset;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use function array_values;
use function array_keys;
use function count;
use function array_pop;
use function implode;
use function strlen;
use function strtolower;
use function in_array;
use function is_int;
use function preg_match;
use Psalm\Internal\Type\TemplateResult;

/**
 * @internal
 */
class ArrayFetchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context
    ) : bool {
        $array_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($stmt->dim) {
            $was_inside_use = $context->inside_use;
            $context->inside_use = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->dim, $context) === false) {
                return false;
            }
            $context->inside_use = $was_inside_use;
        }

        $keyed_array_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $dim_var_id = null;
        $new_offset_type = null;

        if ($stmt->dim) {
            $used_key_type = $statements_analyzer->node_data->getType($stmt->dim) ?: Type::getMixed();

            $dim_var_id = ExpressionIdentifier::getArrayVarId(
                $stmt->dim,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );
        } else {
            $used_key_type = Type::getInt();
        }

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $stmt->var,
            $context
        ) === false) {
            return false;
        }

        $stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);

        $codebase = $statements_analyzer->getCodebase();

        if ($keyed_array_var_id
            && $context->hasVariable($keyed_array_var_id)
            && !$context->vars_in_scope[$keyed_array_var_id]->possibly_undefined
            && $stmt_var_type
            && !$stmt_var_type->hasClassStringMap()
        ) {
            $stmt_type = clone $context->vars_in_scope[$keyed_array_var_id];

            $statements_analyzer->node_data->setType(
                $stmt,
                $stmt_type
            );

            self::taintArrayFetch(
                $statements_analyzer,
                $stmt->var,
                $keyed_array_var_id,
                $stmt_type,
                $used_key_type
            );

            return true;
        }

        $can_store_result = false;

        if ($stmt_var_type) {
            if ($stmt_var_type->isNull()) {
                if (!$context->inside_isset) {
                    if (IssueBuffer::accepts(
                        new NullArrayAccess(
                            'Cannot access array value on null variable ' . $array_var_id,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($stmt_type = $statements_analyzer->node_data->getType($stmt)) {
                    $statements_analyzer->node_data->setType(
                        $stmt,
                        Type::combineUnionTypes($stmt_type, Type::getNull())
                    );
                } else {
                    $statements_analyzer->node_data->setType($stmt, Type::getNull());
                }

                return true;
            }

            $stmt_type = self::getArrayAccessTypeGivenOffset(
                $statements_analyzer,
                $stmt,
                $stmt_var_type,
                $used_key_type,
                false,
                $array_var_id,
                $context,
                null
            );

            if ($stmt->dim && $stmt_var_type->hasArray()) {
                /**
                 * @psalm-suppress PossiblyUndefinedStringArrayOffset
                 * @var TArray|TKeyedArray|TList|Type\Atomic\TClassStringMap
                 */
                $array_type = $stmt_var_type->getAtomicTypes()['array'];

                if ($array_type instanceof Type\Atomic\TClassStringMap) {
                    $array_value_type = Type::getMixed();
                } elseif ($array_type instanceof TArray) {
                    $array_value_type = $array_type->type_params[1];
                } elseif ($array_type instanceof TList) {
                    $array_value_type = $array_type->type_param;
                } else {
                    $array_value_type = $array_type->getGenericValueType();
                }

                if ($context->inside_assignment || !$array_value_type->isMixed()) {
                    $can_store_result = true;
                }
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            if ($context->inside_isset
                && $stmt->dim
                && ($stmt_dim_type = $statements_analyzer->node_data->getType($stmt->dim))
                && $stmt_var_type->hasArray()
                && ($stmt->var instanceof PhpParser\Node\Expr\ClassConstFetch
                    || $stmt->var instanceof PhpParser\Node\Expr\ConstFetch)
            ) {
                /**
                 * @psalm-suppress PossiblyUndefinedStringArrayOffset
                 * @var TArray|TKeyedArray|TList
                 */
                $array_type = $stmt_var_type->getAtomicTypes()['array'];

                if ($array_type instanceof TArray) {
                    $const_array_key_type = $array_type->type_params[0];
                } elseif ($array_type instanceof TList) {
                    $const_array_key_type = Type::getInt();
                } else {
                    $const_array_key_type = $array_type->getGenericKeyType();
                }

                if ($dim_var_id
                    && !$const_array_key_type->hasMixed()
                    && !$stmt_dim_type->hasMixed()
                ) {
                    $new_offset_type = clone $stmt_dim_type;
                    $const_array_key_atomic_types = $const_array_key_type->getAtomicTypes();

                    foreach ($new_offset_type->getAtomicTypes() as $offset_key => $offset_atomic_type) {
                        if ($offset_atomic_type instanceof TString
                            || $offset_atomic_type instanceof TInt
                        ) {
                            if (!isset($const_array_key_atomic_types[$offset_key])
                                && !UnionTypeComparator::isContainedBy(
                                    $codebase,
                                    new Type\Union([$offset_atomic_type]),
                                    $const_array_key_type
                                )
                            ) {
                                $new_offset_type->removeType($offset_key);
                            }
                        } elseif (!UnionTypeComparator::isContainedBy(
                            $codebase,
                            $const_array_key_type,
                            new Type\Union([$offset_atomic_type])
                        )) {
                            $new_offset_type->removeType($offset_key);
                        }
                    }
                }
            }
        }

        if ($keyed_array_var_id
            && $context->hasVariable($keyed_array_var_id)
            && (!($stmt_type = $statements_analyzer->node_data->getType($stmt)) || $stmt_type->isVanillaMixed())
        ) {
            $statements_analyzer->node_data->setType($stmt, $context->vars_in_scope[$keyed_array_var_id]);
        }

        if (!($stmt_type = $statements_analyzer->node_data->getType($stmt))) {
            $stmt_type = Type::getMixed();
            $statements_analyzer->node_data->setType($stmt, $stmt_type);
        } else {
            if ($stmt_type->possibly_undefined
                && !$context->inside_isset
                && !$context->inside_unset
                && ($stmt_var_type && !$stmt_var_type->hasMixed())
            ) {
                if (IssueBuffer::accepts(
                    new PossiblyUndefinedArrayOffset(
                        'Possibly undefined array key ' . $keyed_array_var_id,
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $stmt_type->possibly_undefined = false;
        }

        if ($context->inside_isset && $dim_var_id && $new_offset_type && $new_offset_type->getAtomicTypes()) {
            $context->vars_in_scope[$dim_var_id] = $new_offset_type;
        }

        if ($keyed_array_var_id && !$context->inside_isset && $can_store_result) {
            $context->vars_in_scope[$keyed_array_var_id] = $stmt_type;
            $context->vars_possibly_in_scope[$keyed_array_var_id] = true;

            // reference the variable too
            $context->hasVariable($keyed_array_var_id);
        }

        self::taintArrayFetch(
            $statements_analyzer,
            $stmt->var,
            $keyed_array_var_id,
            $stmt_type,
            $used_key_type
        );

        return true;
    }

    /**
     * Used to create a path between a variable $foo and $foo["a"]
     */
    public static function taintArrayFetch(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $var,
        ?string $keyed_array_var_id,
        Type\Union $stmt_type,
        Type\Union $offset_type
    ) : void {
        if ($statements_analyzer->data_flow_graph
            && ($stmt_var_type = $statements_analyzer->node_data->getType($var))
            && $stmt_var_type->parent_nodes
        ) {
            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                && \in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
            ) {
                $stmt_var_type->parent_nodes = [];
                return;
            }

            $var_location = new CodeLocation($statements_analyzer->getSource(), $var);

            $new_parent_node = \Psalm\Internal\DataFlow\DataFlowNode::getForAssignment(
                $keyed_array_var_id ?: 'array-fetch',
                $var_location
            );

            $statements_analyzer->data_flow_graph->addNode($new_parent_node);

            $dim_value = $offset_type->isSingleStringLiteral()
                ? $offset_type->getSingleStringLiteral()->value
                : ($offset_type->isSingleIntLiteral()
                    ? $offset_type->getSingleIntLiteral()->value
                    : null);

            foreach ($stmt_var_type->parent_nodes as $parent_node) {
                $statements_analyzer->data_flow_graph->addPath(
                    $parent_node,
                    $new_parent_node,
                    'array-fetch' . ($dim_value !== null ? '-\'' . $dim_value . '\'' : '')
                );

                if ($stmt_type->by_ref) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $new_parent_node,
                        $parent_node,
                        'array-assignment' . ($dim_value !== null ? '-\'' . $dim_value . '\'' : '')
                    );
                }
            }

            $stmt_type->parent_nodes = [$new_parent_node->id => $new_parent_node];
        }
    }

    public static function getArrayAccessTypeGivenOffset(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Type\Union $array_type,
        Type\Union $offset_type,
        bool $in_assignment,
        ?string $array_var_id,
        Context $context,
        PhpParser\Node\Expr $assign_value = null,
        Type\Union $replacement_type = null
    ): Type\Union {
        $codebase = $statements_analyzer->getCodebase();

        $has_array_access = false;
        $non_array_types = [];

        $has_valid_offset = false;
        $expected_offset_types = [];

        $key_values = [];

        if ($stmt->dim instanceof PhpParser\Node\Scalar\String_
            || $stmt->dim instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $key_values[] = $stmt->dim->value;
        } elseif ($stmt->dim && ($stmt_dim_type = $statements_analyzer->node_data->getType($stmt->dim))) {
            $string_literals = $stmt_dim_type->getLiteralStrings();
            $int_literals = $stmt_dim_type->getLiteralInts();

            $all_atomic_types = $stmt_dim_type->getAtomicTypes();

            if (count($string_literals) + count($int_literals) === count($all_atomic_types)) {
                foreach ($string_literals as $string_literal) {
                    $key_values[] = $string_literal->value;
                }

                foreach ($int_literals as $int_literal) {
                    $key_values[] = $int_literal->value;
                }
            }
        }

        $array_access_type = null;

        if ($offset_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullArrayOffset(
                    'Cannot access value on variable ' . $array_var_id . ' using null offset',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            if ($in_assignment) {
                $offset_type->removeType('null');
                $offset_type->addType(new TLiteralInt(0));
            }
        }

        if ($offset_type->isNullable() && !$context->inside_isset) {
            if (!$offset_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullArrayOffset(
                        'Cannot access value on variable ' . $array_var_id
                            . ' using possibly null offset ' . $offset_type,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($in_assignment) {
                $offset_type->removeType('null');

                if (!$offset_type->ignore_nullable_issues) {
                    $offset_type->addType(new TLiteralInt(0));
                }
            }
        }

        foreach ($array_type->getAtomicTypes() as $type_string => $type) {
            $original_type = $type;

            if ($type instanceof TMixed || $type instanceof TTemplateParam || $type instanceof TEmpty) {
                if (!$type instanceof TTemplateParam || $type->as->isMixed() || !$type->as->isSingle()) {
                    if (!$context->collect_initializations
                        && !$context->collect_mutations
                        && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                        && (!(($parent_source = $statements_analyzer->getSource())
                                instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                            || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                    ) {
                        $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                    }

                    if (!$context->inside_isset) {
                        if ($in_assignment) {
                            if (IssueBuffer::accepts(
                                new MixedArrayAssignment(
                                    'Cannot access array value on mixed variable ' . $array_var_id,
                                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new MixedArrayAccess(
                                    'Cannot access array value on mixed variable ' . $array_var_id,
                                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }

                    $has_valid_offset = true;
                    if (!$array_access_type) {
                        $array_access_type = Type::getMixed(
                            $type instanceof TEmpty
                        );
                    } else {
                        $array_access_type = Type::combineUnionTypes(
                            $array_access_type,
                            Type::getMixed($type instanceof TEmpty)
                        );
                    }

                    continue;
                }

                $type = clone array_values($type->as->getAtomicTypes())[0];
            }

            if ($type instanceof TNull) {
                if ($array_type->ignore_nullable_issues) {
                    continue;
                }

                if ($in_assignment) {
                    if ($replacement_type) {
                        if ($array_access_type) {
                            $array_access_type = Type::combineUnionTypes($array_access_type, $replacement_type);
                        } else {
                            $array_access_type = clone $replacement_type;
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new PossiblyNullArrayAssignment(
                                'Cannot access array value on possibly null variable ' . $array_var_id .
                                    ' of type ' . $array_type,
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        $array_access_type = new Type\Union([new TEmpty]);
                    }
                } else {
                    if (!$context->inside_isset) {
                        if (IssueBuffer::accepts(
                            new PossiblyNullArrayAccess(
                                'Cannot access array value on possibly null variable ' . $array_var_id .
                                    ' of type ' . $array_type,
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    if ($array_access_type) {
                        $array_access_type = Type::combineUnionTypes($array_access_type, Type::getNull());
                    } else {
                        $array_access_type = Type::getNull();
                    }
                }

                continue;
            }

            if ($type instanceof TArray
                || $type instanceof TKeyedArray
                || $type instanceof TList
                || $type instanceof TClassStringMap
            ) {
                $has_array_access = true;

                if ($in_assignment
                    && $type instanceof TArray
                    && (($type->type_params[0]->isEmpty() && $type->type_params[1]->isEmpty())
                        || ($type->type_params[1]->hasMixed()
                            && count($key_values) === 1
                            &&  \is_string($key_values[0])))
                ) {
                    $from_empty_array = $type->type_params[0]->isEmpty() && $type->type_params[1]->isEmpty();

                    if (count($key_values) === 1) {
                        $from_mixed_array = $type->type_params[1]->isMixed();

                        [$previous_key_type, $previous_value_type] = $type->type_params;

                        // ok, type becomes an TKeyedArray
                        $array_type->removeType($type_string);
                        $type = new TKeyedArray([
                            $key_values[0] => $from_mixed_array ? Type::getMixed() : Type::getEmpty()
                        ]);

                        $type->sealed = $from_empty_array;

                        if (!$from_empty_array) {
                            $type->previous_value_type = clone $previous_value_type;
                            $type->previous_key_type = clone $previous_key_type;
                        }

                        $array_type->addType($type);
                    } elseif (!$stmt->dim && $from_empty_array && $replacement_type) {
                        $array_type->removeType($type_string);
                        $array_type->addType(new Type\Atomic\TNonEmptyList($replacement_type));
                        continue;
                    }
                } elseif ($in_assignment
                    && $type instanceof TKeyedArray
                    && $type->previous_value_type
                    && $type->previous_value_type->isMixed()
                    && count($key_values) === 1
                ) {
                    $type->properties[$key_values[0]] = Type::getMixed();
                }

                $offset_type = self::replaceOffsetTypeWithInts($offset_type);

                if ($type instanceof TList
                    && (($in_assignment && $stmt->dim)
                        || $original_type instanceof TTemplateParam
                        || !$offset_type->isInt())
                ) {
                    $type = new TArray([Type::getInt(), $type->type_param]);
                }

                if ($type instanceof TArray) {
                    // if we're assigning to an empty array with a key offset, refashion that array
                    if ($in_assignment) {
                        if ($type->type_params[0]->isEmpty()) {
                            $type->type_params[0] = $offset_type->isMixed()
                                ? Type::getArrayKey()
                                : $offset_type;
                        }
                    } elseif (!$type->type_params[0]->isEmpty()) {
                        $expected_offset_type = $type->type_params[0]->hasMixed()
                            ? new Type\Union([ new TArrayKey ])
                            : $type->type_params[0];

                        $templated_offset_type = null;

                        foreach ($offset_type->getAtomicTypes() as $offset_atomic_type) {
                            if ($offset_atomic_type instanceof TTemplateParam) {
                                $templated_offset_type = $offset_atomic_type;
                            }
                        }

                        $union_comparison_results = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

                        if ($original_type instanceof TTemplateParam && $templated_offset_type) {
                            foreach ($templated_offset_type->as->getAtomicTypes() as $offset_as) {
                                if ($offset_as instanceof Type\Atomic\TTemplateKeyOf
                                    && $offset_as->param_name === $original_type->param_name
                                    && $offset_as->defining_class === $original_type->defining_class
                                ) {
                                    /** @psalm-suppress PropertyTypeCoercion */
                                    $type->type_params[1] = new Type\Union([
                                        new Type\Atomic\TTemplateIndexedAccess(
                                            $offset_as->param_name,
                                            $templated_offset_type->param_name,
                                            $offset_as->defining_class
                                        )
                                    ]);

                                    $has_valid_offset = true;
                                }
                            }
                        } else {
                            $offset_type_contained_by_expected = UnionTypeComparator::isContainedBy(
                                $codebase,
                                $offset_type,
                                $expected_offset_type,
                                true,
                                $offset_type->ignore_falsable_issues,
                                $union_comparison_results
                            );

                            if ($codebase->config->ensure_array_string_offsets_exist
                                && $offset_type_contained_by_expected
                            ) {
                                self::checkLiteralStringArrayOffset(
                                    $offset_type,
                                    $expected_offset_type,
                                    $array_var_id,
                                    $stmt,
                                    $context,
                                    $statements_analyzer
                                );
                            }

                            if ($codebase->config->ensure_array_int_offsets_exist
                                && $offset_type_contained_by_expected
                            ) {
                                self::checkLiteralIntArrayOffset(
                                    $offset_type,
                                    $expected_offset_type,
                                    $array_var_id,
                                    $stmt,
                                    $context,
                                    $statements_analyzer
                                );
                            }

                            if ((!$offset_type_contained_by_expected
                                    && !$union_comparison_results->type_coerced_from_scalar)
                                || $union_comparison_results->to_string_cast
                            ) {
                                if ($union_comparison_results->type_coerced_from_mixed
                                    && !$offset_type->isMixed()
                                ) {
                                    if (IssueBuffer::accepts(
                                        new MixedArrayTypeCoercion(
                                            'Coercion from array offset type \'' . $offset_type->getId() . '\' '
                                                . 'to the expected type \'' . $expected_offset_type->getId() . '\'',
                                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                                        ),
                                        $statements_analyzer->getSuppressedIssues()
                                    )) {
                                        // fall through
                                    }
                                } else {
                                    $expected_offset_types[] = $expected_offset_type->getId();
                                }

                                if (UnionTypeComparator::canExpressionTypesBeIdentical(
                                    $codebase,
                                    $offset_type,
                                    $expected_offset_type
                                )) {
                                    $has_valid_offset = true;
                                }
                            } else {
                                $has_valid_offset = true;
                            }
                        }
                    }

                    if (!$stmt->dim && $type instanceof TNonEmptyArray && $type->count !== null) {
                        $type->count++;
                    }

                    if ($in_assignment && $replacement_type) {
                        /** @psalm-suppress PropertyTypeCoercion */
                        $type->type_params[1] = Type::combineUnionTypes(
                            $type->type_params[1],
                            $replacement_type,
                            $codebase
                        );
                    }

                    if (!$array_access_type) {
                        $array_access_type = $type->type_params[1];
                    } else {
                        $array_access_type = Type::combineUnionTypes(
                            $array_access_type,
                            $type->type_params[1]
                        );
                    }

                    if ($array_access_type->isEmpty()
                        && !$array_type->hasMixed()
                        && !$in_assignment
                        && !$context->inside_isset
                    ) {
                        if (IssueBuffer::accepts(
                            new EmptyArrayAccess(
                                'Cannot access value on empty array variable ' . $array_var_id,
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return Type::getMixed(true);
                        }

                        if (!IssueBuffer::isRecording()) {
                            $array_access_type = Type::getMixed(true);
                        }
                    }
                } elseif ($type instanceof TList) {
                    // if we're assigning to an empty array with a key offset, refashion that array
                    if (!$in_assignment) {
                        if (!$type instanceof TNonEmptyList
                            || (count($key_values) === 1
                                && is_int($key_values[0])
                                && $key_values[0] > 0
                                && $key_values[0] > ($type->count - 1))
                        ) {
                            $expected_offset_type = Type::getInt();

                            if ($codebase->config->ensure_array_int_offsets_exist) {
                                self::checkLiteralIntArrayOffset(
                                    $offset_type,
                                    $expected_offset_type,
                                    $array_var_id,
                                    $stmt,
                                    $context,
                                    $statements_analyzer
                                );
                            }
                        }

                        $has_valid_offset = true;
                    }

                    if ($in_assignment && $type instanceof Type\Atomic\TNonEmptyList && $type->count !== null) {
                        $type->count++;
                    }

                    if ($in_assignment && $replacement_type) {
                        $type->type_param = Type::combineUnionTypes(
                            $type->type_param,
                            $replacement_type,
                            $codebase
                        );
                    }

                    if (!$array_access_type) {
                        $array_access_type = $type->type_param;
                    } else {
                        $array_access_type = Type::combineUnionTypes(
                            $array_access_type,
                            $type->type_param
                        );
                    }
                } elseif ($type instanceof TClassStringMap) {
                    $offset_type_parts = array_values($offset_type->getAtomicTypes());

                    foreach ($offset_type_parts as $offset_type_part) {
                        if ($offset_type_part instanceof Type\Atomic\TClassString) {
                            if ($offset_type_part instanceof Type\Atomic\TTemplateParamClass) {
                                $template_result_get = new TemplateResult(
                                    [],
                                    [
                                        $type->param_name => [
                                            'class-string-map' => [
                                                new Type\Union([
                                                    new TTemplateParam(
                                                        $offset_type_part->param_name,
                                                        $offset_type_part->as_type
                                                            ? new Type\Union([$offset_type_part->as_type])
                                                            : Type::getObject(),
                                                        $offset_type_part->defining_class
                                                    )
                                                ])
                                            ]
                                        ]
                                    ]
                                );

                                $template_result_set = new TemplateResult(
                                    [],
                                    [
                                        $offset_type_part->param_name => [
                                            $offset_type_part->defining_class => [
                                                new Type\Union([
                                                    new TTemplateParam(
                                                        $type->param_name,
                                                        $type->as_type
                                                            ? new Type\Union([$type->as_type])
                                                            : Type::getObject(),
                                                        'class-string-map'
                                                    )
                                                ])
                                            ]
                                        ]
                                    ]
                                );
                            } else {
                                $template_result_get = new TemplateResult(
                                    [],
                                    [
                                        $type->param_name => [
                                            'class-string-map' => [
                                                new Type\Union([
                                                    $offset_type_part->as_type
                                                        ?: new Type\Atomic\TObject()
                                                ])
                                            ]
                                        ]
                                    ]
                                );
                                $template_result_set = new TemplateResult(
                                    [],
                                    []
                                );
                            }

                            $expected_value_param_get = clone $type->value_param;

                            $expected_value_param_get->replaceTemplateTypesWithArgTypes(
                                $template_result_get,
                                $codebase
                            );

                            if ($replacement_type) {
                                $expected_value_param_set = clone $type->value_param;

                                $replacement_type->replaceTemplateTypesWithArgTypes(
                                    $template_result_set,
                                    $codebase
                                );

                                $type->value_param = Type::combineUnionTypes(
                                    $replacement_type,
                                    $expected_value_param_set,
                                    $codebase
                                );
                            }

                            if (!$array_access_type) {
                                $array_access_type = $expected_value_param_get;
                            } else {
                                $array_access_type = Type::combineUnionTypes(
                                    $array_access_type,
                                    $expected_value_param_get,
                                    $codebase
                                );
                            }
                        }
                    }
                } else {
                    $generic_key_type = $type->getGenericKeyType();

                    if (!$stmt->dim && $type->sealed && $type->is_list) {
                        $key_values[] = count($type->properties);
                    }

                    if ($key_values) {
                        foreach ($key_values as $key_value) {
                            if (isset($type->properties[$key_value]) || $replacement_type) {
                                $has_valid_offset = true;

                                if ($replacement_type) {
                                    if (isset($type->properties[$key_value])) {
                                        $type->properties[$key_value] = Type::combineUnionTypes(
                                            $type->properties[$key_value],
                                            $replacement_type
                                        );
                                    } else {
                                        $type->properties[$key_value] = $replacement_type;
                                    }
                                }

                                if (!$array_access_type) {
                                    $array_access_type = clone $type->properties[$key_value];
                                } else {
                                    $array_access_type = Type::combineUnionTypes(
                                        $array_access_type,
                                        $type->properties[$key_value]
                                    );
                                }
                            } elseif ($in_assignment) {
                                $type->properties[$key_value] = new Type\Union([new TEmpty]);

                                if (!$array_access_type) {
                                    $array_access_type = clone $type->properties[$key_value];
                                } else {
                                    $array_access_type = Type::combineUnionTypes(
                                        $array_access_type,
                                        $type->properties[$key_value]
                                    );
                                }
                            } elseif ($type->previous_value_type) {
                                if ($codebase->config->ensure_array_string_offsets_exist) {
                                    self::checkLiteralStringArrayOffset(
                                        $offset_type,
                                        $type->getGenericKeyType(),
                                        $array_var_id,
                                        $stmt,
                                        $context,
                                        $statements_analyzer
                                    );
                                }

                                if ($codebase->config->ensure_array_int_offsets_exist) {
                                    self::checkLiteralIntArrayOffset(
                                        $offset_type,
                                        $type->getGenericKeyType(),
                                        $array_var_id,
                                        $stmt,
                                        $context,
                                        $statements_analyzer
                                    );
                                }

                                $type->properties[$key_value] = clone $type->previous_value_type;

                                $array_access_type = clone $type->previous_value_type;
                            } elseif ($array_type->hasMixed()) {
                                $has_valid_offset = true;

                                $array_access_type = Type::getMixed();
                            } else {
                                if ($type->sealed || !$context->inside_isset) {
                                    $object_like_keys = array_keys($type->properties);

                                    if (count($object_like_keys) === 1) {
                                        $expected_keys_string = '\'' . $object_like_keys[0] . '\'';
                                    } else {
                                        $last_key = array_pop($object_like_keys);
                                        $expected_keys_string = '\'' . implode('\', \'', $object_like_keys) .
                                            '\' or \'' . $last_key . '\'';
                                    }

                                    $expected_offset_types[] = $expected_keys_string;
                                }

                                $array_access_type = Type::getMixed();
                            }
                        }
                    } else {
                        $key_type = $generic_key_type->hasMixed()
                                ? Type::getArrayKey()
                                : $generic_key_type;

                        $union_comparison_results = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

                        $is_contained = UnionTypeComparator::isContainedBy(
                            $codebase,
                            $offset_type,
                            $key_type,
                            true,
                            $offset_type->ignore_falsable_issues,
                            $union_comparison_results
                        );

                        if ($context->inside_isset && !$is_contained) {
                            $is_contained = UnionTypeComparator::isContainedBy(
                                $codebase,
                                $key_type,
                                $offset_type,
                                true,
                                $offset_type->ignore_falsable_issues
                            )
                            || UnionTypeComparator::canBeContainedBy(
                                $codebase,
                                $offset_type,
                                $key_type,
                                true,
                                $offset_type->ignore_falsable_issues
                            );
                        }

                        if (($is_contained
                            || $union_comparison_results->type_coerced_from_scalar
                            || $union_comparison_results->type_coerced_from_mixed
                            || $in_assignment)
                            && !$union_comparison_results->to_string_cast
                        ) {
                            if ($replacement_type) {
                                $generic_params = Type::combineUnionTypes(
                                    $type->getGenericValueType(),
                                    $replacement_type
                                );

                                $new_key_type = Type::combineUnionTypes(
                                    $generic_key_type,
                                    $offset_type->isMixed() ? Type::getArrayKey() : $offset_type
                                );

                                $property_count = $type->sealed ? count($type->properties) : null;

                                if (!$stmt->dim && $property_count) {
                                    ++$property_count;
                                    $array_type->removeType($type_string);
                                    $type = new TNonEmptyArray([
                                        $new_key_type,
                                        $generic_params,
                                    ]);
                                    $array_type->addType($type);
                                    $type->count = $property_count;
                                } else {
                                    $array_type->removeType($type_string);

                                    if (!$stmt->dim && $type->is_list) {
                                        $type = new TList($generic_params);
                                    } else {
                                        $type = new TArray([
                                            $new_key_type,
                                            $generic_params,
                                        ]);
                                    }

                                    $array_type->addType($type);
                                }

                                if (!$array_access_type) {
                                    $array_access_type = clone $generic_params;
                                } else {
                                    $array_access_type = Type::combineUnionTypes(
                                        $array_access_type,
                                        $generic_params
                                    );
                                }
                            } else {
                                if (!$array_access_type) {
                                    $array_access_type = $type->getGenericValueType();
                                } else {
                                    $array_access_type = Type::combineUnionTypes(
                                        $array_access_type,
                                        $type->getGenericValueType()
                                    );
                                }
                            }

                            $has_valid_offset = true;
                        } else {
                            if (!$context->inside_isset
                                || ($type->sealed && !$union_comparison_results->type_coerced)
                            ) {
                                $expected_offset_types[] = $generic_key_type->getId();
                            }

                            $array_access_type = Type::getMixed();
                        }
                    }
                }
                continue;
            }

            if ($type instanceof TString) {
                if ($in_assignment && $replacement_type) {
                    if ($replacement_type->hasMixed()) {
                        if (!$context->collect_initializations
                            && !$context->collect_mutations
                            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                            && (!(($parent_source = $statements_analyzer->getSource())
                                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                                || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                        ) {
                            $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                        }

                        if (IssueBuffer::accepts(
                            new MixedStringOffsetAssignment(
                                'Right-hand-side of string offset assignment cannot be mixed',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (!$context->collect_initializations
                            && !$context->collect_mutations
                            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                            && (!(($parent_source = $statements_analyzer->getSource())
                                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                                || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                        ) {
                            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
                        }
                    }
                }

                if ($type instanceof TSingleLetter) {
                    $valid_offset_type = Type::getInt(false, 0);
                } elseif ($type instanceof TLiteralString) {
                    if (!strlen($type->value)) {
                        $valid_offset_type = Type::getEmpty();
                    } elseif (strlen($type->value) < 10) {
                        $valid_offsets = [];

                        for ($i = -strlen($type->value), $l = strlen($type->value); $i < $l; $i++) {
                            $valid_offsets[] = new TLiteralInt($i);
                        }

                        if (!$valid_offsets) {
                            throw new \UnexpectedValueException('This is weird');
                        }

                        $valid_offset_type = new Type\Union($valid_offsets);
                    } else {
                        $valid_offset_type = Type::getInt();
                    }
                } else {
                    $valid_offset_type = Type::getInt();
                }

                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $offset_type,
                    $valid_offset_type,
                    true
                )) {
                    $expected_offset_types[] = $valid_offset_type->getId();

                    $array_access_type = Type::getMixed();
                } else {
                    $has_valid_offset = true;

                    if (!$array_access_type) {
                        $array_access_type = Type::getSingleLetter();
                    } else {
                        $array_access_type = Type::combineUnionTypes(
                            $array_access_type,
                            Type::getSingleLetter()
                        );
                    }
                }

                continue;
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

            if ($type instanceof Type\Atomic\TFalse && $array_type->ignore_falsable_issues) {
                continue;
            }

            if ($type instanceof TNamedObject) {
                if (strtolower($type->value) === 'simplexmlelement') {
                    $call_array_access_type = new Type\Union([new TNamedObject('SimpleXMLElement')]);
                } elseif (strtolower($type->value) === 'domnodelist' && $stmt->dim) {
                    $old_data_provider = $statements_analyzer->node_data;

                    $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                    $fake_method_call = new PhpParser\Node\Expr\MethodCall(
                        $stmt->var,
                        new PhpParser\Node\Identifier('item', $stmt->var->getAttributes()),
                        [
                            new PhpParser\Node\Arg($stmt->dim)
                        ]
                    );

                    $suppressed_issues = $statements_analyzer->getSuppressedIssues();

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    if (!in_array('MixedMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->addSuppressedIssues(['MixedMethodCall']);
                    }

                    \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
                        $statements_analyzer,
                        $fake_method_call,
                        $context
                    );

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    if (!in_array('MixedMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->removeSuppressedIssues(['MixedMethodCall']);
                    }

                    $call_array_access_type = $statements_analyzer->node_data->getType(
                        $fake_method_call
                    ) ?: Type::getMixed();

                    $statements_analyzer->node_data = $old_data_provider;
                } else {
                    $suppressed_issues = $statements_analyzer->getSuppressedIssues();

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    if (!in_array('MixedMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->addSuppressedIssues(['MixedMethodCall']);
                    }

                    if ($in_assignment) {
                        $old_node_data = $statements_analyzer->node_data;

                        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                        $fake_set_method_call = new PhpParser\Node\Expr\MethodCall(
                            $stmt->var,
                            new PhpParser\Node\Identifier('offsetSet', $stmt->var->getAttributes()),
                            [
                                new PhpParser\Node\Arg(
                                    $stmt->dim
                                        ? $stmt->dim
                                        : new PhpParser\Node\Expr\ConstFetch(
                                            new PhpParser\Node\Name('null'),
                                            $stmt->var->getAttributes()
                                        )
                                ),
                                new PhpParser\Node\Arg(
                                    $assign_value
                                        ?: new PhpParser\Node\Expr\ConstFetch(
                                            new PhpParser\Node\Name('null'),
                                            $stmt->var->getAttributes()
                                        )
                                ),
                            ]
                        );

                        \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
                            $statements_analyzer,
                            $fake_set_method_call,
                            $context
                        );

                        $statements_analyzer->node_data = $old_node_data;
                    }

                    if ($stmt->dim) {
                        $old_node_data = $statements_analyzer->node_data;

                        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                        $fake_get_method_call = new PhpParser\Node\Expr\MethodCall(
                            $stmt->var,
                            new PhpParser\Node\Identifier('offsetGet', $stmt->var->getAttributes()),
                            [
                                new PhpParser\Node\Arg(
                                    $stmt->dim
                                )
                            ]
                        );

                        \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
                            $statements_analyzer,
                            $fake_get_method_call,
                            $context
                        );

                        $call_array_access_type = $statements_analyzer->node_data->getType($fake_get_method_call)
                            ?: Type::getMixed();

                        $statements_analyzer->node_data = $old_node_data;
                    } else {
                        $call_array_access_type = Type::getVoid();
                    }

                    $has_array_access = true;

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    if (!in_array('MixedMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->removeSuppressedIssues(['MixedMethodCall']);
                    }
                }

                if (!$array_access_type) {
                    $array_access_type = $call_array_access_type;
                } else {
                    $array_access_type = Type::combineUnionTypes(
                        $array_access_type,
                        $call_array_access_type
                    );
                }
            } elseif (!$array_type->hasMixed()) {
                $non_array_types[] = (string)$type;
            }
        }

        if ($non_array_types) {
            if ($has_array_access) {
                if ($in_assignment) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidArrayAssignment(
                            'Cannot access array value on non-array variable ' .
                            $array_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                    ) {
                        // do nothing
                    }
                } elseif (!$context->inside_isset) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidArrayAccess(
                            'Cannot access array value on non-array variable ' .
                            $array_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                    ) {
                        // do nothing
                    }
                }
            } else {
                if ($in_assignment) {
                    if (IssueBuffer::accepts(
                        new InvalidArrayAssignment(
                            'Cannot access array value on non-array variable ' .
                            $array_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidArrayAccess(
                            'Cannot access array value on non-array variable ' .
                            $array_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $array_access_type = Type::getMixed();
            }
        }

        if ($offset_type->hasMixed()) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            }

            if (IssueBuffer::accepts(
                new MixedArrayOffset(
                    'Cannot access value on variable ' . $array_var_id . ' using mixed offset',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        } else {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
            }

            if ($expected_offset_types) {
                $invalid_offset_type = $expected_offset_types[0];

                $used_offset = 'using a ' . $offset_type->getId() . ' offset';

                if ($key_values) {
                    $used_offset = 'using offset value of '
                        . (is_int($key_values[0]) ? $key_values[0] : '\'' . $key_values[0] . '\'');
                }

                if ($has_valid_offset && $context->inside_isset) {
                    // do nothing
                } elseif ($has_valid_offset) {
                    if (!$context->inside_unset) {
                        if (IssueBuffer::accepts(
                            new PossiblyInvalidArrayOffset(
                                'Cannot access value on variable ' . $array_var_id . ' ' . $used_offset
                                    . ', expecting ' . $invalid_offset_type,
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidArrayOffset(
                            'Cannot access value on variable ' . $array_var_id . ' ' . $used_offset
                                . ', expecting ' . $invalid_offset_type,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($array_access_type === null) {
            // shouldn’t happen, but don’t crash
            return Type::getMixed();
        }

        if ($array_type->by_ref) {
            $array_access_type->by_ref = true;
        }

        if ($in_assignment) {
            $array_type->bustCache();
        }

        return $array_access_type;
    }

    private static function checkLiteralIntArrayOffset(
        Type\Union $offset_type,
        Type\Union $expected_offset_type,
        ?string $array_var_id,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        StatementsAnalyzer $statements_analyzer
    ) : void {
        if ($context->inside_isset || $context->inside_unset) {
            return;
        }

        if ($offset_type->hasLiteralInt()) {
            $found_match = false;

            foreach ($offset_type->getAtomicTypes() as $offset_type_part) {
                if ($array_var_id
                    && $offset_type_part instanceof TLiteralInt
                    && isset(
                        $context->vars_in_scope[
                            $array_var_id . '[' . $offset_type_part->value . ']'
                        ]
                    )
                    && !$context->vars_in_scope[
                            $array_var_id . '[' . $offset_type_part->value . ']'
                        ]->possibly_undefined
                ) {
                    $found_match = true;
                    break;
                }
            }

            if (!$found_match) {
                if (IssueBuffer::accepts(
                    new PossiblyUndefinedIntArrayOffset(
                        'Possibly undefined array offset \''
                            . $offset_type->getId() . '\' '
                            . 'is risky given expected type \''
                            . $expected_offset_type->getId() . '\'.'
                            . ' Consider using isset beforehand.',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    private static function checkLiteralStringArrayOffset(
        Type\Union $offset_type,
        Type\Union $expected_offset_type,
        ?string $array_var_id,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        StatementsAnalyzer $statements_analyzer
    ) : void {
        if ($context->inside_isset || $context->inside_unset) {
            return;
        }

        if ($offset_type->hasLiteralString() && !$expected_offset_type->hasLiteralClassString()) {
            $found_match = false;

            foreach ($offset_type->getAtomicTypes() as $offset_type_part) {
                if ($array_var_id
                    && $offset_type_part instanceof TLiteralString
                    && isset(
                        $context->vars_in_scope[
                            $array_var_id . '[\'' . $offset_type_part->value . '\']'
                        ]
                    )
                    && !$context->vars_in_scope[
                            $array_var_id . '[\'' . $offset_type_part->value . '\']'
                        ]->possibly_undefined
                ) {
                    $found_match = true;
                    break;
                }
            }

            if (!$found_match) {
                if (IssueBuffer::accepts(
                    new PossiblyUndefinedStringArrayOffset(
                        'Possibly undefined array offset \''
                            . $offset_type->getId() . '\' '
                            . 'is risky given expected type \''
                            . $expected_offset_type->getId() . '\'.'
                            . ' Consider using isset beforehand.',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    public static function replaceOffsetTypeWithInts(Type\Union $offset_type): Type\Union
    {
        $offset_types = $offset_type->getAtomicTypes();

        $cloned = false;

        foreach ($offset_types as $key => $offset_type_part) {
            if ($offset_type_part instanceof Type\Atomic\TLiteralString) {
                if (preg_match('/^(0|[1-9][0-9]*)$/', $offset_type_part->value)) {
                    if (!$cloned) {
                        $offset_type = clone $offset_type;
                        $cloned = true;
                    }
                    $offset_type->addType(new Type\Atomic\TLiteralInt((int) $offset_type_part->value));
                    $offset_type->removeType($key);
                }
            } elseif ($offset_type_part instanceof Type\Atomic\TBool) {
                if (!$cloned) {
                    $offset_type = clone $offset_type;
                    $cloned = true;
                }

                if ($offset_type_part instanceof Type\Atomic\TFalse) {
                    if (!$offset_type->ignore_falsable_issues) {
                        $offset_type->addType(new Type\Atomic\TLiteralInt(0));
                        $offset_type->removeType($key);
                    }
                } elseif ($offset_type_part instanceof Type\Atomic\TTrue) {
                    $offset_type->addType(new Type\Atomic\TLiteralInt(1));
                    $offset_type->removeType($key);
                } else {
                    $offset_type->addType(new Type\Atomic\TLiteralInt(0));
                    $offset_type->addType(new Type\Atomic\TLiteralInt(1));
                    $offset_type->removeType($key);
                }
            }
        }

        return $offset_type;
    }
}
