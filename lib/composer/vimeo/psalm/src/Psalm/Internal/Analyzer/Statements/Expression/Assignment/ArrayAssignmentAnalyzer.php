<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use function array_reverse;
use function array_shift;
use function count;
use function array_unshift;
use function preg_match;
use function is_string;
use function implode;
use function array_pop;

/**
 * @internal
 */
class ArrayAssignmentAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        ?PhpParser\Node\Expr $assign_value,
        Type\Union $assignment_value_type
    ): void {
        $nesting = 0;
        $var_id = ExpressionIdentifier::getVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
            $nesting
        );

        self::updateArrayType(
            $statements_analyzer,
            $stmt,
            $assign_value,
            $assignment_value_type,
            $context
        );

        if (!$statements_analyzer->node_data->getType($stmt->var) && $var_id) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
        }
    }

    /**
     * @return false|null
     */
    public static function updateArrayType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        ?PhpParser\Node\Expr $assign_value,
        Type\Union $assignment_type,
        Context $context
    ): ?bool {
        $root_array_expr = $stmt;

        $child_stmts = [];

        while ($root_array_expr->var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $child_stmts[] = $root_array_expr;
            $root_array_expr = $root_array_expr->var;
        }

        $child_stmts[] = $root_array_expr;
        $root_array_expr = $root_array_expr->var;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $root_array_expr,
            $context,
            true
        ) === false) {
            // fall through
        }

        $codebase = $statements_analyzer->getCodebase();

        $root_type = $statements_analyzer->node_data->getType($root_array_expr) ?: Type::getMixed();

        if ($root_type->hasMixed()) {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $context,
                true
            ) === false) {
                // fall through
            }

            if ($stmt->dim) {
                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->dim,
                    $context
                ) === false) {
                    // fall through
                }
            }
        }

        $child_stmts = array_reverse($child_stmts);

        $current_type = $root_type;

        $current_dim = $stmt->dim;

        $reversed_child_stmts = [];

        // gets a variable id that *may* contain array keys
        $root_var_id = ExpressionIdentifier::getArrayVarId(
            $root_array_expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $var_id_additions = [];

        $parent_var_id = null;

        $offset_already_existed = false;
        $full_var_id = true;

        $child_stmt = null;

        // First go from the root element up, and go as far as we can to figure out what
        // array types there are
        while ($child_stmts) {
            $child_stmt = array_shift($child_stmts);

            if (count($child_stmts)) {
                array_unshift($reversed_child_stmts, $child_stmt);
            }

            $child_stmt_dim_type = null;

            $dim_value = null;

            if ($child_stmt->dim) {
                $was_inside_use = $context->inside_use;
                $context->inside_use = true;

                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $child_stmt->dim,
                    $context
                ) === false) {
                    return false;
                }

                $context->inside_use = $was_inside_use;

                if (!($child_stmt_dim_type = $statements_analyzer->node_data->getType($child_stmt->dim))) {
                    return null;
                }

                if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_
                    || (($child_stmt->dim instanceof PhpParser\Node\Expr\ConstFetch
                            || $child_stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch)
                       && $child_stmt_dim_type->isSingleStringLiteral())
                ) {
                    if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                        $dim_value = new Type\Atomic\TLiteralString($child_stmt->dim->value);
                    } else {
                        $dim_value = $child_stmt_dim_type->getSingleStringLiteral();
                    }

                    if (preg_match('/^(0|[1-9][0-9]*)$/', $dim_value->value)) {
                        $var_id_additions[] = '[' . $dim_value->value . ']';
                    } else {
                        $var_id_additions[] = '[\'' . $dim_value->value . '\']';
                    }
                } elseif ($child_stmt->dim instanceof PhpParser\Node\Scalar\LNumber
                    || (($child_stmt->dim instanceof PhpParser\Node\Expr\ConstFetch
                            || $child_stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch)
                        && $child_stmt_dim_type->isSingleIntLiteral())
                ) {
                    if ($child_stmt->dim instanceof PhpParser\Node\Scalar\LNumber) {
                        $dim_value = new Type\Atomic\TLiteralInt($child_stmt->dim->value);
                    } else {
                        $dim_value = $child_stmt_dim_type->getSingleIntLiteral();
                    }

                    $var_id_additions[] = '[' . $dim_value->value . ']';
                } elseif ($child_stmt->dim instanceof PhpParser\Node\Expr\Variable
                    && is_string($child_stmt->dim->name)
                ) {
                    $var_id_additions[] = '[$' . $child_stmt->dim->name . ']';
                } elseif ($child_stmt->dim instanceof PhpParser\Node\Expr\PropertyFetch
                    && $child_stmt->dim->name instanceof PhpParser\Node\Identifier
                ) {
                    $object_id = ExpressionIdentifier::getArrayVarId(
                        $child_stmt->dim->var,
                        $statements_analyzer->getFQCLN(),
                        $statements_analyzer
                    );

                    if ($object_id) {
                        $var_id_additions[] = '[' . $object_id . '->' . $child_stmt->dim->name->name . ']';
                    }
                } elseif ($child_stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $child_stmt->dim->name instanceof PhpParser\Node\Identifier
                    && $child_stmt->dim->class instanceof PhpParser\Node\Name
                ) {
                    $object_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                        $child_stmt->dim->class,
                        $statements_analyzer->getAliases()
                    );
                    $var_id_additions[] = '[' . $object_name . '::' . $child_stmt->dim->name->name . ']';
                } else {
                    $var_id_additions[] = '[' . $child_stmt_dim_type . ']';
                    $full_var_id = false;
                }
            } else {
                $var_id_additions[] = '';
                $full_var_id = false;
            }

            if (!($child_stmt_var_type = $statements_analyzer->node_data->getType($child_stmt->var))) {
                return null;
            }

            if ($child_stmt_var_type->isEmpty()) {
                $child_stmt_var_type = Type::getEmptyArray();
                $statements_analyzer->node_data->setType($child_stmt->var, $child_stmt_var_type);
            }

            $array_var_id = $root_var_id . implode('', $var_id_additions);

            if ($parent_var_id && isset($context->vars_in_scope[$parent_var_id])) {
                $child_stmt_var_type = clone $context->vars_in_scope[$parent_var_id];
                $statements_analyzer->node_data->setType($child_stmt->var, $child_stmt_var_type);
            }

            $array_type = clone $child_stmt_var_type;

            $child_stmt_type = ArrayFetchAnalyzer::getArrayAccessTypeGivenOffset(
                $statements_analyzer,
                $child_stmt,
                $array_type,
                $child_stmt_dim_type ?: Type::getInt(),
                true,
                $array_var_id,
                $context,
                $assign_value,
                $child_stmts ? null : $assignment_type
            );

            $statements_analyzer->node_data->setType(
                $child_stmt,
                $child_stmt_type
            );

            $statements_analyzer->node_data->setType($child_stmt->var, $array_type);

            if ($root_var_id) {
                if (!$parent_var_id) {
                    $rooted_parent_id = $root_var_id;
                    $root_type = $array_type;
                } else {
                    $rooted_parent_id = $parent_var_id;
                }

                $context->vars_in_scope[$rooted_parent_id] = $array_type;
                $context->possibly_assigned_var_ids[$rooted_parent_id] = true;
            }

            if (!$child_stmts) {
                // we need this slight hack as the type we're putting it has to be
                // different from the type we're getting out
                if ($array_type->isSingle() && $array_type->hasClassStringMap()) {
                    $assignment_type = $child_stmt_type;
                }

                $child_stmt_type = $assignment_type;
                $statements_analyzer->node_data->setType($child_stmt, $assignment_type);

                if ($statements_analyzer->data_flow_graph) {
                    self::taintArrayAssignment(
                        $statements_analyzer,
                        $child_stmt,
                        $array_type,
                        $assignment_type,
                        ExpressionIdentifier::getArrayVarId(
                            $child_stmt->var,
                            $statements_analyzer->getFQCLN(),
                            $statements_analyzer
                        ),
                        $dim_value !== null ? [$dim_value] : []
                    );
                }
            }

            $current_type = $child_stmt_type;
            $current_dim = $child_stmt->dim;

            $parent_var_id = $array_var_id;
        }

        if ($root_var_id
            && $full_var_id
            && $child_stmt
            && ($child_stmt_var_type = $statements_analyzer->node_data->getType($child_stmt->var))
            && !$child_stmt_var_type->hasObjectType()
        ) {
            $array_var_id = $root_var_id . implode('', $var_id_additions);
            $parent_var_id = $root_var_id . implode('', \array_slice($var_id_additions, 0, -1));

            if (isset($context->vars_in_scope[$array_var_id])
                && !$context->vars_in_scope[$array_var_id]->possibly_undefined
            ) {
                $offset_already_existed = true;
            }

            $context->vars_in_scope[$array_var_id] = clone $assignment_type;
            $context->possibly_assigned_var_ids[$array_var_id] = true;
        }

        // only update as many child stmts are we were able to process above
        foreach ($reversed_child_stmts as $child_stmt) {
            $child_stmt_type = $statements_analyzer->node_data->getType($child_stmt);

            if (!$child_stmt_type) {
                throw new \InvalidArgumentException('Should never get here');
            }

            $key_values = [];

            if ($current_dim instanceof PhpParser\Node\Scalar\String_) {
                $key_values[] = new Type\Atomic\TLiteralString($current_dim->value);
            } elseif ($current_dim instanceof PhpParser\Node\Scalar\LNumber) {
                $key_values[] = new Type\Atomic\TLiteralInt($current_dim->value);
            } elseif ($current_dim
                && ($current_dim_type = $statements_analyzer->node_data->getType($current_dim))
            ) {
                $string_literals = $current_dim_type->getLiteralStrings();
                $int_literals = $current_dim_type->getLiteralInts();

                $all_atomic_types = $current_dim_type->getAtomicTypes();

                if (count($string_literals) + count($int_literals) === count($all_atomic_types)) {
                    foreach ($string_literals as $string_literal) {
                        $key_values[] = clone $string_literal;
                    }

                    foreach ($int_literals as $int_literal) {
                        $key_values[] = clone $int_literal;
                    }
                }
            }

            if ($key_values) {
                $new_child_type = self::updateTypeWithKeyValues(
                    $codebase,
                    $child_stmt_type,
                    $current_type,
                    $key_values
                );
            } else {
                if (!$current_dim) {
                    $array_assignment_type = new Type\Union([
                        new TList($current_type),
                    ]);
                } else {
                    $current_dim_type = $statements_analyzer->node_data->getType($current_dim);

                    $array_assignment_type = new Type\Union([
                        new TArray([
                            $current_dim_type && !$current_dim_type->hasMixed()
                                ? $current_dim_type
                                : Type::getArrayKey(),
                            $current_type,
                        ]),
                    ]);
                }

                $new_child_type = Type::combineUnionTypes(
                    $child_stmt_type,
                    $array_assignment_type,
                    $codebase,
                    true,
                    true
                );
            }

            $new_child_type->removeType('null');
            $new_child_type->possibly_undefined = false;

            if (!$child_stmt_type->hasObjectType()) {
                $child_stmt_type = $new_child_type;
                $statements_analyzer->node_data->setType($child_stmt, $new_child_type);
            }

            $current_type = $child_stmt_type;
            $current_dim = $child_stmt->dim;

            array_pop($var_id_additions);

            $parent_array_var_id = null;

            if ($root_var_id) {
                $array_var_id = $root_var_id . implode('', $var_id_additions);
                $parent_array_var_id = $root_var_id . implode('', \array_slice($var_id_additions, 0, -1));
                $context->vars_in_scope[$array_var_id] = clone $child_stmt_type;
                $context->possibly_assigned_var_ids[$array_var_id] = true;
            }

            if ($statements_analyzer->data_flow_graph) {
                self::taintArrayAssignment(
                    $statements_analyzer,
                    $child_stmt,
                    $statements_analyzer->node_data->getType($child_stmt->var) ?: Type::getMixed(),
                    $new_child_type,
                    $parent_array_var_id,
                    $key_values
                );
            }
        }

        $root_is_string = $root_type->isString();
        $key_values = [];

        if ($current_dim instanceof PhpParser\Node\Scalar\String_) {
            $key_values[] = new Type\Atomic\TLiteralString($current_dim->value);
        } elseif ($current_dim instanceof PhpParser\Node\Scalar\LNumber && !$root_is_string) {
            $key_values[] = new Type\Atomic\TLiteralInt($current_dim->value);
        } elseif ($current_dim
            && ($current_dim_type = $statements_analyzer->node_data->getType($current_dim))
            && !$root_is_string
        ) {
            $string_literals = $current_dim_type->getLiteralStrings();
            $int_literals = $current_dim_type->getLiteralInts();

            $all_atomic_types = $current_dim_type->getAtomicTypes();

            if (count($string_literals) + count($int_literals) === count($all_atomic_types)) {
                foreach ($string_literals as $string_literal) {
                    $key_values[] = clone $string_literal;
                }

                foreach ($int_literals as $int_literal) {
                    $key_values[] = clone $int_literal;
                }
            }
        }

        if ($key_values) {
            $new_child_type = self::updateTypeWithKeyValues(
                $codebase,
                $root_type,
                $current_type,
                $key_values
            );
        } elseif (!$root_is_string) {
            if ($current_dim) {
                if ($current_dim_type = $statements_analyzer->node_data->getType($current_dim)) {
                    if ($current_dim_type->hasMixed()) {
                        $current_dim_type = Type::getArrayKey();
                    }

                    $array_atomic_key_type = ArrayFetchAnalyzer::replaceOffsetTypeWithInts(
                        $current_dim_type
                    );
                } else {
                    $array_atomic_key_type = Type::getArrayKey();
                }

                if ($offset_already_existed
                    && $child_stmt
                    && $parent_var_id
                    && ($parent_type = $context->vars_in_scope[$parent_var_id] ?? null)
                ) {
                    if ($parent_type->hasList()) {
                        $array_atomic_type = new TNonEmptyList(
                            $current_type
                        );
                    } elseif ($parent_type->hasClassStringMap()
                        && $current_dim_type
                        && $current_dim_type->isTemplatedClassString()
                    ) {
                        /**
                         * @var Type\Atomic\TClassStringMap
                         * @psalm-suppress PossiblyUndefinedStringArrayOffset
                         */
                        $class_string_map = $parent_type->getAtomicTypes()['array'];
                        /**
                         * @var Type\Atomic\TTemplateParamClass
                         */
                        $offset_type_part = \array_values($current_dim_type->getAtomicTypes())[0];

                        $template_result = new \Psalm\Internal\Type\TemplateResult(
                            [],
                            [
                                $offset_type_part->param_name => [
                                    $offset_type_part->defining_class => [
                                        new Type\Union([
                                            new Type\Atomic\TTemplateParam(
                                                $class_string_map->param_name,
                                                $offset_type_part->as_type
                                                    ? new Type\Union([$offset_type_part->as_type])
                                                    : Type::getObject(),
                                                'class-string-map'
                                            )
                                        ])
                                    ]
                                ]
                            ]
                        );

                        $current_type->replaceTemplateTypesWithArgTypes(
                            $template_result,
                            $codebase
                        );

                        $array_atomic_type = new Type\Atomic\TClassStringMap(
                            $class_string_map->param_name,
                            $class_string_map->as_type,
                            $current_type
                        );
                    } else {
                        $array_atomic_type = new TNonEmptyArray([
                            $array_atomic_key_type,
                            $current_type,
                        ]);
                    }
                } else {
                    $array_atomic_type = new TNonEmptyArray([
                        $array_atomic_key_type,
                        $current_type,
                    ]);
                }
            } else {
                $array_atomic_type = new TNonEmptyList($current_type);
            }

            $from_countable_object_like = false;

            $new_child_type = null;

            if (!$current_dim && !$context->inside_loop) {
                $atomic_root_types = $root_type->getAtomicTypes();

                if (isset($atomic_root_types['array'])) {
                    if ($array_atomic_type instanceof Type\Atomic\TClassStringMap) {
                        $array_atomic_type = new TNonEmptyArray([
                            $array_atomic_type->getStandinKeyParam(),
                            $array_atomic_type->value_param
                        ]);
                    } elseif ($atomic_root_types['array'] instanceof TNonEmptyArray
                        || $atomic_root_types['array'] instanceof TNonEmptyList
                    ) {
                        $array_atomic_type->count = $atomic_root_types['array']->count;
                    } elseif ($atomic_root_types['array'] instanceof TKeyedArray
                        && $atomic_root_types['array']->sealed
                    ) {
                        $array_atomic_type->count = count($atomic_root_types['array']->properties);
                        $from_countable_object_like = true;

                        if ($atomic_root_types['array']->is_list
                            && $array_atomic_type instanceof TList
                        ) {
                            $array_atomic_type = clone $atomic_root_types['array'];

                            $new_child_type = new Type\Union([$array_atomic_type]);

                            $new_child_type->parent_nodes = $root_type->parent_nodes;
                        }
                    } elseif ($array_atomic_type instanceof TList) {
                        $array_atomic_type = new TNonEmptyList(
                            $array_atomic_type->type_param
                        );
                    } else {
                        $array_atomic_type = new TNonEmptyArray(
                            $array_atomic_type->type_params
                        );
                    }
                }
            }

            $array_assignment_type = new Type\Union([
                $array_atomic_type,
            ]);

            if (!$new_child_type) {
                $new_child_type = Type::combineUnionTypes(
                    $root_type,
                    $array_assignment_type,
                    $codebase,
                    true,
                    true
                );
            }

            if ($from_countable_object_like) {
                $atomic_root_types = $new_child_type->getAtomicTypes();

                if (isset($atomic_root_types['array'])
                    && ($atomic_root_types['array'] instanceof TNonEmptyArray
                        || $atomic_root_types['array'] instanceof TNonEmptyList)
                    && $atomic_root_types['array']->count !== null
                ) {
                    $atomic_root_types['array']->count++;
                }
            }
        } else {
            $new_child_type = $root_type;
        }

        $new_child_type->removeType('null');

        if (!$root_type->hasObjectType()) {
            $root_type = $new_child_type;
        }

        $statements_analyzer->node_data->setType($root_array_expr, $root_type);

        if ($root_array_expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            if ($root_array_expr->name instanceof PhpParser\Node\Identifier) {
                InstancePropertyAssignmentAnalyzer::analyze(
                    $statements_analyzer,
                    $root_array_expr,
                    $root_array_expr->name->name,
                    null,
                    $root_type,
                    $context,
                    false
                );
            } else {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $root_array_expr->name, $context) === false) {
                    return false;
                }

                if (ExpressionAnalyzer::analyze($statements_analyzer, $root_array_expr->var, $context) === false) {
                    return false;
                }
            }
        } elseif ($root_array_expr instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && $root_array_expr->name instanceof PhpParser\Node\Identifier
        ) {
            StaticPropertyAssignmentAnalyzer::analyze(
                $statements_analyzer,
                $root_array_expr,
                null,
                $root_type,
                $context
            );
        } elseif ($root_var_id) {
            $context->vars_in_scope[$root_var_id] = $root_type;
        }

        if ($root_array_expr instanceof PhpParser\Node\Expr\MethodCall
            || $root_array_expr instanceof PhpParser\Node\Expr\StaticCall
            || $root_array_expr instanceof PhpParser\Node\Expr\FuncCall
        ) {
            if ($root_type->hasArray()) {
                if (IssueBuffer::accepts(
                    new InvalidArrayAssignment(
                        'Assigning to the output of a function has no effect',
                        new \Psalm\CodeLocation($statements_analyzer->getSource(), $root_array_expr)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )
                ) {
                    // do nothing
                }
            }
        }

        return null;
    }

    /**
     * @param non-empty-list<Type\Atomic\TLiteralInt|Type\Atomic\TLiteralString> $key_values
     */
    private static function updateTypeWithKeyValues(
        \Psalm\Codebase $codebase,
        Type\Union $child_stmt_type,
        Type\Union $current_type,
        array $key_values
    ) : Type\Union {
        $has_matching_objectlike_property = false;
        $has_matching_string = false;

        foreach ($child_stmt_type->getAtomicTypes() as $type) {
            foreach ($key_values as $key_value) {
                if ($type instanceof TKeyedArray) {
                    if (isset($type->properties[$key_value->value])) {
                        $has_matching_objectlike_property = true;

                        $type->properties[$key_value->value] = clone $current_type;
                    }
                } elseif ($type instanceof Type\Atomic\TString
                    && $key_value instanceof Type\Atomic\TLiteralInt
                ) {
                    $has_matching_string = true;

                    if ($type instanceof Type\Atomic\TLiteralString
                        && $current_type->isSingleStringLiteral()
                    ) {
                        $new_char = $current_type->getSingleStringLiteral()->value;

                        if (\strlen($new_char) === 1) {
                            $type->value[0] = $new_char;
                        }
                    }
                } elseif ($type instanceof TNonEmptyList
                    && $key_value instanceof Type\Atomic\TLiteralInt
                    && count($key_values) === 1
                ) {
                    $has_matching_objectlike_property = true;

                    $type->type_param = Type::combineUnionTypes(
                        clone $current_type,
                        $type->type_param,
                        $codebase,
                        true,
                        false
                    );
                }
            }
        }

        $child_stmt_type->bustCache();

        if (!$has_matching_objectlike_property && !$has_matching_string) {
            if (count($key_values) === 1) {
                $key_value = $key_values[0];

                $object_like = new TKeyedArray(
                    [$key_value->value => clone $current_type],
                    $key_value instanceof Type\Atomic\TLiteralClassString
                        ? [(string) $key_value->value => true]
                        : null
                );

                $object_like->sealed = true;

                $array_assignment_type = new Type\Union([
                    $object_like,
                ]);
            } else {
                $array_assignment_literals = $key_values;

                $array_assignment_type = new Type\Union([
                    new Type\Atomic\TNonEmptyArray([
                        new Type\Union($array_assignment_literals),
                        clone $current_type
                    ])
                ]);
            }

            return Type::combineUnionTypes(
                $child_stmt_type,
                $array_assignment_type,
                $codebase,
                true,
                false
            );
        }

        return $child_stmt_type;
    }

    /**
     * @param list<Type\Atomic\TLiteralInt|Type\Atomic\TLiteralString> $key_values $key_values
     */
    private static function taintArrayAssignment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $expr,
        Type\Union $stmt_type,
        Type\Union $child_stmt_type,
        ?string $var_var_id,
        array $key_values
    ) : void {
        if ($statements_analyzer->data_flow_graph
            && ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
                || !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues()))
        ) {
            if (!$stmt_type->parent_nodes) {
                $var_location = new \Psalm\CodeLocation($statements_analyzer->getSource(), $expr->var);

                $parent_node = \Psalm\Internal\DataFlow\DataFlowNode::getForAssignment(
                    $var_var_id ?: 'assignment',
                    $var_location
                );

                $statements_analyzer->data_flow_graph->addNode($parent_node);

                $stmt_type->parent_nodes = [$parent_node->id => $parent_node];
            }

            foreach ($stmt_type->parent_nodes as $parent_node) {
                foreach ($child_stmt_type->parent_nodes as $child_parent_node) {
                    if ($key_values) {
                        foreach ($key_values as $key_value) {
                            $statements_analyzer->data_flow_graph->addPath(
                                $child_parent_node,
                                $parent_node,
                                'array-assignment-\'' . $key_value->value . '\''
                            );
                        }
                    } else {
                        $statements_analyzer->data_flow_graph->addPath(
                            $child_parent_node,
                            $parent_node,
                            'array-assignment'
                        );
                    }
                }
            }
        }
    }
}
