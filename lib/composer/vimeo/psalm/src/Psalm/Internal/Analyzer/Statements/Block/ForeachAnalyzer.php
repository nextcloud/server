<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidIterator;
use Psalm\Issue\NullIterator;
use Psalm\Issue\PossiblyFalseIterator;
use Psalm\Issue\PossiblyInvalidIterator;
use Psalm\Issue\PossiblyNullIterator;
use Psalm\Issue\PossibleRawObjectIteration;
use Psalm\Issue\RawObjectIteration;
use Psalm\Issue\UnnecessaryVarAnnotation;
use Psalm\IssueBuffer;
use Psalm\Internal\Scope\LoopScope;
use Psalm\Type;
use function is_string;
use function in_array;
use function array_merge;
use function array_intersect_key;
use function array_values;
use function strtolower;
use function array_map;
use function array_search;
use function array_keys;

/**
 * @internal
 */
class ForeachAnalyzer
{
    /**
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Foreach_ $stmt,
        Context $context
    ): ?bool {
        $var_comments = [];

        $doc_comment = $stmt->getDocComment();

        $codebase = $statements_analyzer->getCodebase();
        $file_path = $statements_analyzer->getRootFilePath();
        $type_aliases = $codebase->file_storage_provider->get($file_path)->type_aliases;

        if ($doc_comment) {
            try {
                $var_comments = CommentAnalyzer::getTypeFromComment(
                    $doc_comment,
                    $statements_analyzer->getSource(),
                    $statements_analyzer->getSource()->getAliases(),
                    $statements_analyzer->getTemplateTypeMap() ?: [],
                    $type_aliases
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_analyzer, $stmt)
                    )
                )) {
                    // fall through
                }
            }
        }

        $safe_var_ids = [];

        if ($stmt->keyVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->keyVar->name)) {
            $safe_var_ids['$' . $stmt->keyVar->name] = true;
        }

        if ($stmt->valueVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->valueVar->name)) {
            $safe_var_ids['$' . $stmt->valueVar->name] = true;
        } elseif ($stmt->valueVar instanceof PhpParser\Node\Expr\List_) {
            foreach ($stmt->valueVar->items as $list_item) {
                if (!$list_item) {
                    continue;
                }

                $list_item_key = $list_item->key;
                $list_item_value = $list_item->value;

                if ($list_item_value instanceof PhpParser\Node\Expr\Variable && is_string($list_item_value->name)) {
                    $safe_var_ids['$' . $list_item_value->name] = true;
                }

                if ($list_item_key instanceof PhpParser\Node\Expr\Variable && is_string($list_item_key->name)) {
                    $safe_var_ids['$' . $list_item_key->name] = true;
                }
            }
        }

        foreach ($var_comments as $var_comment) {
            if (!$var_comment->var_id || !$var_comment->type) {
                continue;
            }

            if (isset($safe_var_ids[$var_comment->var_id])) {
                continue;
            }

            $comment_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $var_comment->type,
                $context->self,
                $context->self,
                $statements_analyzer->getParentFQCLN()
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
                        $comment_type,
                        $type_location,
                        $context->calling_method_id
                    );
                }
            }

            if (isset($context->vars_in_scope[$var_comment->var_id])
                || VariableFetchAnalyzer::isSuperGlobal($var_comment->var_id)
            ) {
                if ($codebase->find_unused_variables
                    && $doc_comment
                    && $type_location
                    && isset($context->vars_in_scope[$var_comment->var_id])
                    && $context->vars_in_scope[$var_comment->var_id]->getId() === $comment_type->getId()
                    && !$comment_type->isMixed()
                ) {
                    $project_analyzer = $statements_analyzer->getProjectAnalyzer();

                    if ($codebase->alter_code
                        && isset($project_analyzer->getIssuesToFix()['UnnecessaryVarAnnotation'])
                    ) {
                        FileManipulationBuffer::addVarAnnotationToRemove($type_location);
                    } elseif (IssueBuffer::accepts(
                        new UnnecessaryVarAnnotation(
                            'The @var ' . $comment_type . ' annotation for '
                                . $var_comment->var_id . ' is unnecessary',
                            $type_location
                        ),
                        [],
                        true
                    )) {
                        // fall through
                    }
                }

                if (isset($context->vars_in_scope[$var_comment->var_id])) {
                    $comment_type->parent_nodes = $context->vars_in_scope[$var_comment->var_id]->parent_nodes;
                }

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        $was_inside_use = $context->inside_use;
        $context->inside_use = true;
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }
        $context->inside_use = $was_inside_use;

        $key_type = null;
        $value_type = null;
        $always_non_empty_array = true;

        $var_id = ExpressionIdentifier::getVarId(
            $stmt->expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            $iterator_type = $stmt_expr_type;
        } elseif ($var_id && $context->hasVariable($var_id)) {
            $iterator_type = $context->vars_in_scope[$var_id];
        } else {
            $iterator_type = null;
        }

        if ($iterator_type) {
            if (self::checkIteratorType(
                $statements_analyzer,
                $stmt,
                $iterator_type,
                $codebase,
                $context,
                $key_type,
                $value_type,
                $always_non_empty_array
            ) === false
            ) {
                return false;
            }
        }

        $foreach_context = clone $context;

        foreach ($foreach_context->vars_in_scope as $context_var_id => $context_type) {
            $foreach_context->vars_in_scope[$context_var_id] = clone $context_type;
        }

        $foreach_context->inside_loop = true;
        $foreach_context->break_types[] = 'loop';

        if ($codebase->alter_code) {
            $foreach_context->branch_point =
                $foreach_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
        }

        if ($stmt->keyVar && $stmt->keyVar instanceof PhpParser\Node\Expr\Variable && is_string($stmt->keyVar->name)) {
            $key_type = $key_type ?: Type::getMixed();

            AssignmentAnalyzer::analyze(
                $statements_analyzer,
                $stmt->keyVar,
                $stmt->expr,
                $key_type,
                $foreach_context,
                $doc_comment,
                ['$' . $stmt->keyVar->name => true]
            );
        }

        $value_type = $value_type ?: Type::getMixed();

        if ($stmt->byRef) {
            $value_type->by_ref = true;
        }

        AssignmentAnalyzer::analyze(
            $statements_analyzer,
            $stmt->valueVar,
            $stmt->expr,
            $value_type,
            $foreach_context,
            $doc_comment,
            $stmt->valueVar instanceof PhpParser\Node\Expr\Variable
                && is_string($stmt->valueVar->name)
                ? ['$' . $stmt->valueVar->name => true]
                : []
        );

        foreach ($var_comments as $var_comment) {
            if (!$var_comment->var_id || !$var_comment->type) {
                continue;
            }

            $comment_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $var_comment->type,
                $context->self,
                $context->self,
                $statements_analyzer->getParentFQCLN()
            );

            if (isset($foreach_context->vars_in_scope[$var_comment->var_id])) {
                $existing_var_type = $foreach_context->vars_in_scope[$var_comment->var_id];
                $comment_type->parent_nodes = $existing_var_type->parent_nodes;
                $comment_type->by_ref = $existing_var_type->by_ref;
            }

            $foreach_context->vars_in_scope[$var_comment->var_id] = $comment_type;
        }

        $loop_scope = new LoopScope($foreach_context, $context);

        $loop_scope->protected_var_ids = $context->protected_var_ids;

        LoopAnalyzer::analyze(
            $statements_analyzer,
            $stmt->stmts,
            [],
            [],
            $loop_scope,
            $inner_loop_context,
            false,
            $always_non_empty_array
        );

        if (!$inner_loop_context) {
            throw new \UnexpectedValueException('There should be an inner loop context');
        }

        $foreach_context->loop_scope = null;

        $context->vars_possibly_in_scope = array_merge(
            $foreach_context->vars_possibly_in_scope,
            $context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_intersect_key(
            $foreach_context->referenced_var_ids,
            $context->referenced_var_ids
        );

        if ($context->collect_exceptions) {
            $context->mergeExceptions($foreach_context);
        }

        return null;
    }

    /**
     * @return false|null
     */
    public static function checkIteratorType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Foreach_ $stmt,
        Type\Union $iterator_type,
        Codebase $codebase,
        Context $context,
        ?Type\Union &$key_type,
        ?Type\Union &$value_type,
        bool &$always_non_empty_array
    ): ?bool {
        if ($iterator_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullIterator(
                    'Cannot iterate over null',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif ($iterator_type->isNullable() && !$iterator_type->ignore_nullable_issues) {
            if (IssueBuffer::accepts(
                new PossiblyNullIterator(
                    'Cannot iterate over nullable var ' . $iterator_type,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif ($iterator_type->isFalsable() && !$iterator_type->ignore_falsable_issues) {
            if (IssueBuffer::accepts(
                new PossiblyFalseIterator(
                    'Cannot iterate over falsable var ' . $iterator_type,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $has_valid_iterator = false;
        $invalid_iterator_types = [];
        $raw_object_types = [];

        foreach ($iterator_type->getAtomicTypes() as $iterator_atomic_type) {
            if ($iterator_atomic_type instanceof Type\Atomic\TTemplateParam) {
                $iterator_atomic_type = array_values($iterator_atomic_type->as->getAtomicTypes())[0];
            }

            // if it's an empty array, we cannot iterate over it
            if ($iterator_atomic_type instanceof Type\Atomic\TArray
                && $iterator_atomic_type->type_params[1]->isEmpty()
            ) {
                $always_non_empty_array = false;
                $has_valid_iterator = true;
                continue;
            }

            if ($iterator_atomic_type instanceof Type\Atomic\TNull
                || $iterator_atomic_type instanceof Type\Atomic\TFalse
            ) {
                $always_non_empty_array = false;
                continue;
            }

            if ($iterator_atomic_type instanceof Type\Atomic\TArray
                || $iterator_atomic_type instanceof Type\Atomic\TKeyedArray
                || $iterator_atomic_type instanceof Type\Atomic\TList
            ) {
                if ($iterator_atomic_type instanceof Type\Atomic\TKeyedArray) {
                    if (!$iterator_atomic_type->sealed) {
                        $always_non_empty_array = false;
                    }
                    $iterator_atomic_type = $iterator_atomic_type->getGenericArrayType();
                } elseif ($iterator_atomic_type instanceof Type\Atomic\TList) {
                    if (!$iterator_atomic_type instanceof Type\Atomic\TNonEmptyList) {
                        $always_non_empty_array = false;
                    }

                    $iterator_atomic_type = new Type\Atomic\TArray([
                        Type::getInt(),
                        $iterator_atomic_type->type_param
                    ]);
                } elseif (!$iterator_atomic_type instanceof Type\Atomic\TNonEmptyArray) {
                    $always_non_empty_array = false;
                }

                if (!$value_type) {
                    $value_type = clone $iterator_atomic_type->type_params[1];
                } else {
                    $value_type = Type::combineUnionTypes($value_type, $iterator_atomic_type->type_params[1]);
                }

                ArrayFetchAnalyzer::taintArrayFetch(
                    $statements_analyzer,
                    $stmt->expr,
                    null,
                    $value_type,
                    Type::getMixed()
                );

                $key_type_part = $iterator_atomic_type->type_params[0];

                if (!$key_type) {
                    $key_type = $key_type_part;
                } else {
                    $key_type = Type::combineUnionTypes($key_type, $key_type_part);
                }

                ArrayFetchAnalyzer::taintArrayFetch(
                    $statements_analyzer,
                    $stmt->expr,
                    null,
                    $key_type,
                    Type::getMixed()
                );

                $has_valid_iterator = true;
                continue;
            }

            $always_non_empty_array = false;

            if ($iterator_atomic_type instanceof Type\Atomic\Scalar ||
                $iterator_atomic_type instanceof Type\Atomic\TVoid
            ) {
                $invalid_iterator_types[] = $iterator_atomic_type->getKey();

                $value_type = Type::getMixed();
            } elseif ($iterator_atomic_type instanceof Type\Atomic\TObject ||
                $iterator_atomic_type instanceof Type\Atomic\TMixed ||
                $iterator_atomic_type instanceof Type\Atomic\TEmpty
            ) {
                $has_valid_iterator = true;
                $value_type = Type::getMixed();

                ArrayFetchAnalyzer::taintArrayFetch(
                    $statements_analyzer,
                    $stmt->expr,
                    null,
                    $value_type,
                    Type::getMixed()
                );

                if (!$context->pure) {
                    if ($statements_analyzer->getSource()
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                        && $statements_analyzer->getSource()->track_mutations
                    ) {
                        $statements_analyzer->getSource()->inferred_has_mutation = true;
                        $statements_analyzer->getSource()->inferred_impure = true;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new ImpureMethodCall(
                            'Cannot call a possibly-mutating iterator from a pure context',
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } elseif ($iterator_atomic_type instanceof Type\Atomic\TIterable) {
                if ($iterator_atomic_type->extra_types) {
                    $iterator_atomic_type_copy = clone $iterator_atomic_type;
                    $iterator_atomic_type_copy->extra_types = [];
                    $iterator_atomic_types = [$iterator_atomic_type_copy];
                    $iterator_atomic_types = array_merge(
                        $iterator_atomic_types,
                        $iterator_atomic_type->extra_types
                    );
                } else {
                    $iterator_atomic_types = [$iterator_atomic_type];
                }

                $intersection_value_type = null;
                $intersection_key_type = null;

                foreach ($iterator_atomic_types as $iat) {
                    if (!$iat instanceof Type\Atomic\TIterable) {
                        continue;
                    }

                    [$key_type_part, $value_type_part] = $iat->type_params;

                    if (!$intersection_value_type) {
                        $intersection_value_type = $value_type_part;
                    } else {
                        $intersection_value_type = Type::intersectUnionTypes(
                            $intersection_value_type,
                            $value_type_part,
                            $codebase
                        ) ?: Type::getMixed();
                    }

                    if (!$intersection_key_type) {
                        $intersection_key_type = $key_type_part;
                    } else {
                        $intersection_key_type = Type::intersectUnionTypes(
                            $intersection_key_type,
                            $key_type_part,
                            $codebase
                        ) ?: Type::getMixed();
                    }
                }

                if (!$intersection_value_type || !$intersection_key_type) {
                    throw new \UnexpectedValueException('Should not happen');
                }

                if (!$value_type) {
                    $value_type = $intersection_value_type;
                } else {
                    $value_type = Type::combineUnionTypes($value_type, $intersection_value_type);
                }

                if (!$key_type) {
                    $key_type = $intersection_key_type;
                } else {
                    $key_type = Type::combineUnionTypes($key_type, $intersection_key_type);
                }

                $has_valid_iterator = true;

                if (!$context->pure) {
                    if ($statements_analyzer->getSource()
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                        && $statements_analyzer->getSource()->track_mutations
                    ) {
                        $statements_analyzer->getSource()->inferred_has_mutation = true;
                        $statements_analyzer->getSource()->inferred_impure = true;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new ImpureMethodCall(
                            'Cannot call a possibly-mutating Traversable::getIterator from a pure context',
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } elseif ($iterator_atomic_type instanceof Type\Atomic\TNamedObject) {
                if ($iterator_atomic_type->value !== 'Traversable' &&
                    $iterator_atomic_type->value !== $statements_analyzer->getClassName()
                ) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $iterator_atomic_type->value,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                        $context->self,
                        $context->calling_method_id,
                        $statements_analyzer->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }

                if (AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $iterator_atomic_type,
                    new Type\Atomic\TIterable([Type::getMixed(), Type::getMixed()])
                )) {
                    self::handleIterable(
                        $statements_analyzer,
                        $iterator_atomic_type,
                        $stmt->expr,
                        $codebase,
                        $context,
                        $key_type,
                        $value_type,
                        $has_valid_iterator
                    );
                } else {
                    $raw_object_types[] = $iterator_atomic_type->value;
                }

                if (!$context->pure) {
                    if ($statements_analyzer->getSource()
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                        && $statements_analyzer->getSource()->track_mutations
                    ) {
                        $statements_analyzer->getSource()->inferred_has_mutation = true;
                        $statements_analyzer->getSource()->inferred_impure = true;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new ImpureMethodCall(
                            'Cannot call a possibly-mutating iterator from a pure context',
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($raw_object_types) {
            if ($has_valid_iterator) {
                if (IssueBuffer::accepts(
                    new PossibleRawObjectIteration(
                        'Possibly undesired iteration over regular object ' . \reset($raw_object_types),
                        new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new RawObjectIteration(
                        'Possibly undesired iteration over regular object ' . \reset($raw_object_types),
                        new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        if ($invalid_iterator_types) {
            if ($has_valid_iterator) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidIterator(
                        'Cannot iterate over ' . $invalid_iterator_types[0],
                        new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidIterator(
                        'Cannot iterate over ' . $invalid_iterator_types[0],
                        new CodeLocation($statements_analyzer->getSource(), $stmt->expr)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        return null;
    }

    public static function handleIterable(
        StatementsAnalyzer $statements_analyzer,
        Type\Atomic\TNamedObject $iterator_atomic_type,
        PhpParser\Node\Expr $foreach_expr,
        Codebase $codebase,
        Context $context,
        ?Type\Union &$key_type,
        ?Type\Union &$value_type,
        bool &$has_valid_iterator
    ): void {
        if ($iterator_atomic_type->extra_types) {
            $iterator_atomic_type_copy = clone $iterator_atomic_type;
            $iterator_atomic_type_copy->extra_types = [];
            $iterator_atomic_types = [$iterator_atomic_type_copy];
            $iterator_atomic_types = array_merge($iterator_atomic_types, $iterator_atomic_type->extra_types);
        } else {
            $iterator_atomic_types = [$iterator_atomic_type];
        }

        foreach ($iterator_atomic_types as $iterator_atomic_type) {
            if ($iterator_atomic_type instanceof Type\Atomic\TTemplateParam
                || $iterator_atomic_type instanceof Type\Atomic\TObjectWithProperties
            ) {
                throw new \UnexpectedValueException('Shouldn’t get a generic param here');
            }


            $has_valid_iterator = true;

            if ($iterator_atomic_type instanceof Type\Atomic\TNamedObject
                && strtolower($iterator_atomic_type->value) === 'simplexmlelement'
            ) {
                if ($value_type) {
                    $value_type = Type::combineUnionTypes(
                        $value_type,
                        new Type\Union([clone $iterator_atomic_type])
                    );
                } else {
                    $value_type = new Type\Union([clone $iterator_atomic_type]);
                }

                if ($key_type) {
                    $key_type = Type::combineUnionTypes(
                        $key_type,
                        Type::getString()
                    );
                } else {
                    $key_type = Type::getString();
                }
            }

            if ($iterator_atomic_type instanceof Type\Atomic\TIterable
                || (strtolower($iterator_atomic_type->value) === 'traversable'
                    || $codebase->classImplements(
                        $iterator_atomic_type->value,
                        'Traversable'
                    ) ||
                    (
                        $codebase->interfaceExists($iterator_atomic_type->value)
                        && $codebase->interfaceExtends(
                            $iterator_atomic_type->value,
                            'Traversable'
                        )
                    ))
            ) {
                if (strtolower($iterator_atomic_type->value) === 'iteratoraggregate'
                    || $codebase->classImplements(
                        $iterator_atomic_type->value,
                        'IteratorAggregate'
                    )
                    || ($codebase->interfaceExists($iterator_atomic_type->value)
                        && $codebase->interfaceExtends(
                            $iterator_atomic_type->value,
                            'IteratorAggregate'
                        )
                    )
                ) {
                    $old_data_provider = $statements_analyzer->node_data;

                    $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                    $fake_method_call = new PhpParser\Node\Expr\MethodCall(
                        $foreach_expr,
                        new PhpParser\Node\Identifier('getIterator', $foreach_expr->getAttributes())
                    );

                    $suppressed_issues = $statements_analyzer->getSuppressedIssues();

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    if (!in_array('PossiblyUndefinedMethod', $suppressed_issues, true)) {
                        $statements_analyzer->addSuppressedIssues(['PossiblyUndefinedMethod']);
                    }

                    \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
                        $statements_analyzer,
                        $fake_method_call,
                        $context
                    );

                    if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                        $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
                    }

                    if (!in_array('PossiblyUndefinedMethod', $suppressed_issues, true)) {
                        $statements_analyzer->removeSuppressedIssues(['PossiblyUndefinedMethod']);
                    }

                    $iterator_class_type = $statements_analyzer->node_data->getType($fake_method_call) ?: null;

                    $statements_analyzer->node_data = $old_data_provider;

                    if ($iterator_class_type) {
                        foreach ($iterator_class_type->getAtomicTypes() as $array_atomic_type) {
                            $key_type_part = null;
                            $value_type_part = null;

                            if ($array_atomic_type instanceof Type\Atomic\TArray
                                || $array_atomic_type instanceof Type\Atomic\TKeyedArray
                            ) {
                                if ($array_atomic_type instanceof Type\Atomic\TKeyedArray) {
                                    $array_atomic_type = $array_atomic_type->getGenericArrayType();
                                }

                                [$key_type_part, $value_type_part] = $array_atomic_type->type_params;
                            } else {
                                if ($array_atomic_type instanceof Type\Atomic\TNamedObject
                                    && $codebase->classExists($array_atomic_type->value)
                                    && $codebase->classImplements(
                                        $array_atomic_type->value,
                                        'Traversable'
                                    )
                                ) {
                                    $generic_storage = $codebase->classlike_storage_provider->get(
                                        $array_atomic_type->value
                                    );

                                    // The collection might be an iterator, in which case
                                    // we want to call the iterator function
                                    /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
                                    if (!isset($generic_storage->template_type_extends['Traversable'])
                                        || ($generic_storage
                                                ->template_type_extends['Traversable']['TKey']->isMixed()
                                            && $generic_storage
                                                ->template_type_extends['Traversable']['TValue']->isMixed())
                                    ) {
                                        self::handleIterable(
                                            $statements_analyzer,
                                            $array_atomic_type,
                                            $fake_method_call,
                                            $codebase,
                                            $context,
                                            $key_type,
                                            $value_type,
                                            $has_valid_iterator
                                        );

                                        continue;
                                    }
                                }

                                if ($array_atomic_type instanceof Type\Atomic\TIterable
                                    || ($array_atomic_type instanceof Type\Atomic\TNamedObject
                                        && ($array_atomic_type->value === 'Traversable'
                                            || ($codebase->classOrInterfaceExists($array_atomic_type->value)
                                                && $codebase->classImplements(
                                                    $array_atomic_type->value,
                                                    'Traversable'
                                                ))))
                                ) {
                                    self::getKeyValueParamsForTraversableObject(
                                        $array_atomic_type,
                                        $codebase,
                                        $key_type_part,
                                        $value_type_part
                                    );
                                }
                            }

                            if (!$key_type_part || !$value_type_part) {
                                break;
                            }

                            if (!$key_type) {
                                $key_type = $key_type_part;
                            } else {
                                $key_type = Type::combineUnionTypes($key_type, $key_type_part);
                            }

                            if (!$value_type) {
                                $value_type = $value_type_part;
                            } else {
                                $value_type = Type::combineUnionTypes($value_type, $value_type_part);
                            }
                        }
                    }
                } elseif ($codebase->classImplements(
                    $iterator_atomic_type->value,
                    'Iterator'
                ) ||
                    (
                        $codebase->interfaceExists($iterator_atomic_type->value)
                        && $codebase->interfaceExtends(
                            $iterator_atomic_type->value,
                            'Iterator'
                        )
                    )
                ) {
                    $iterator_value_type = self::getFakeMethodCallType(
                        $statements_analyzer,
                        $foreach_expr,
                        $context,
                        'current'
                    );

                    $iterator_key_type = self::getFakeMethodCallType(
                        $statements_analyzer,
                        $foreach_expr,
                        $context,
                        'key'
                    );

                    if ($iterator_value_type && !$iterator_value_type->isMixed()) {
                        if (!$value_type) {
                            $value_type = $iterator_value_type;
                        } else {
                            $value_type = Type::combineUnionTypes($value_type, $iterator_value_type);
                        }
                    }

                    if ($iterator_key_type && !$iterator_key_type->isMixed()) {
                        if (!$key_type) {
                            $key_type = $iterator_key_type;
                        } else {
                            $key_type = Type::combineUnionTypes($key_type, $iterator_key_type);
                        }
                    }
                }

                if (!$key_type && !$value_type) {
                    self::getKeyValueParamsForTraversableObject(
                        $iterator_atomic_type,
                        $codebase,
                        $key_type,
                        $value_type
                    );
                }

                return;
            }

            if (!$codebase->classlikes->classOrInterfaceExists($iterator_atomic_type->value)) {
                return;
            }
        }
    }

    public static function getKeyValueParamsForTraversableObject(
        Type\Atomic $iterator_atomic_type,
        Codebase $codebase,
        ?Type\Union &$key_type,
        ?Type\Union &$value_type
    ): void {
        if ($iterator_atomic_type instanceof Type\Atomic\TIterable
            || ($iterator_atomic_type instanceof Type\Atomic\TGenericObject
                && strtolower($iterator_atomic_type->value) === 'traversable')
        ) {
            $value_type_part = $iterator_atomic_type->type_params[1];

            if (!$value_type) {
                $value_type = $value_type_part;
            } else {
                $value_type = Type::combineUnionTypes($value_type, $value_type_part);
            }

            $key_type_part = $iterator_atomic_type->type_params[0];

            if (!$key_type) {
                $key_type = $key_type_part;
            } else {
                $key_type = Type::combineUnionTypes($key_type, $key_type_part);
            }
            return;
        }

        if ($iterator_atomic_type instanceof Type\Atomic\TNamedObject
            && (
                $codebase->classImplements(
                    $iterator_atomic_type->value,
                    'Traversable'
                )
                || $codebase->interfaceExtends(
                    $iterator_atomic_type->value,
                    'Traversable'
                )
            )
        ) {
            $generic_storage = $codebase->classlike_storage_provider->get(
                $iterator_atomic_type->value
            );

            if (!isset($generic_storage->template_type_extends['Traversable'])) {
                return;
            }

            if ($generic_storage->template_types
                || $iterator_atomic_type instanceof Type\Atomic\TGenericObject
            ) {
                // if we're just being passed the non-generic class itself, assume
                // that it's inside the calling class
                $passed_type_params = $iterator_atomic_type instanceof Type\Atomic\TGenericObject
                    ? $iterator_atomic_type->type_params
                    : array_values(
                        array_map(
                            /** @param array<string, array{0:Type\Union}> $arr */
                            function (array $arr) use ($iterator_atomic_type) : Type\Union {
                                if (isset($arr[$iterator_atomic_type->value])) {
                                    return $arr[$iterator_atomic_type->value][0];
                                }

                                return Type::getMixed();
                            },
                            $generic_storage->template_types
                        )
                    );
            } else {
                $passed_type_params = null;
            }

            $key_type = self::getExtendedType(
                'TKey',
                'Traversable',
                $generic_storage->name,
                $generic_storage->template_type_extends,
                $generic_storage->template_types,
                $passed_type_params
            );

            $value_type = self::getExtendedType(
                'TValue',
                'Traversable',
                $generic_storage->name,
                $generic_storage->template_type_extends,
                $generic_storage->template_types,
                $passed_type_params
            );

            return;
        }
    }

    private static function getFakeMethodCallType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $foreach_expr,
        Context $context,
        string $method_name
    ) : ?Type\Union {
        $old_data_provider = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $fake_method_call = new PhpParser\Node\Expr\MethodCall(
            $foreach_expr,
            new PhpParser\Node\Identifier($method_name, $foreach_expr->getAttributes())
        );

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        if (!in_array('PossiblyUndefinedMethod', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['PossiblyUndefinedMethod']);
        }

        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
            $statements_analyzer,
            $fake_method_call,
            $context
        );

        $context->inside_call = $was_inside_call;

        if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
        }

        if (!in_array('PossiblyUndefinedMethod', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['PossiblyUndefinedMethod']);
        }

        $iterator_class_type = $statements_analyzer->node_data->getType($fake_method_call) ?: null;

        $statements_analyzer->node_data = $old_data_provider;

        return $iterator_class_type;
    }

    /**
     * @param  array<string, array<int|string, Type\Union>>  $template_type_extends
     * @param  array<string, array<string, array{Type\Union}>>  $class_template_types
     * @param  array<int, Type\Union> $calling_type_params
     */
    private static function getExtendedType(
        string $template_name,
        string $template_class,
        string $calling_class,
        array $template_type_extends,
        ?array $class_template_types = null,
        ?array $calling_type_params = null
    ): ?Type\Union {
        if ($calling_class === $template_class) {
            if (isset($class_template_types[$template_name]) && $calling_type_params) {
                $offset = array_search($template_name, array_keys($class_template_types));

                if ($offset !== false && isset($calling_type_params[$offset])) {
                    return $calling_type_params[$offset];
                }
            }

            return null;
        }

        if (isset($template_type_extends[$template_class][$template_name])) {
            $extended_type = $template_type_extends[$template_class][$template_name];

            $return_type = null;

            foreach ($extended_type->getAtomicTypes() as $extended_atomic_type) {
                if (!$extended_atomic_type instanceof Type\Atomic\TTemplateParam) {
                    if (!$return_type) {
                        $return_type = $extended_type;
                    } else {
                        $return_type = Type::combineUnionTypes(
                            $return_type,
                            $extended_type
                        );
                    }

                    continue;
                }

                $candidate_type = self::getExtendedType(
                    $extended_atomic_type->param_name,
                    $extended_atomic_type->defining_class,
                    $calling_class,
                    $template_type_extends,
                    $class_template_types,
                    $calling_type_params
                );

                if ($candidate_type) {
                    if (!$return_type) {
                        $return_type = $candidate_type;
                    } else {
                        $return_type = Type::combineUnionTypes(
                            $return_type,
                            $candidate_type
                        );
                    }
                }
            }

            if ($return_type) {
                return $return_type;
            }
        }

        return null;
    }
}
