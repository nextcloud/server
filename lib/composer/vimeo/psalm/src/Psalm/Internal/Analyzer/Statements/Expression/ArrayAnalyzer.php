<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DuplicateArrayKey;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Internal\Type\TypeCombination;
use function preg_match;
use function array_merge;
use function array_values;
use function count;

/**
 * @internal
 */
class ArrayAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Array_ $stmt,
        Context $context
    ) : bool {
        // if the array is empty, this special type allows us to match any other array type against it
        if (empty($stmt->items)) {
            $statements_analyzer->node_data->setType($stmt, Type::getEmptyArray());

            return true;
        }

        $item_key_atomic_types = [];
        $item_value_atomic_types = [];

        $property_types = [];
        $class_strings = [];

        $can_create_objectlike = true;

        $array_keys = [];

        $int_offset_diff = 0;

        $codebase = $statements_analyzer->getCodebase();

        $all_list = true;

        $parent_taint_nodes = [];

        foreach ($stmt->items as $int_offset => $item) {
            if ($item === null) {
                \Psalm\IssueBuffer::add(
                    new \Psalm\Issue\ParseError(
                        'Array element cannot be empty',
                        new CodeLocation($statements_analyzer, $stmt)
                    )
                );

                return false;
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $item->value, $context) === false) {
                return false;
            }

            if ($item->unpack) {
                $unpacked_array_type = $statements_analyzer->node_data->getType($item->value);

                if (!$unpacked_array_type) {
                    continue;
                }

                foreach ($unpacked_array_type->getAtomicTypes() as $unpacked_atomic_type) {
                    if ($unpacked_atomic_type instanceof Type\Atomic\TKeyedArray) {
                        $unpacked_array_offset = 0;
                        foreach ($unpacked_atomic_type->properties as $key => $property_value) {
                            if (\is_string($key)) {
                                if (IssueBuffer::accepts(
                                    new DuplicateArrayKey(
                                        'String keys are not supported in unpacked arrays',
                                        new CodeLocation($statements_analyzer->getSource(), $item->value)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }

                                continue;
                            }

                            $item_key_atomic_types[] = new Type\Atomic\TLiteralInt($key);
                            $item_value_atomic_types = array_merge(
                                $item_value_atomic_types,
                                array_values($property_value->getAtomicTypes())
                            );
                            $array_keys[$int_offset + $int_offset_diff + $unpacked_array_offset] = true;
                            $property_types[$int_offset + $int_offset_diff + $unpacked_array_offset] = $property_value;

                            $unpacked_array_offset++;
                        }

                        $int_offset_diff += $unpacked_array_offset - 1;
                    } else {
                        $can_create_objectlike = false;

                        if ($unpacked_atomic_type instanceof Type\Atomic\TArray) {
                            if ($unpacked_atomic_type->type_params[0]->hasString()) {
                                if (IssueBuffer::accepts(
                                    new DuplicateArrayKey(
                                        'String keys are not supported in unpacked arrays',
                                        new CodeLocation($statements_analyzer->getSource(), $item->value)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            } elseif ($unpacked_atomic_type->type_params[0]->hasInt()) {
                                $item_key_atomic_types[] = new Type\Atomic\TInt();
                            }

                            $item_value_atomic_types = array_merge(
                                $item_value_atomic_types,
                                array_values($unpacked_atomic_type->type_params[1]->getAtomicTypes())
                            );
                        } elseif ($unpacked_atomic_type instanceof Type\Atomic\TList) {
                            $item_key_atomic_types[] = new Type\Atomic\TInt();

                            $item_value_atomic_types = array_merge(
                                $item_value_atomic_types,
                                array_values($unpacked_atomic_type->type_param->getAtomicTypes())
                            );
                        }
                    }
                }

                continue;
            }

            $item_key_value = null;

            if ($item->key) {
                $all_list = false;

                $was_inside_use = $context->inside_use;
                $context->inside_use = true;
                if (ExpressionAnalyzer::analyze($statements_analyzer, $item->key, $context) === false) {
                    return false;
                }
                $context->inside_use = $was_inside_use;

                if ($item_key_type = $statements_analyzer->node_data->getType($item->key)) {
                    $key_type = $item_key_type;

                    if ($key_type->isNull()) {
                        $key_type = Type::getString('');
                    }

                    if ($item->key instanceof PhpParser\Node\Scalar\String_
                        && preg_match('/^(0|[1-9][0-9]*)$/', $item->key->value)
                    ) {
                        $key_type = Type::getInt(false, (int) $item->key->value);
                    }

                    $item_key_atomic_types = array_merge(
                        $item_key_atomic_types,
                        array_values($key_type->getAtomicTypes())
                    );

                    if ($key_type->isSingleStringLiteral()) {
                        $item_key_literal_type = $key_type->getSingleStringLiteral();
                        $item_key_value = $item_key_literal_type->value;

                        if ($item_key_literal_type instanceof Type\Atomic\TLiteralClassString) {
                            $class_strings[$item_key_value] = true;
                        }
                    } elseif ($key_type->isSingleIntLiteral()) {
                        $item_key_value = $key_type->getSingleIntLiteral()->value;

                        if ($item_key_value > $int_offset + $int_offset_diff) {
                            $int_offset_diff = $item_key_value - $int_offset;
                        }
                    }
                }
            } else {
                $item_key_value = $int_offset + $int_offset_diff;
                $item_key_atomic_types[] = new Type\Atomic\TInt();
            }

            if ($item_key_value !== null) {
                if (isset($array_keys[$item_key_value])) {
                    if (IssueBuffer::accepts(
                        new DuplicateArrayKey(
                            'Key \'' . $item_key_value . '\' already exists on array',
                            new CodeLocation($statements_analyzer->getSource(), $item)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $array_keys[$item_key_value] = true;
            }

            if ($statements_analyzer->data_flow_graph
                && ($statements_analyzer->data_flow_graph instanceof \Psalm\Internal\Codebase\VariableUseGraph
                    || !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues()))
            ) {
                if ($item_value_type = $statements_analyzer->node_data->getType($item->value)) {
                    if ($item_value_type->parent_nodes) {
                        $var_location = new CodeLocation($statements_analyzer->getSource(), $item);

                        $new_parent_node = \Psalm\Internal\DataFlow\DataFlowNode::getForAssignment(
                            'array'
                                . ($item_key_value !== null ? '[\'' . $item_key_value . '\']' : ''),
                            $var_location
                        );

                        $statements_analyzer->data_flow_graph->addNode($new_parent_node);

                        foreach ($item_value_type->parent_nodes as $parent_node) {
                            $statements_analyzer->data_flow_graph->addPath(
                                $parent_node,
                                $new_parent_node,
                                'array-assignment'
                                    . ($item_key_value !== null ? '-\'' . $item_key_value . '\'' : '')
                            );
                        }

                        $parent_taint_nodes += [$new_parent_node->id => $new_parent_node];
                    }
                }
            }

            if ($item->byRef) {
                $var_id = ExpressionIdentifier::getArrayVarId(
                    $item->value,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer
                );

                if ($var_id) {
                    $context->removeDescendents(
                        $var_id,
                        $context->vars_in_scope[$var_id] ?? null,
                        null,
                        $statements_analyzer
                    );

                    $context->vars_in_scope[$var_id] = Type::getMixed();
                }
            }

            if ($item_value_atomic_types && !$can_create_objectlike) {
                continue;
            }

            if ($item_value_type = $statements_analyzer->node_data->getType($item->value)) {
                if ($item_key_value !== null && count($property_types) <= 100) {
                    $property_types[$item_key_value] = $item_value_type;
                } else {
                    $can_create_objectlike = false;
                }

                $item_value_atomic_types = array_merge(
                    $item_value_atomic_types,
                    array_values($item_value_type->getAtomicTypes())
                );
            } else {
                $item_value_atomic_types[] = new Type\Atomic\TMixed();

                if ($item_key_value !== null && count($property_types) <= 100) {
                    $property_types[$item_key_value] = Type::getMixed();
                } else {
                    $can_create_objectlike = false;
                }
            }
        }

        if ($item_key_atomic_types) {
            $item_key_type = TypeCombination::combineTypes(
                $item_key_atomic_types,
                $codebase,
                false,
                true,
                30
            );
        } else {
            $item_key_type = null;
        }

        if ($item_value_atomic_types) {
            $item_value_type = TypeCombination::combineTypes(
                $item_value_atomic_types,
                $codebase,
                false,
                true,
                30
            );
        } else {
            $item_value_type = null;
        }

        // if this array looks like an object-like array, let's return that instead
        if ($item_value_type
            && $item_key_type
            && ($item_key_type->hasString() || $item_key_type->hasInt())
            && $can_create_objectlike
            && $property_types
        ) {
            $object_like = new Type\Atomic\TKeyedArray($property_types, $class_strings);
            $object_like->sealed = true;
            $object_like->is_list = $all_list;

            $stmt_type = new Type\Union([$object_like]);

            if ($parent_taint_nodes) {
                $stmt_type->parent_nodes = $parent_taint_nodes;
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($all_list) {
            $array_type = new Type\Atomic\TNonEmptyList($item_value_type ?: Type::getMixed());
            $array_type->count = count($stmt->items);

            $stmt_type = new Type\Union([
                $array_type,
            ]);

            if ($parent_taint_nodes) {
                $stmt_type->parent_nodes = $parent_taint_nodes;
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        $array_type = new Type\Atomic\TNonEmptyArray([
            $item_key_type && !$item_key_type->hasMixed() ? $item_key_type : Type::getArrayKey(),
            $item_value_type ?: Type::getMixed(),
        ]);

        $array_type->count = count($stmt->items);

        $stmt_type = new Type\Union([
            $array_type,
        ]);

        if ($parent_taint_nodes) {
            $stmt_type->parent_nodes = $parent_taint_nodes;
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return true;
    }
}
