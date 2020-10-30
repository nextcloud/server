<?php
namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Issue\FalsableReturnStatement;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidReturnStatement;
use Psalm\Issue\LessSpecificReturnStatement;
use Psalm\Issue\MixedReturnStatement;
use Psalm\Issue\MixedReturnTypeCoercion;
use Psalm\Issue\NoValue;
use Psalm\Issue\NullableReturnStatement;
use Psalm\IssueBuffer;
use Psalm\Type;
use function explode;
use function strtolower;

/**
 * @internal
 */
class ReturnAnalyzer
{
    /**
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Return_ $stmt,
        Context $context
    ): ?bool {
        $doc_comment = $stmt->getDocComment();

        $var_comments = [];
        $var_comment_type = null;

        $source = $statements_analyzer->getSource();

        $codebase = $statements_analyzer->getCodebase();

        if ($doc_comment && ($parsed_docblock = $statements_analyzer->getParsedDocblock())) {
            $file_storage_provider = $codebase->file_storage_provider;

            $file_storage = $file_storage_provider->get($statements_analyzer->getFilePath());

            try {
                $var_comments = CommentAnalyzer::arrayToDocblocks(
                    $doc_comment,
                    $parsed_docblock,
                    $statements_analyzer->getSource(),
                    $statements_analyzer->getAliases(),
                    $statements_analyzer->getTemplateTypeMap(),
                    $file_storage->type_aliases
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($source, $stmt)
                    )
                )) {
                    // fall through
                }
            }

            foreach ($var_comments as $var_comment) {
                if (!$var_comment->type) {
                    continue;
                }

                $comment_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $var_comment->type,
                    $context->self,
                    $context->self,
                    $statements_analyzer->getParentFQCLN()
                );

                if ($codebase->alter_code
                    && $var_comment->type_start
                    && $var_comment->type_end
                    && $var_comment->line_number
                ) {
                    $type_location = new CodeLocation\DocblockTypeLocation(
                        $statements_analyzer,
                        $var_comment->type_start,
                        $var_comment->type_end,
                        $var_comment->line_number
                    );

                    $codebase->classlikes->handleDocblockTypeInMigration(
                        $codebase,
                        $statements_analyzer,
                        $comment_type,
                        $type_location,
                        $context->calling_method_id
                    );
                }

                if (!$var_comment->var_id) {
                    $var_comment_type = $comment_type;
                    continue;
                }

                if (isset($context->vars_in_scope[$var_comment->var_id])) {
                    $comment_type->parent_nodes = $context->vars_in_scope[$var_comment->var_id]->parent_nodes;
                }

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if ($stmt->expr) {
            $context->inside_call = true;

            if ($stmt->expr instanceof PhpParser\Node\Expr\Closure
                || $stmt->expr instanceof PhpParser\Node\Expr\ArrowFunction
            ) {
                self::potentiallyInferTypesOnClosureFromParentReturnType(
                    $statements_analyzer,
                    $stmt->expr,
                    $context
                );
            }

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($var_comment_type) {
                $stmt_type = $var_comment_type;

                if ($stmt_expr_type && $stmt_expr_type->parent_nodes) {
                    $stmt_type->parent_nodes = $stmt_expr_type->parent_nodes;
                }

                $statements_analyzer->node_data->setType($stmt, $var_comment_type);
            } elseif ($stmt_expr_type) {
                $stmt_type = $stmt_expr_type;

                if ($stmt_type->isNever()) {
                    if (IssueBuffer::accepts(
                        new NoValue(
                            'This function or method call never returns output',
                            new CodeLocation($source, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    $stmt_type = Type::getEmpty();
                }

                if ($stmt_type->isVoid()) {
                    $stmt_type = Type::getNull();
                }
            } else {
                $stmt_type = Type::getMixed();
            }
        } else {
            $stmt_type = Type::getVoid();
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        if ($context->finally_scope) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if (isset($context->finally_scope->vars_in_scope[$var_id])) {
                    if ($context->finally_scope->vars_in_scope[$var_id] !== $type) {
                        $context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->finally_scope->vars_in_scope[$var_id],
                            $type,
                            $statements_analyzer->getCodebase()
                        );
                    }
                } else {
                    $context->finally_scope->vars_in_scope[$var_id] = $type;
                }
            }
        }

        if ($source instanceof FunctionLikeAnalyzer
            && !($source->getSource() instanceof TraitAnalyzer)
        ) {
            $source->addReturnTypes($context);

            $source->examineParamTypes($statements_analyzer, $context, $codebase, $stmt);

            $storage = $source->getFunctionLikeStorage($statements_analyzer);

            $cased_method_id = $source->getCorrectlyCasedMethodId();

            if ($stmt->expr && $storage->location) {
                $inferred_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $stmt_type,
                    $source->getFQCLN(),
                    $source->getFQCLN(),
                    $source->getParentFQCLN()
                );

                if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
                    self::handleTaints(
                        $statements_analyzer,
                        $stmt,
                        $cased_method_id,
                        $inferred_type,
                        $storage
                    );
                }

                if ($storage instanceof \Psalm\Storage\MethodStorage && $context->self) {
                    $self_class = $context->self;

                    $declared_return_type = $codebase->methods->getMethodReturnType(
                        \Psalm\Internal\MethodIdentifier::wrap($cased_method_id),
                        $self_class,
                        $statements_analyzer,
                        null
                    );
                } else {
                    $declared_return_type = $storage->return_type;
                }

                if ($declared_return_type && !$declared_return_type->hasMixed()) {
                    $local_return_type = $source->getLocalReturnType(
                        $declared_return_type,
                        $storage instanceof \Psalm\Storage\MethodStorage && $storage->final
                    );

                    if ($storage instanceof \Psalm\Storage\MethodStorage) {
                        [$fq_class_name, $method_name] = explode('::', $cased_method_id);

                        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                        $found_generic_params = ClassTemplateParamCollector::collect(
                            $codebase,
                            $class_storage,
                            $class_storage,
                            strtolower($method_name),
                            null,
                            '$this'
                        );

                        if ($found_generic_params) {
                            foreach ($found_generic_params as $template_name => $_) {
                                unset($found_generic_params[$template_name][$fq_class_name]);
                            }

                            $local_return_type = clone $local_return_type;

                            $local_return_type->replaceTemplateTypesWithArgTypes(
                                new TemplateResult([], $found_generic_params),
                                $codebase
                            );
                        }
                    }

                    if ($local_return_type->isGenerator() && $storage->has_yield) {
                        return null;
                    }

                    if ($stmt_type->hasMixed()) {
                        if ($local_return_type->isVoid() || $local_return_type->isNever()) {
                            if (IssueBuffer::accepts(
                                new InvalidReturnStatement(
                                    'No return values are expected for ' . $cased_method_id,
                                    new CodeLocation($source, $stmt->expr)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                return false;
                            }
                        }

                        if (!$context->collect_initializations
                            && !$context->collect_mutations
                            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                            && !($source->getSource() instanceof TraitAnalyzer)
                        ) {
                            $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                        }

                        if ($stmt_type->isMixed()) {
                            if (IssueBuffer::accepts(
                                new MixedReturnStatement(
                                    'Could not infer a return type',
                                    new CodeLocation($source, $stmt->expr)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }

                            return null;
                        }

                        if (IssueBuffer::accepts(
                            new MixedReturnStatement(
                                'Possibly-mixed return value',
                                new CodeLocation($source, $stmt->expr)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    if ($local_return_type->isMixed()) {
                        return null;
                    }

                    if (!$context->collect_initializations
                        && !$context->collect_mutations
                        && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                        && !($source->getSource() instanceof TraitAnalyzer)
                    ) {
                        $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
                    }

                    if ($local_return_type->isVoid()) {
                        if (IssueBuffer::accepts(
                            new InvalidReturnStatement(
                                'No return values are expected for ' . $cased_method_id,
                                new CodeLocation($source, $stmt->expr)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }

                    $union_comparison_results = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

                    if (!UnionTypeComparator::isContainedBy(
                        $codebase,
                        $inferred_type,
                        $local_return_type,
                        true,
                        true,
                        $union_comparison_results
                    )
                    ) {
                        // is the declared return type more specific than the inferred one?
                        if ($union_comparison_results->type_coerced) {
                            if ($union_comparison_results->type_coerced_from_mixed) {
                                if (!$union_comparison_results->type_coerced_from_as_mixed) {
                                    if ($inferred_type->hasMixed()) {
                                        if (IssueBuffer::accepts(
                                            new MixedReturnStatement(
                                                'Could not infer a return type',
                                                new CodeLocation($source, $stmt->expr)
                                            ),
                                            $statements_analyzer->getSuppressedIssues()
                                        )) {
                                            // fall through
                                        }
                                    } else {
                                        if (IssueBuffer::accepts(
                                            new MixedReturnTypeCoercion(
                                                'The type \'' . $stmt_type->getId() . '\' is more general than the'
                                                    . ' declared return type \'' . $local_return_type->getId() . '\''
                                                    . ' for ' . $cased_method_id,
                                                new CodeLocation($source, $stmt->expr)
                                            ),
                                            $statements_analyzer->getSuppressedIssues()
                                        )) {
                                            // fall through
                                        }
                                    }
                                }
                            } else {
                                if (IssueBuffer::accepts(
                                    new LessSpecificReturnStatement(
                                        'The type \'' . $stmt_type->getId() . '\' is more general than the'
                                            . ' declared return type \'' . $local_return_type->getId() . '\''
                                            . ' for ' . $cased_method_id,
                                        new CodeLocation($source, $stmt->expr)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall throuhg
                                }
                            }

                            foreach ($local_return_type->getAtomicTypes() as $local_type_part) {
                                if ($local_type_part instanceof Type\Atomic\TClassString
                                    && $stmt->expr instanceof PhpParser\Node\Scalar\String_
                                ) {
                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                        $statements_analyzer,
                                        $stmt->expr->value,
                                        new CodeLocation($source, $stmt->expr),
                                        $context->self,
                                        $context->calling_method_id,
                                        $statements_analyzer->getSuppressedIssues()
                                    ) === false
                                    ) {
                                        return false;
                                    }
                                } elseif ($local_type_part instanceof Type\Atomic\TArray
                                    && $stmt->expr instanceof PhpParser\Node\Expr\Array_
                                ) {
                                    $value_param = $local_type_part->type_params[1];

                                    foreach ($value_param->getAtomicTypes() as $local_array_type_part) {
                                        if ($local_array_type_part instanceof Type\Atomic\TClassString) {
                                            foreach ($stmt->expr->items as $item) {
                                                if ($item && $item->value instanceof PhpParser\Node\Scalar\String_) {
                                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                                        $statements_analyzer,
                                                        $item->value->value,
                                                        new CodeLocation($source, $item->value),
                                                        $context->self,
                                                        $context->calling_method_id,
                                                        $statements_analyzer->getSuppressedIssues()
                                                    ) === false
                                                    ) {
                                                        return false;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new InvalidReturnStatement(
                                    'The inferred type \'' . $inferred_type->getId()
                                        . '\' does not match the declared return '
                                        . 'type \'' . $local_return_type->getId() . '\' for ' . $cased_method_id,
                                    new CodeLocation($source, $stmt->expr)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }

                    if (!$stmt_type->ignore_nullable_issues
                        && $inferred_type->isNullable()
                        && !$local_return_type->isNullable()
                        && !$local_return_type->hasTemplate()
                    ) {
                        if (IssueBuffer::accepts(
                            new NullableReturnStatement(
                                'The declared return type \'' . $local_return_type->getId() . '\' for '
                                    . $cased_method_id . ' is not nullable, but the function returns \''
                                        . $inferred_type->getId() . '\'',
                                new CodeLocation($source, $stmt->expr)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            //fall through
                        }
                    }

                    if (!$stmt_type->ignore_falsable_issues
                        && $inferred_type->isFalsable()
                        && !$local_return_type->isFalsable()
                        && (!$local_return_type->hasBool() || $local_return_type->isTrue())
                        && !$local_return_type->hasScalar()
                    ) {
                        if (IssueBuffer::accepts(
                            new FalsableReturnStatement(
                                'The declared return type \'' . $local_return_type . '\' for '
                                    . $cased_method_id . ' does not allow false, but the function returns \''
                                        . $inferred_type . '\'',
                                new CodeLocation($source, $stmt->expr)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall throughg
                        }
                    }
                }
            } else {
                if ($storage->signature_return_type
                    && !$storage->signature_return_type->isVoid()
                    && !$storage->has_yield
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidReturnStatement(
                            'Empty return statement is not expected in ' . $cased_method_id,
                            new CodeLocation($source, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        return null;
    }

    private static function handleTaints(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Return_ $stmt,
        string $cased_method_id,
        Type\Union $inferred_type,
        \Psalm\Storage\FunctionLikeStorage $storage
    ) : void {
        if (!$statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            || !$stmt->expr
            || !$storage->location
        ) {
            return;
        }

        $method_node = DataFlowNode::getForMethodReturn(
            strtolower($cased_method_id),
            $cased_method_id,
            $storage->signature_return_type_location ?: $storage->location,
        );

        $statements_analyzer->data_flow_graph->addNode($method_node);

        if ($inferred_type->parent_nodes) {
            foreach ($inferred_type->parent_nodes as $parent_node) {
                $statements_analyzer->data_flow_graph->addPath(
                    $parent_node,
                    $method_node,
                    'return',
                    $storage->added_taints,
                    $storage->removed_taints
                );
            }
        }
    }

    /**
     * If a function returns a closure, we try to infer the param/return types of
     * the inner closure.
     * @see \Psalm\Tests\ReturnTypeTest:756
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $expr
     */
    private static function potentiallyInferTypesOnClosureFromParentReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\FunctionLike $expr,
        Context $context
    ): void {
        // if not returning from inside of a function, return
        if (!$context->calling_method_id && !$context->calling_function_id) {
            return;
        }

        $closure_id = (new ClosureAnalyzer($expr, $statements_analyzer))->getClosureId();
        $closure_storage = $statements_analyzer
            ->getCodebase()
            ->getFunctionLikeStorage($statements_analyzer, $closure_id);

        /** @psalm-suppress ArgumentTypeCoercion */
        $parent_fn_storage = $statements_analyzer
            ->getCodebase()
            ->getFunctionLikeStorage(
                $statements_analyzer,
                $context->calling_function_id ?: $context->calling_method_id
            );

        if ($parent_fn_storage->return_type === null) {
            return;
        }

        // can't infer returned closure if the parent doesn't have a callable return type
        if (!$parent_fn_storage->return_type->hasCallableType()) {
            return;
        }

        // cannot infer if we have union/intersection types
        if (!$parent_fn_storage->return_type->isSingle()) {
            return;
        }

        /** @var Type\Atomic\TClosure|Type\Atomic\TCallable $parent_callable_return_type */
        $parent_callable_return_type = \current($parent_fn_storage->return_type->getAtomicTypes());

        if ($parent_callable_return_type->params === null && $parent_callable_return_type->return_type === null) {
            return;
        }

        foreach ($closure_storage->params as $key => $param) {
            $parent_param = $parent_callable_return_type->params[$key] ?? null;
            $param->type = self::inferInnerClosureTypeFromParent(
                $statements_analyzer->getCodebase(),
                $param->type,
                $parent_param ? $parent_param->type : null
            );
        }

        $closure_storage->return_type = self::inferInnerClosureTypeFromParent(
            $statements_analyzer->getCodebase(),
            $closure_storage->return_type,
            $parent_callable_return_type->return_type
        );
    }

    /**
     * - If non parent type, do nothing
     * - If no return type, infer from parent
     * - If parent return type is more specific, infer from parent
     * - else, do nothing
     */
    private static function inferInnerClosureTypeFromParent(
        Codebase $codebase,
        ?Type\Union $return_type,
        ?Type\Union $parent_return_type
    ): ?Type\Union {
        if (!$parent_return_type) {
            return $return_type;
        }
        if (!$return_type || UnionTypeComparator::isContainedBy($codebase, $parent_return_type, $return_type)) {
            return $parent_return_type;
        }
        return $return_type;
    }
}
