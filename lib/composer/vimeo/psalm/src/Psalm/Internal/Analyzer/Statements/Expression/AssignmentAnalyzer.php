<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\StaticPropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Scanner\VarDocblockComment;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\AssignmentToVoid;
use Psalm\Issue\ImpureByReferenceAssignment;
use Psalm\Issue\ImpurePropertyAssignment;
use Psalm\Issue\InvalidArrayAccess;
use Psalm\Issue\InvalidArrayOffset;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\LoopInvalidation;
use Psalm\Issue\MissingDocblockType;
use Psalm\Issue\MixedAssignment;
use Psalm\Issue\MixedArrayAccess;
use Psalm\Issue\NoValue;
use Psalm\Issue\PossiblyInvalidArrayAccess;
use Psalm\Issue\PossiblyNullArrayAccess;
use Psalm\Issue\PossiblyUndefinedArrayOffset;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\Issue\UnnecessaryVarAnnotation;
use Psalm\IssueBuffer;
use Psalm\Type;
use function is_string;
use function strpos;
use function strtolower;
use function substr;

/**
 * @internal
 */
class AssignmentAnalyzer
{
    /**
     * @param  PhpParser\Node\Expr|null $assign_value  This has to be null to support list destructuring
     *
     * @return false|Type\Union
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $assign_var,
        ?PhpParser\Node\Expr $assign_value,
        ?Type\Union $assign_value_type,
        Context $context,
        ?PhpParser\Comment\Doc $doc_comment,
        array $not_ignored_docblock_var_ids = []
    ) {
        $var_id = ExpressionIdentifier::getVarId(
            $assign_var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        // gets a variable id that *may* contain array keys
        $array_var_id = ExpressionIdentifier::getArrayVarId(
            $assign_var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $var_comments = [];
        $comment_type = null;
        $comment_type_location = null;

        $was_in_assignment = $context->inside_assignment;

        $context->inside_assignment = true;

        $codebase = $statements_analyzer->getCodebase();

        $base_assign_value = $assign_value;

        while ($base_assign_value instanceof PhpParser\Node\Expr\Assign) {
            $base_assign_value = $base_assign_value->expr;
        }

        if ($base_assign_value !== $assign_value) {
            ExpressionAnalyzer::analyze($statements_analyzer, $base_assign_value, $context);

            $assign_value_type = $statements_analyzer->node_data->getType($base_assign_value) ?: $assign_value_type;
        }

        $removed_taints = [];

        if ($doc_comment) {
            $file_path = $statements_analyzer->getRootFilePath();

            $file_storage_provider = $codebase->file_storage_provider;

            $file_storage = $file_storage_provider->get($file_path);

            $template_type_map = $statements_analyzer->getTemplateTypeMap();

            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $doc_comment,
                    $statements_analyzer->getSource(),
                    $statements_analyzer->getAliases(),
                    $template_type_map,
                    $file_storage->type_aliases
                );
            } catch (IncorrectDocblockException $e) {
                if (IssueBuffer::accepts(
                    new MissingDocblockType(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    )
                )) {
                    // fall through
                }
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    )
                )) {
                    // fall through
                }
            }

            foreach ($var_comments as $var_comment) {
                if ($var_comment->removed_taints) {
                    $removed_taints = $var_comment->removed_taints;
                }

                self::assignTypeFromVarDocblock(
                    $statements_analyzer,
                    $assign_var,
                    $var_comment,
                    $context,
                    $var_id,
                    $comment_type,
                    $comment_type_location,
                    $not_ignored_docblock_var_ids
                );
            }
        }

        if ($array_var_id) {
            unset($context->referenced_var_ids[$array_var_id]);
            $context->assigned_var_ids[$array_var_id] = true;
            $context->possibly_assigned_var_ids[$array_var_id] = true;
        }

        if ($assign_value) {
            if ($var_id && $assign_value instanceof PhpParser\Node\Expr\Closure) {
                foreach ($assign_value->uses as $closure_use) {
                    if ($closure_use->byRef
                        && is_string($closure_use->var->name)
                        && $var_id === '$' . $closure_use->var->name
                    ) {
                        $context->vars_in_scope[$var_id] = Type::getClosure();
                        $context->vars_possibly_in_scope[$var_id] = true;
                    }
                }
            }

            $was_inside_use = $context->inside_use;

            $root_expr = $assign_var;

            while ($root_expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
                $root_expr = $root_expr->var;
            }

            // if we don't know where this data is going, treat as a dead-end usage
            if (!$root_expr instanceof PhpParser\Node\Expr\Variable
                || (\is_string($root_expr->name)
                    && \in_array('$' . $root_expr->name, VariableFetchAnalyzer::SUPER_GLOBALS, true))
            ) {
                $context->inside_use = true;
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_value, $context) === false) {
                if ($var_id) {
                    if ($array_var_id) {
                        $context->removeDescendents($array_var_id, null, $assign_value_type);
                    }

                    // if we're not exiting immediately, make everything mixed
                    $context->vars_in_scope[$var_id] = $comment_type ?: Type::getMixed();
                }

                return false;
            }

            $context->inside_use = $was_inside_use;
        }

        if ($comment_type && $comment_type_location) {
            $temp_assign_value_type = $assign_value_type
                ? $assign_value_type
                : ($assign_value ? $statements_analyzer->node_data->getType($assign_value) : null);

            if ($codebase->find_unused_variables
                && $temp_assign_value_type
                && $array_var_id
                && (!$not_ignored_docblock_var_ids || isset($not_ignored_docblock_var_ids[$array_var_id]))
                && $temp_assign_value_type->getId() === $comment_type->getId()
                && !$comment_type->isMixed()
            ) {
                if ($codebase->alter_code
                    && isset($statements_analyzer->getProjectAnalyzer()->getIssuesToFix()['UnnecessaryVarAnnotation'])
                ) {
                    FileManipulationBuffer::addVarAnnotationToRemove($comment_type_location);
                } elseif (IssueBuffer::accepts(
                    new UnnecessaryVarAnnotation(
                        'The @var ' . $comment_type . ' annotation for '
                            . $array_var_id . ' is unnecessary',
                        $comment_type_location
                    )
                )) {
                    // fall through
                }
            }

            $parent_nodes = $temp_assign_value_type->parent_nodes ?? [];

            $assign_value_type = $comment_type;
            $assign_value_type->parent_nodes = $parent_nodes;
        } elseif (!$assign_value_type) {
            if ($assign_value) {
                $assign_value_type = $statements_analyzer->node_data->getType($assign_value);
            }

            if ($assign_value_type) {
                $assign_value_type = clone $assign_value_type;
            } else {
                $assign_value_type = Type::getMixed();
            }
        }

        if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
            && !$assign_value_type->parent_nodes
        ) {
            if ($array_var_id) {
                $assignment_node = DataFlowNode::getForAssignment(
                    $array_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $assign_var)
                );
            } else {
                $assignment_node = new DataFlowNode('unknown-origin', 'unknown origin', null);
            }

            $assign_value_type->parent_nodes = [
                $assignment_node->id => $assignment_node
            ];
        }

        if ($array_var_id && isset($context->vars_in_scope[$array_var_id])) {
            if ($context->vars_in_scope[$array_var_id]->by_ref) {
                if ($context->mutation_free) {
                    if (IssueBuffer::accepts(
                        new ImpureByReferenceAssignment(
                            'Variable ' . $array_var_id . ' cannot be assigned to as it is passed by reference',
                            new CodeLocation($statements_analyzer->getSource(), $assign_var)
                        )
                    )) {
                        // fall through
                    }
                } elseif ($statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                    && $statements_analyzer->getSource()->track_mutations
                ) {
                    $statements_analyzer->getSource()->inferred_impure = true;
                    $statements_analyzer->getSource()->inferred_has_mutation = true;
                }

                $assign_value_type->by_ref = true;
            }

            // removes dependent vars from $context
            $context->removeDescendents(
                $array_var_id,
                $context->vars_in_scope[$array_var_id],
                $assign_value_type,
                $statements_analyzer
            );
        } else {
            $root_var_id = ExpressionIdentifier::getRootVarId(
                $assign_var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($root_var_id && isset($context->vars_in_scope[$root_var_id])) {
                $context->removeVarFromConflictingClauses(
                    $root_var_id,
                    $context->vars_in_scope[$root_var_id],
                    $statements_analyzer
                );
            }
        }

        $codebase = $statements_analyzer->getCodebase();

        if ($assign_value_type->hasMixed()) {
            $root_var_id = ExpressionIdentifier::getRootVarId(
                $assign_var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            }

            if (!$assign_var instanceof PhpParser\Node\Expr\PropertyFetch
                && !strpos($root_var_id ?? '', '->')
                && !$comment_type
                && substr($var_id ?? '', 0, 2) !== '$_'
            ) {
                if (IssueBuffer::accepts(
                    new MixedAssignment(
                        $var_id
                            ? 'Unable to determine the type that ' . $var_id . ' is being assigned to'
                            : 'Unable to determine the type of this assignment',
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
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

            if ($var_id
                && isset($context->byref_constraints[$var_id])
                && ($outer_constraint_type = $context->byref_constraints[$var_id]->type)
            ) {
                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $assign_value_type,
                    $outer_constraint_type,
                    $assign_value_type->ignore_nullable_issues,
                    $assign_value_type->ignore_falsable_issues
                )
                ) {
                    if (IssueBuffer::accepts(
                        new ReferenceConstraintViolation(
                            'Variable ' . $var_id . ' is limited to values of type '
                                . $context->byref_constraints[$var_id]->type
                                . ' because it is passed by reference, '
                                . $assign_value_type->getId() . ' type found',
                            new CodeLocation($statements_analyzer->getSource(), $assign_var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($var_id === '$this' && IssueBuffer::accepts(
            new InvalidScope(
                'Cannot re-assign ' . $var_id,
                new CodeLocation($statements_analyzer->getSource(), $assign_var)
            ),
            $statements_analyzer->getSuppressedIssues()
        )) {
            return false;
        }

        if (isset($context->protected_var_ids[$var_id])
            && $assign_value_type->hasLiteralInt()
        ) {
            if (IssueBuffer::accepts(
                new LoopInvalidation(
                    'Variable ' . $var_id . ' has already been assigned in a for/foreach loop',
                    new CodeLocation($statements_analyzer->getSource(), $assign_var)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($assign_var instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($assign_var->name)) {
                if ($var_id) {
                    $context->vars_in_scope[$var_id] = $assign_value_type;
                    $context->vars_possibly_in_scope[$var_id] = true;

                    $location = new CodeLocation($statements_analyzer, $assign_var);

                    if (!$statements_analyzer->hasVariable($var_id)) {
                        $statements_analyzer->registerVariable(
                            $var_id,
                            $location,
                            $context->branch_point
                        );
                    } elseif (!$context->inside_isset) {
                        $statements_analyzer->registerVariableAssignment(
                            $var_id,
                            $location
                        );
                    }

                    if ($codebase->store_node_types
                        && !$context->collect_initializations
                        && !$context->collect_mutations
                    ) {
                        $location = new CodeLocation($statements_analyzer, $assign_var);
                        $codebase->analyzer->addNodeReference(
                            $statements_analyzer->getFilePath(),
                            $assign_var,
                            $location->raw_file_start
                                . '-' . $location->raw_file_end
                                . ':' . $assign_value_type->getId()
                        );
                    }

                    if (isset($context->byref_constraints[$var_id])) {
                        $assign_value_type->by_ref = true;
                    }

                    if ($assign_value_type->by_ref) {
                        if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
                            && $assign_value_type->parent_nodes
                        ) {
                            $location = new CodeLocation($statements_analyzer, $assign_var);

                            $byref_node = DataFlowNode::getForAssignment($var_id, $location);

                            foreach ($assign_value_type->parent_nodes as $parent_node) {
                                $statements_analyzer->data_flow_graph->addPath(
                                    $parent_node,
                                    new DataFlowNode('variable-use', 'variable use', null),
                                    'variable-use'
                                );

                                $statements_analyzer->data_flow_graph->addPath(
                                    $byref_node,
                                    $parent_node,
                                    'byref-assignment'
                                );
                            }
                        }
                    }
                }
            } else {
                $was_inside_use = $context->inside_use;
                $context->inside_use = true;

                if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_var->name, $context) === false) {
                    return false;
                }

                $context->inside_use = $was_inside_use;

                if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
                    && $assign_value_type->parent_nodes
                ) {
                    foreach ($assign_value_type->parent_nodes as $parent_node) {
                        $statements_analyzer->data_flow_graph->addPath(
                            $parent_node,
                            new DataFlowNode('variable-use', 'variable use', null),
                            'variable-use'
                        );
                    }
                }
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\List_
            || $assign_var instanceof PhpParser\Node\Expr\Array_
        ) {
            if (!$assign_value_type->hasArray()
                && !$assign_value_type->isMixed()
                && !$assign_value_type->hasArrayAccessInterface($codebase)
            ) {
                if (IssueBuffer::accepts(
                    new InvalidArrayOffset(
                        'Cannot destructure non-array of type ' . $assign_value_type->getId(),
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $can_be_empty = true;

            foreach ($assign_var->items as $offset => $assign_var_item) {
                // $assign_var_item can be null e.g. list($a, ) = ['a', 'b']
                if (!$assign_var_item) {
                    continue;
                }

                $var = $assign_var_item->value;

                if ($assign_value instanceof PhpParser\Node\Expr\Array_
                    && $statements_analyzer->node_data->getType($assign_var_item->value)
                ) {
                    self::analyze(
                        $statements_analyzer,
                        $var,
                        $assign_var_item->value,
                        null,
                        $context,
                        $doc_comment
                    );

                    continue;
                }

                $offset_value = null;

                if (!$assign_var_item->key) {
                    $offset_value = $offset;
                } elseif ($assign_var_item->key instanceof PhpParser\Node\Scalar\String_) {
                    $offset_value = $assign_var_item->key->value;
                }

                $list_var_id = ExpressionIdentifier::getArrayVarId(
                    $var,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer
                );

                $new_assign_type = null;
                $assigned = false;
                $has_null = false;

                foreach ($assign_value_type->getAtomicTypes() as $assign_value_atomic_type) {
                    if ($assign_value_atomic_type instanceof Type\Atomic\TKeyedArray
                        && !$assign_var_item->key
                    ) {
                        // if object-like has int offsets
                        if ($offset_value !== null
                            && isset($assign_value_atomic_type->properties[$offset_value])
                        ) {
                            $value_type = $assign_value_atomic_type->properties[$offset_value];

                            if ($value_type->possibly_undefined) {
                                if (IssueBuffer::accepts(
                                    new PossiblyUndefinedArrayOffset(
                                        'Possibly undefined array key',
                                        new CodeLocation($statements_analyzer->getSource(), $var)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }

                                $value_type = clone $value_type;
                                $value_type->possibly_undefined = false;
                            }

                            if ($statements_analyzer->data_flow_graph
                                && $assign_value
                            ) {
                                $assign_value_id = ExpressionIdentifier::getArrayVarId(
                                    $assign_value,
                                    $statements_analyzer->getFQCLN(),
                                    $statements_analyzer
                                );

                                $keyed_array_var_id = null;

                                if ($assign_value_id) {
                                    $keyed_array_var_id = $assign_value_id . '[\'' . $offset_value . '\']';
                                }

                                ArrayFetchAnalyzer::taintArrayFetch(
                                    $statements_analyzer,
                                    $assign_value,
                                    $keyed_array_var_id,
                                    $value_type,
                                    Type::getString((string) $offset_value)
                                );
                            }

                            self::analyze(
                                $statements_analyzer,
                                $var,
                                null,
                                $value_type,
                                $context,
                                $doc_comment
                            );

                            $assigned = true;

                            continue;
                        }

                        if ($assign_value_atomic_type->sealed) {
                            if (IssueBuffer::accepts(
                                new InvalidArrayOffset(
                                    'Cannot access value with offset ' . $offset,
                                    new CodeLocation($statements_analyzer->getSource(), $var)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }

                    if ($assign_value_atomic_type instanceof Type\Atomic\TMixed) {
                        if (IssueBuffer::accepts(
                            new MixedArrayAccess(
                                'Cannot access array value on mixed variable ' . $array_var_id,
                                new CodeLocation($statements_analyzer->getSource(), $var)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } elseif ($assign_value_atomic_type instanceof Type\Atomic\TNull) {
                        $has_null = true;

                        if (IssueBuffer::accepts(
                            new PossiblyNullArrayAccess(
                                'Cannot access array value on null variable ' . $array_var_id,
                                new CodeLocation($statements_analyzer->getSource(), $var)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )
                        ) {
                            // do nothing
                        }
                    } elseif (!$assign_value_atomic_type instanceof Type\Atomic\TArray
                        && !$assign_value_atomic_type instanceof Type\Atomic\TKeyedArray
                        && !$assign_value_atomic_type instanceof Type\Atomic\TList
                        && !$assign_value_type->hasArrayAccessInterface($codebase)
                    ) {
                        if ($assign_value_type->hasArray()) {
                            if (($assign_value_atomic_type instanceof Type\Atomic\TFalse
                                    && $assign_value_type->ignore_falsable_issues)
                                || ($assign_value_atomic_type instanceof Type\Atomic\TNull
                                    && $assign_value_type->ignore_nullable_issues)
                            ) {
                                // do nothing
                            } elseif (IssueBuffer::accepts(
                                new PossiblyInvalidArrayAccess(
                                    'Cannot access array value on non-array variable '
                                        . $array_var_id . ' of type ' . $assign_value_atomic_type->getId(),
                                    new CodeLocation($statements_analyzer->getSource(), $var)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )
                            ) {
                                // do nothing
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new InvalidArrayAccess(
                                    'Cannot access array value on non-array variable '
                                        . $array_var_id . ' of type ' . $assign_value_atomic_type->getId(),
                                    new CodeLocation($statements_analyzer->getSource(), $var)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )
                            ) {
                                // do nothing
                            }
                        }
                    }

                    if ($var instanceof PhpParser\Node\Expr\List_
                        || $var instanceof PhpParser\Node\Expr\Array_
                    ) {
                        if ($assign_value_atomic_type instanceof Type\Atomic\TKeyedArray) {
                            $assign_value_atomic_type = $assign_value_atomic_type->getGenericArrayType();
                        }

                        if ($assign_value_atomic_type instanceof Type\Atomic\TList) {
                            $assign_value_atomic_type = new Type\Atomic\TArray([
                                Type::getInt(),
                                $assign_value_atomic_type->type_param
                            ]);
                        }

                        $array_value_type = $assign_value_atomic_type instanceof Type\Atomic\TArray
                            ? clone $assign_value_atomic_type->type_params[1]
                            : Type::getMixed();

                        self::analyze(
                            $statements_analyzer,
                            $var,
                            null,
                            $array_value_type,
                            $context,
                            $doc_comment
                        );

                        continue;
                    }

                    if ($list_var_id) {
                        $context->vars_possibly_in_scope[$list_var_id] = true;
                        $context->assigned_var_ids[$list_var_id] = true;
                        $context->possibly_assigned_var_ids[$list_var_id] = true;

                        $already_in_scope = isset($context->vars_in_scope[$list_var_id]);

                        if (strpos($list_var_id, '-') === false && strpos($list_var_id, '[') === false) {
                            $location = new CodeLocation($statements_analyzer, $var);

                            if (!$statements_analyzer->hasVariable($list_var_id)) {
                                $statements_analyzer->registerVariable(
                                    $list_var_id,
                                    $location,
                                    $context->branch_point
                                );
                            } else {
                                $statements_analyzer->registerVariableAssignment(
                                    $list_var_id,
                                    $location
                                );
                            }

                            if (isset($context->byref_constraints[$list_var_id])) {
                                // something
                            }
                        }

                        if ($assign_value_atomic_type instanceof Type\Atomic\TArray) {
                            $new_assign_type = clone $assign_value_atomic_type->type_params[1];

                            if ($statements_analyzer->data_flow_graph
                                && $assign_value
                            ) {
                                ArrayFetchAnalyzer::taintArrayFetch(
                                    $statements_analyzer,
                                    $assign_value,
                                    null,
                                    $new_assign_type,
                                    Type::getArrayKey()
                                );
                            }

                            $can_be_empty = !$assign_value_atomic_type instanceof Type\Atomic\TNonEmptyArray;
                        } elseif ($assign_value_atomic_type instanceof Type\Atomic\TList) {
                            $new_assign_type = clone $assign_value_atomic_type->type_param;

                            if ($statements_analyzer->data_flow_graph && $assign_value) {
                                ArrayFetchAnalyzer::taintArrayFetch(
                                    $statements_analyzer,
                                    $assign_value,
                                    null,
                                    $new_assign_type,
                                    Type::getArrayKey()
                                );
                            }

                            $can_be_empty = !$assign_value_atomic_type instanceof Type\Atomic\TNonEmptyList;
                        } elseif ($assign_value_atomic_type instanceof Type\Atomic\TKeyedArray) {
                            if ($assign_var_item->key
                                && ($assign_var_item->key instanceof PhpParser\Node\Scalar\String_
                                    || $assign_var_item->key instanceof PhpParser\Node\Scalar\LNumber)
                                && isset($assign_value_atomic_type->properties[$assign_var_item->key->value])
                            ) {
                                $new_assign_type =
                                    clone $assign_value_atomic_type->properties[$assign_var_item->key->value];

                                if ($new_assign_type->possibly_undefined) {
                                    if (IssueBuffer::accepts(
                                        new PossiblyUndefinedArrayOffset(
                                            'Possibly undefined array key',
                                            new CodeLocation($statements_analyzer->getSource(), $var)
                                        ),
                                        $statements_analyzer->getSuppressedIssues()
                                    )) {
                                        // fall through
                                    }

                                    $new_assign_type->possibly_undefined = false;
                                }
                            }

                            if ($statements_analyzer->data_flow_graph && $assign_value && $new_assign_type) {
                                ArrayFetchAnalyzer::taintArrayFetch(
                                    $statements_analyzer,
                                    $assign_value,
                                    null,
                                    $new_assign_type,
                                    Type::getArrayKey()
                                );
                            }

                            $can_be_empty = !$assign_value_atomic_type->sealed;
                        } elseif ($assign_value_atomic_type->hasArrayAccessInterface($codebase)) {
                            ForeachAnalyzer::getKeyValueParamsForTraversableObject(
                                $assign_value_atomic_type,
                                $codebase,
                                $array_access_key_type,
                                $array_access_value_type
                            );

                            $new_assign_type = $array_access_value_type;
                        }

                        if ($already_in_scope) {
                            // removes dependennt vars from $context
                            $context->removeDescendents(
                                $list_var_id,
                                $context->vars_in_scope[$list_var_id],
                                $new_assign_type,
                                $statements_analyzer
                            );
                        }
                    }
                }

                if (!$assigned) {
                    foreach ($var_comments as $var_comment) {
                        if (!$var_comment->type) {
                            continue;
                        }

                        try {
                            if ($var_comment->var_id === $list_var_id) {
                                $var_comment_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                                    $codebase,
                                    $var_comment->type,
                                    $context->self,
                                    $context->self,
                                    $statements_analyzer->getParentFQCLN()
                                );

                                $var_comment_type->setFromDocblock();

                                $new_assign_type = $var_comment_type;
                                break;
                            }
                        } catch (\UnexpectedValueException $e) {
                            if (IssueBuffer::accepts(
                                new InvalidDocblock(
                                    (string)$e->getMessage(),
                                    new CodeLocation($statements_analyzer->getSource(), $assign_var)
                                )
                            )) {
                                // fall through
                            }
                        }
                    }

                    if ($list_var_id) {
                        $context->vars_in_scope[$list_var_id] = $new_assign_type ?: Type::getMixed();

                        if (($context->error_suppressing && ($offset || $can_be_empty))
                            || $has_null
                        ) {
                            $context->vars_in_scope[$list_var_id]->addType(new Type\Atomic\TNull);
                        }

                        if ($statements_analyzer->data_flow_graph) {
                            $data_flow_graph = $statements_analyzer->data_flow_graph;

                            $var_location = new CodeLocation($statements_analyzer->getSource(), $var);

                            if (!$context->vars_in_scope[$list_var_id]->parent_nodes) {
                                $assignment_node = DataFlowNode::getForAssignment(
                                    $list_var_id,
                                    $var_location
                                );

                                $context->vars_in_scope[$list_var_id]->parent_nodes = [
                                    $assignment_node->id => $assignment_node
                                ];
                            } else {
                                if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                                    && \in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                                ) {
                                    $context->vars_in_scope[$list_var_id]->parent_nodes = [];
                                } else {
                                    $new_parent_node = DataFlowNode::getForAssignment($list_var_id, $var_location);

                                    $statements_analyzer->data_flow_graph->addNode($new_parent_node);

                                    foreach ($context->vars_in_scope[$list_var_id]->parent_nodes as $parent_node) {
                                        $data_flow_graph->addPath(
                                            $parent_node,
                                            $new_parent_node,
                                            '=',
                                            [],
                                            $removed_taints
                                        );
                                    }

                                    $context->vars_in_scope[$list_var_id]->parent_nodes = [
                                        $new_parent_node->id => $new_parent_node
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            ArrayAssignmentAnalyzer::analyze(
                $statements_analyzer,
                $assign_var,
                $context,
                $assign_value,
                $assign_value_type
            );
        } elseif ($assign_var instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (!$assign_var->name instanceof PhpParser\Node\Identifier) {
                $was_inside_use = $context->inside_use;
                $context->inside_use = true;

                // this can happen when the user actually means to type $this-><autocompleted>, but there's
                // a variable on the next line
                if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_var->var, $context) === false) {
                    return false;
                }

                if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_var->name, $context) === false) {
                    return false;
                }

                $context->inside_use = $was_inside_use;
            }

            if ($assign_var->name instanceof PhpParser\Node\Identifier) {
                $prop_name = $assign_var->name->name;
            } elseif (($assign_var_name_type = $statements_analyzer->node_data->getType($assign_var->name))
                && $assign_var_name_type->isSingleStringLiteral()
            ) {
                $prop_name = $assign_var_name_type->getSingleStringLiteral()->value;
            } else {
                $prop_name = null;
            }

            if ($prop_name) {
                InstancePropertyAssignmentAnalyzer::analyze(
                    $statements_analyzer,
                    $assign_var,
                    $prop_name,
                    $assign_value,
                    $assign_value_type,
                    $context
                );
            } else {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_var->var, $context) === false) {
                    return false;
                }

                if (($assign_var_type = $statements_analyzer->node_data->getType($assign_var->var))
                    && !$context->ignore_variable_property
                ) {
                    $stmt_var_type = $assign_var_type;

                    if ($stmt_var_type->hasObjectType()) {
                        foreach ($stmt_var_type->getAtomicTypes() as $type) {
                            if ($type instanceof Type\Atomic\TNamedObject) {
                                $codebase->analyzer->addMixedMemberName(
                                    strtolower($type->value) . '::$',
                                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                                );
                            }
                        }
                    }
                }
            }

            if ($var_id) {
                $context->vars_possibly_in_scope[$var_id] = true;
            }

            $property_var_pure_compatible = $statements_analyzer->node_data->isPureCompatible($assign_var->var);

            // prevents writing to any properties in a mutation-free context
            if (!$property_var_pure_compatible
                && !$context->collect_mutations
                && !$context->collect_initializations
            ) {
                if ($context->mutation_free || $context->external_mutation_free) {
                    if (IssueBuffer::accepts(
                        new ImpurePropertyAssignment(
                            'Cannot assign to a property from a mutation-free context',
                            new CodeLocation($statements_analyzer, $assign_var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } elseif ($statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                    && $statements_analyzer->getSource()->track_mutations
                ) {
                    if (!$assign_var->var instanceof PhpParser\Node\Expr\Variable
                        || $assign_var->var->name !== 'this'
                    ) {
                        $statements_analyzer->getSource()->inferred_has_mutation = true;
                    }

                    $statements_analyzer->getSource()->inferred_impure = true;
                }
            }
        } elseif ($assign_var instanceof PhpParser\Node\Expr\StaticPropertyFetch &&
            $assign_var->class instanceof PhpParser\Node\Name
        ) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $assign_var, $context) === false) {
                return false;
            }

            if ($context->check_classes) {
                StaticPropertyAssignmentAnalyzer::analyze(
                    $statements_analyzer,
                    $assign_var,
                    $assign_value,
                    $assign_value_type,
                    $context
                );
            }

            if ($var_id) {
                $context->vars_possibly_in_scope[$var_id] = true;
            }
        }

        if ($var_id && isset($context->vars_in_scope[$var_id])) {
            if ($context->vars_in_scope[$var_id]->isVoid()) {
                if (IssueBuffer::accepts(
                    new AssignmentToVoid(
                        'Cannot assign ' . $var_id . ' to type void',
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                $context->vars_in_scope[$var_id] = Type::getNull();

                if (!$was_in_assignment) {
                    $context->inside_assignment = false;
                }

                return $context->vars_in_scope[$var_id];
            }

            if ($context->vars_in_scope[$var_id]->isNever()) {
                if (IssueBuffer::accepts(
                    new NoValue(
                        'This function or method call never returns output',
                        new CodeLocation($statements_analyzer->getSource(), $assign_var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                $context->vars_in_scope[$var_id] = Type::getEmpty();

                if (!$was_in_assignment) {
                    $context->inside_assignment = false;
                }

                return $context->vars_in_scope[$var_id];
            }

            if ($statements_analyzer->data_flow_graph) {
                $data_flow_graph = $statements_analyzer->data_flow_graph;

                if ($context->vars_in_scope[$var_id]->parent_nodes) {
                    if ($data_flow_graph instanceof TaintFlowGraph
                        && \in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                    ) {
                        $context->vars_in_scope[$var_id]->parent_nodes = [];
                    } else {
                        $var_location = new CodeLocation($statements_analyzer->getSource(), $assign_var);

                        $new_parent_node = DataFlowNode::getForAssignment($var_id, $var_location);

                        $data_flow_graph->addNode($new_parent_node);

                        foreach ($context->vars_in_scope[$var_id]->parent_nodes as $parent_node) {
                            $data_flow_graph->addPath($parent_node, $new_parent_node, '=', [], $removed_taints);
                        }

                        $context->vars_in_scope[$var_id]->parent_nodes = [
                            $new_parent_node->id => $new_parent_node
                        ];
                    }
                }
            }
        }

        if (!$was_in_assignment) {
            $context->inside_assignment = false;
        }

        return $assign_value_type;
    }

    public static function assignTypeFromVarDocblock(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node $stmt,
        VarDocblockComment $var_comment,
        Context $context,
        ?string $var_id = null,
        ?Type\Union &$comment_type = null,
        ?CodeLocation\DocblockTypeLocation &$comment_type_location = null,
        array $not_ignored_docblock_var_ids = []
    ) : void {
        if (!$var_comment->type) {
            return;
        }

        $codebase = $statements_analyzer->getCodebase();

        try {
            $var_comment_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $var_comment->type,
                $context->self,
                $context->self,
                $statements_analyzer->getParentFQCLN()
            );

            $var_comment_type->setFromDocblock();

            $var_comment_type->check(
                $statements_analyzer,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues(),
                [],
                false,
                false,
                false,
                $context->calling_method_id
            );

            $type_location = null;

            if ($var_comment->type_start
                && $var_comment->type_end
                && $var_comment->line_number
            ) {
                $type_location = new CodeLocation\DocblockTypeLocation(
                    $statements_analyzer,
                    $var_comment->type_start,
                    $var_comment->type_end,
                    $var_comment->line_number
                );

                if ($codebase->alter_code) {
                    $codebase->classlikes->handleDocblockTypeInMigration(
                        $codebase,
                        $statements_analyzer,
                        $var_comment_type,
                        $type_location,
                        $context->calling_method_id
                    );
                }
            }

            if (!$var_comment->var_id || $var_comment->var_id === $var_id) {
                $comment_type = $var_comment_type;
                $comment_type_location = $type_location;
                return;
            }

            $project_analyzer = $statements_analyzer->getProjectAnalyzer();

            if ($codebase->find_unused_variables
                && $type_location
                && (!$not_ignored_docblock_var_ids || isset($not_ignored_docblock_var_ids[$var_comment->var_id]))
                && isset($context->vars_in_scope[$var_comment->var_id])
                && $context->vars_in_scope[$var_comment->var_id]->getId() === $var_comment_type->getId()
                && !$var_comment_type->isMixed()
            ) {
                if ($codebase->alter_code
                    && isset($project_analyzer->getIssuesToFix()['UnnecessaryVarAnnotation'])
                ) {
                    FileManipulationBuffer::addVarAnnotationToRemove($type_location);
                } elseif (IssueBuffer::accepts(
                    new UnnecessaryVarAnnotation(
                        'The @var ' . $var_comment_type . ' annotation for '
                            . $var_comment->var_id . ' is unnecessary',
                        $type_location
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                    true
                )) {
                    // fall through
                }
            }

            $parent_nodes = $context->vars_in_scope[$var_comment->var_id]->parent_nodes ?? [];
            $var_comment_type->parent_nodes = $parent_nodes;

            $context->vars_in_scope[$var_comment->var_id] = $var_comment_type;
        } catch (\UnexpectedValueException $e) {
            if (IssueBuffer::accepts(
                new InvalidDocblock(
                    (string)$e->getMessage(),
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                )
            )) {
                // fall through
            }
        }
    }

    public static function analyzeAssignmentOperation(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\AssignOp $stmt,
        Context $context
    ): bool {
        $array_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($stmt instanceof PhpParser\Node\Expr\AssignOp\Coalesce) {
            $old_data_provider = $statements_analyzer->node_data;

            $statements_analyzer->node_data = clone $statements_analyzer->node_data;

            $fake_coalesce_expr = new PhpParser\Node\Expr\BinaryOp\Coalesce(
                $stmt->var,
                $stmt->expr,
                $stmt->getAttributes()
            );

            $fake_coalesce_type = AssignmentAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $fake_coalesce_expr,
                null,
                $context,
                $stmt->getDocComment()
            );

            $statements_analyzer->node_data = $old_data_provider;

            if ($fake_coalesce_type) {
                if ($array_var_id) {
                    $context->vars_in_scope[$array_var_id] = $fake_coalesce_type;
                }

                $statements_analyzer->node_data->setType($stmt, $fake_coalesce_type);
            }

            return true;
        }

        $was_in_assignment = $context->inside_assignment;

        $context->inside_assignment = true;

        $root_expr = $stmt->var;

        while ($root_expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $root_expr = $root_expr->var;
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            return false;
        }

        $was_inside_use = $context->inside_use;

        // if we don't know where this data is going, treat as a dead-end usage
        if (!$root_expr instanceof PhpParser\Node\Expr\Variable
            || (\is_string($root_expr->name)
                && \in_array('$' . $root_expr->name, VariableFetchAnalyzer::SUPER_GLOBALS, true))
        ) {
            $context->inside_use = true;
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        $context->inside_use = $was_inside_use;

        if ($array_var_id
            && $stmt->var instanceof PhpParser\Node\Expr\PropertyFetch
            && ($stmt_var_var_type = $statements_analyzer->node_data->getType($stmt->var->var))
            && !$stmt_var_var_type->reference_free
        ) {
            if ($context->mutation_free) {
                if (IssueBuffer::accepts(
                    new ImpurePropertyAssignment(
                        'Cannot assign to a property from a mutation-free context',
                        new CodeLocation($statements_analyzer, $stmt->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                && $statements_analyzer->getSource()->track_mutations
            ) {
                $statements_analyzer->getSource()->inferred_has_mutation = true;
                $statements_analyzer->getSource()->inferred_impure = true;
            }
        } elseif (!$context->collect_mutations
            && !$context->collect_initializations
            && $stmt->var instanceof PhpParser\Node\Expr\PropertyFetch
        ) {
            $lhs_var_id = ExpressionIdentifier::getArrayVarId(
                $stmt->var->var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($context->mutation_free) {
                if (isset($context->vars_in_scope[$lhs_var_id])
                    && !$context->vars_in_scope[$lhs_var_id]->allow_mutations
                ) {
                    if (IssueBuffer::accepts(
                        new ImpurePropertyAssignment(
                            'Cannot assign to a property from a mutation-free context',
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } elseif ($statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                && $statements_analyzer->getSource()->track_mutations
            ) {
                $statements_analyzer->getSource()->inferred_has_mutation = true;
                $statements_analyzer->getSource()->inferred_impure = true;
            }
        }

        $codebase = $statements_analyzer->getCodebase();

        if ($array_var_id) {
            $context->assigned_var_ids[$array_var_id] = true;
            $context->possibly_assigned_var_ids[$array_var_id] = true;

            if ($codebase->find_unused_variables && $stmt->var instanceof PhpParser\Node\Expr\Variable) {
                $location = new CodeLocation($statements_analyzer, $stmt->var);
                $statements_analyzer->registerVariableAssignment(
                    $array_var_id,
                    $location
                );
            }
        }

        $stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);
        $stmt_var_type = $stmt_var_type ? clone $stmt_var_type: null;

        $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);
        $result_type = null;

        if ($stmt instanceof PhpParser\Node\Expr\AssignOp\Plus
            || $stmt instanceof PhpParser\Node\Expr\AssignOp\Minus
            || $stmt instanceof PhpParser\Node\Expr\AssignOp\Mod
            || $stmt instanceof PhpParser\Node\Expr\AssignOp\Mul
            || $stmt instanceof PhpParser\Node\Expr\AssignOp\Pow
        ) {
            BinaryOp\NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->var,
                $stmt->expr,
                $stmt,
                $result_type,
                $context
            );

            if ($stmt->var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
                $result_type = $result_type ?: Type::getMixed($context->inside_loop);

                ArrayAssignmentAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->var,
                    $context,
                    $stmt->expr,
                    $result_type
                );
            } else {
                $result_type = $result_type
                    ? clone $result_type
                    : new Type\Union([new Type\Atomic\TInt(), new Type\Atomic\TFloat()]);
            }

            if ($array_var_id) {
                $context->vars_in_scope[$array_var_id] = $result_type;
            }

            $statements_analyzer->node_data->setType($stmt, $result_type);

            BinaryOpAnalyzer::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->var,
                $stmt->expr,
                'nondivop'
            );
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp\Div) {
            if ($stmt_var_type
                && $stmt_expr_type
                && $stmt_var_type->hasDefinitelyNumericType()
                && $stmt_expr_type->hasDefinitelyNumericType()
                && $array_var_id
            ) {
                $context->vars_in_scope[$array_var_id] = Type::combineUnionTypes(Type::getFloat(), Type::getInt());
                $statements_analyzer->node_data->setType($stmt, clone $context->vars_in_scope[$array_var_id]);
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            }

            BinaryOpAnalyzer::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->var,
                $stmt->expr,
                'div'
            );
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp\Concat) {
            BinaryOp\ConcatAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $stmt->expr,
                $context,
                $result_type
            );

            if ($result_type && $array_var_id) {
                $context->vars_in_scope[$array_var_id] = $result_type;
                $statements_analyzer->node_data->setType($stmt, clone $context->vars_in_scope[$array_var_id]);

                BinaryOpAnalyzer::addDataFlow(
                    $statements_analyzer,
                    $stmt,
                    $stmt->var,
                    $stmt->expr,
                    'concatop'
                );
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp\BitwiseOr
            || $stmt instanceof PhpParser\Node\Expr\AssignOp\BitwiseXor
            || $stmt instanceof PhpParser\Node\Expr\AssignOp\BitwiseAnd
            || $stmt instanceof PhpParser\Node\Expr\AssignOp\ShiftLeft
            || $stmt instanceof PhpParser\Node\Expr\AssignOp\ShiftRight
        ) {
            BinaryOp\NonDivArithmeticOpAnalyzer::analyze(
                $statements_analyzer,
                $statements_analyzer->node_data,
                $stmt->var,
                $stmt->expr,
                $stmt,
                $result_type,
                $context
            );

            if ($result_type && $array_var_id) {
                $context->vars_in_scope[$array_var_id] = clone $result_type;
                $statements_analyzer->node_data->setType($stmt, $context->vars_in_scope[$array_var_id]);
            }

            BinaryOpAnalyzer::addDataFlow(
                $statements_analyzer,
                $stmt,
                $stmt->var,
                $stmt->expr,
                'bitwiseop'
            );
        }

        if ($statements_analyzer->data_flow_graph
            && $array_var_id
            && isset($context->vars_in_scope[$array_var_id])
            && ($stmt_type = $statements_analyzer->node_data->getType($stmt))
        ) {
            $data_flow_graph = $statements_analyzer->data_flow_graph;

            if ($stmt_type->parent_nodes) {
                if ($data_flow_graph instanceof TaintFlowGraph
                    && \in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                ) {
                    $stmt_type->parent_nodes = [];
                } else {
                    $var_location = new CodeLocation($statements_analyzer->getSource(), $stmt->var);

                    $new_parent_node = DataFlowNode::getForAssignment($array_var_id, $var_location);

                    $data_flow_graph->addNode($new_parent_node);

                    foreach ($stmt_type->parent_nodes as $parent_node) {
                        $data_flow_graph->addPath($parent_node, $new_parent_node, '=');
                    }

                    $context->vars_in_scope[$array_var_id]->parent_nodes = [
                        $new_parent_node->id => $new_parent_node
                    ];
                }
            }
        }

        if ($array_var_id && isset($context->vars_in_scope[$array_var_id])) {
            if ($result_type && $context->vars_in_scope[$array_var_id]->by_ref) {
                $result_type->by_ref = true;
            }

            // removes dependent vars from $context
            $context->removeDescendents(
                $array_var_id,
                $context->vars_in_scope[$array_var_id],
                $result_type,
                $statements_analyzer
            );
        } else {
            $root_var_id = ExpressionIdentifier::getRootVarId(
                $stmt->var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($root_var_id && isset($context->vars_in_scope[$root_var_id])) {
                $context->removeVarFromConflictingClauses(
                    $root_var_id,
                    $context->vars_in_scope[$root_var_id],
                    $statements_analyzer
                );
            }
        }

        if (!($stmt instanceof PhpParser\Node\Expr\AssignOp\Plus
                || $stmt instanceof PhpParser\Node\Expr\AssignOp\Minus
                || $stmt instanceof PhpParser\Node\Expr\AssignOp\Mod
                || $stmt instanceof PhpParser\Node\Expr\AssignOp\Mul
                || $stmt instanceof PhpParser\Node\Expr\AssignOp\Pow)
            && $stmt->var instanceof PhpParser\Node\Expr\ArrayDimFetch
        ) {
            ArrayAssignmentAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $context,
                null,
                $result_type ?: Type::getEmpty()
            );
        }

        if (!$was_in_assignment) {
            $context->inside_assignment = false;
        }

        return true;
    }

    public static function analyzeAssignmentRef(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\AssignRef $stmt,
        Context $context
    ) : bool {
        $assignment_type = self::analyze(
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

        $assignment_type->by_ref = true;

        $lhs_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $rhs_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($lhs_var_id) {
            $context->vars_in_scope[$lhs_var_id] = $assignment_type;
            $context->hasVariable($lhs_var_id);
            $context->byref_constraints[$lhs_var_id] = new \Psalm\Internal\ReferenceConstraint();

            if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
                foreach ($context->vars_in_scope[$lhs_var_id]->parent_nodes as $parent_node) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode('variable-use', 'variable use', null),
                        'variable-use'
                    );
                }
            }
        }

        if ($rhs_var_id) {
            if (!isset($context->vars_in_scope[$rhs_var_id])) {
                $context->vars_in_scope[$rhs_var_id] = Type::getMixed();
            }

            $context->byref_constraints[$rhs_var_id] = new \Psalm\Internal\ReferenceConstraint();
        }

        if ($statements_analyzer->data_flow_graph
            && $lhs_var_id
            && $rhs_var_id
            && isset($context->vars_in_scope[$rhs_var_id])
        ) {
            $rhs_type = $context->vars_in_scope[$rhs_var_id];

            $data_flow_graph = $statements_analyzer->data_flow_graph;

            $lhs_location = new CodeLocation($statements_analyzer->getSource(), $stmt->var);

            $lhs_node = DataFlowNode::getForAssignment($lhs_var_id, $lhs_location);

            foreach ($rhs_type->parent_nodes as $byref_destination_node) {
                $data_flow_graph->addPath($lhs_node, $byref_destination_node, '=');
            }
        }

        return true;
    }

    public static function assignByRefParam(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Type\Union $by_ref_type,
        Type\Union $by_ref_out_type,
        Context $context,
        bool $constrain_type = true,
        bool $prevent_null = false
    ): void {
        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && $stmt->name instanceof PhpParser\Node\Identifier) {
            $prop_name = $stmt->name->name;

            InstancePropertyAssignmentAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $prop_name,
                null,
                $by_ref_out_type,
                $context
            );

            return;
        }

        $var_id = ExpressionIdentifier::getVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($var_id) {
            $var_not_in_scope = false;

            if (!$by_ref_type->hasMixed() && $constrain_type) {
                $context->byref_constraints[$var_id] = new \Psalm\Internal\ReferenceConstraint($by_ref_type);
            }

            if (!$context->hasVariable($var_id)) {
                $context->vars_possibly_in_scope[$var_id] = true;

                if (!$statements_analyzer->hasVariable($var_id)) {
                    if ($constrain_type
                        && $prevent_null
                        && !$by_ref_type->isMixed()
                        && !$by_ref_type->isNullable()
                        && !strpos($var_id, '->')
                        && !strpos($var_id, '::')
                    ) {
                        if (IssueBuffer::accepts(
                            new \Psalm\Issue\NullReference(
                                'Not expecting null argument passed by reference',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    $context->hasVariable($var_id);
                } else {
                    $var_not_in_scope = true;
                }
            } elseif ($var_id === '$this') {
                // don't allow changing $this
                return;
            } else {
                $existing_type = $context->vars_in_scope[$var_id];

                // removes dependent vars from $context
                $context->removeDescendents(
                    $var_id,
                    $existing_type,
                    $by_ref_type,
                    $statements_analyzer
                );

                $by_ref_out_type = clone $by_ref_out_type;

                if ($existing_type->parent_nodes) {
                    $by_ref_out_type->parent_nodes += $existing_type->parent_nodes;
                }

                if ($existing_type->getId() !== 'array<empty, empty>') {
                    $context->vars_in_scope[$var_id] = $by_ref_out_type;

                    if (!($stmt_type = $statements_analyzer->node_data->getType($stmt))
                        || $stmt_type->isEmpty()
                    ) {
                        $statements_analyzer->node_data->setType($stmt, clone $by_ref_type);
                    }

                    return;
                }
            }

            $context->assigned_var_ids[$var_id] = true;

            $context->vars_in_scope[$var_id] = $by_ref_out_type;

            $stmt_type = $statements_analyzer->node_data->getType($stmt);

            if (!$stmt_type || $stmt_type->isEmpty()) {
                $statements_analyzer->node_data->setType($stmt, clone $by_ref_type);
            }

            if ($var_not_in_scope && $stmt instanceof PhpParser\Node\Expr\Variable) {
                $statements_analyzer->registerPossiblyUndefinedVariable($var_id, $stmt);
            }
        }
    }
}
