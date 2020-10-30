<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\FunctionLike\ReturnTypeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLike\ReturnTypeCollector;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FunctionDocblockManipulator;
use Psalm\Issue\InvalidDocblockParamName;
use Psalm\Issue\InvalidParamDefault;
use Psalm\Issue\MismatchingDocblockParamType;
use Psalm\Issue\MissingClosureParamType;
use Psalm\Issue\MissingParamType;
use Psalm\Issue\MissingThrowsDocblock;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\UnusedClosureParam;
use Psalm\Issue\UnusedParam;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function md5;
use function strtolower;
use function array_merge;
use function array_filter;
use function array_key_exists;
use function substr;
use function strpos;
use function array_search;
use function array_keys;
use function end;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Storage\FunctionStorage;

/**
 * @internal
 */
abstract class FunctionLikeAnalyzer extends SourceAnalyzer
{
    /**
     * @var Closure|Function_|ClassMethod|ArrowFunction
     */
    protected $function;

    /**
     * @var Codebase
     */
    protected $codebase;

    /**
     * @var array<string>
     */
    protected $suppressed_issues;

    /**
     * @var bool
     */
    protected $is_static = false;

    /**
     * @var StatementsSource
     */
    protected $source;

    /**
     * @var ?array<string, Type\Union>
     */
    protected $return_vars_in_scope = [];

    /**
     * @var ?array<string, bool>
     */
    protected $return_vars_possibly_in_scope = [];

    /**
     * @var Type\Union|null
     */
    private $local_return_type;

    /**
     * @var array<string, bool>
     */
    protected static $no_effects_hashes = [];

    /**
     * @var bool
     */
    public $track_mutations = false;

    /**
     * @var bool
     */
    public $inferred_impure = false;

    /**
     * @var bool
     */
    public $inferred_has_mutation = false;

    /**
     * Holds param nodes for functions with func_get_args calls
     *
     * @var array<string, DataFlowNode>
     */
    public $param_nodes = [];

    /**
     * @var FunctionLikeStorage
     */
    protected $storage;

    /**
     * @param Closure|Function_|ClassMethod|ArrowFunction $function
     */
    public function __construct($function, SourceAnalyzer $source, FunctionLikeStorage $storage)
    {
        $this->function = $function;
        $this->source = $source;
        $this->suppressed_issues = $source->getSuppressedIssues();
        $this->codebase = $source->getCodebase();
        $this->storage = $storage;
    }

    /**
     * @param bool          $add_mutations  whether or not to add mutations to this method
     * @param ?array<string, bool> $byref_uses
     *
     * @return false|null
     */
    public function analyze(
        Context $context,
        \Psalm\Internal\Provider\NodeDataProvider $type_provider,
        ?Context $global_context = null,
        bool $add_mutations = false,
        ?array $byref_uses = null
    ): ?bool {
        $storage = $this->storage;

        $function_stmts = $this->function->getStmts() ?: [];

        if ($this->function instanceof ArrowFunction
            && isset($function_stmts[0])
            && $function_stmts[0] instanceof PhpParser\Node\Stmt\Return_
            && $function_stmts[0]->expr
        ) {
            $function_stmts[0]->setAttributes($function_stmts[0]->expr->getAttributes());
        }

        $hash = null;
        $real_method_id = null;
        $method_id = null;

        $cased_method_id = null;

        $appearing_class_storage = null;

        if ($global_context) {
            foreach ($global_context->constants as $const_name => $var_type) {
                if (!$context->hasVariable($const_name)) {
                    $context->vars_in_scope[$const_name] = clone $var_type;
                }
            }
        }

        $codebase = $this->codebase;
        $project_analyzer = $this->getProjectAnalyzer();

        $implemented_docblock_param_types = [];

        $classlike_storage_provider = $codebase->classlike_storage_provider;

        if ($codebase->track_unused_suppressions && !isset($storage->suppressed_issues[0])) {
            foreach ($storage->suppressed_issues as $offset => $issue_name) {
                IssueBuffer::addUnusedSuppression($this->getFilePath(), $offset, $issue_name);
            }
        }

        foreach ($storage->docblock_issues as $docblock_issue) {
            IssueBuffer::add($docblock_issue);
        }

        $overridden_method_ids = [];

        if ($this->function instanceof ClassMethod) {
            if (!$storage instanceof MethodStorage || !$this instanceof MethodAnalyzer) {
                throw new \UnexpectedValueException('$storage must be MethodStorage');
            }

            $real_method_id = $this->getMethodId();

            $method_id = $this->getMethodId($context->self);

            $fq_class_name = (string)$context->self;
            $appearing_class_storage = $classlike_storage_provider->get($fq_class_name);

            if ($add_mutations) {
                if (!$context->collect_initializations) {
                    $hash = md5($real_method_id . '::' . $context->getScopeSummary());

                    // if we know that the function has no effects on vars, we don't bother rechecking
                    if (isset(self::$no_effects_hashes[$hash])) {
                        return null;
                    }
                }
            } elseif ($context->self) {
                if ($appearing_class_storage->template_types) {
                    $template_params = [];

                    foreach ($appearing_class_storage->template_types as $param_name => $template_map) {
                        $key = array_keys($template_map)[0];

                        $template_params[] = new Type\Union([
                            new Type\Atomic\TTemplateParam(
                                $param_name,
                                \reset($template_map)[0],
                                $key
                            )
                        ]);
                    }

                    $this_object_type = new Type\Atomic\TGenericObject(
                        $context->self,
                        $template_params
                    );
                    $this_object_type->was_static = !$storage->final;
                } else {
                    $this_object_type = new TNamedObject($context->self);
                    $this_object_type->was_static = !$storage->final;
                }

                $context->vars_in_scope['$this'] = new Type\Union([$this_object_type]);

                if ($storage->external_mutation_free
                    && !$storage->mutation_free_inferred
                ) {
                    $context->vars_in_scope['$this']->reference_free = true;

                    if ($this->function->name->name !== '__construct') {
                        $context->vars_in_scope['$this']->allow_mutations = false;
                    }
                }

                $context->vars_possibly_in_scope['$this'] = true;
            }

            if ($appearing_class_storage->has_visitor_issues) {
                return null;
            }

            $cased_method_id = $fq_class_name . '::' . $storage->cased_name;

            $overridden_method_ids = $codebase->methods->getOverriddenMethodIds($method_id);

            if ($this->function->name->name === '__construct') {
                $context->inside_constructor = true;
            }

            $codeLocation = new CodeLocation(
                $this,
                $this->function,
                null,
                true
            );

            if ($overridden_method_ids
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                foreach ($overridden_method_ids as $overridden_method_id) {
                    $parent_method_storage = $codebase->methods->getStorage($overridden_method_id);

                    $overridden_fq_class_name = $overridden_method_id->fq_class_name;

                    $parent_storage = $classlike_storage_provider->get($overridden_fq_class_name);

                    if ($this->function->name->name === '__construct'
                        && !$parent_storage->preserve_constructor_signature
                    ) {
                        continue;
                    }

                    $implementer_visibility = $storage->visibility;

                    $implementer_appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);
                    $implementer_declaring_method_id = $real_method_id;

                    $declaring_class_storage = $appearing_class_storage;

                    if ($implementer_appearing_method_id
                        && $implementer_appearing_method_id !== $implementer_declaring_method_id
                    ) {
                        $appearing_fq_class_name = $implementer_appearing_method_id->fq_class_name;
                        $appearing_method_name = $implementer_appearing_method_id->method_name;

                        $declaring_fq_class_name = $implementer_declaring_method_id->fq_class_name;

                        $appearing_class_storage = $classlike_storage_provider->get(
                            $appearing_fq_class_name
                        );

                        $declaring_class_storage = $classlike_storage_provider->get(
                            $declaring_fq_class_name
                        );

                        if (isset($appearing_class_storage->trait_visibility_map[$appearing_method_name])) {
                            $implementer_visibility
                                = $appearing_class_storage->trait_visibility_map[$appearing_method_name];
                        }
                    }

                    // we've already checked this in the class checker
                    if (!isset($appearing_class_storage->class_implements[strtolower($overridden_fq_class_name)])) {
                        MethodComparator::compare(
                            $codebase,
                            \count($overridden_method_ids) === 1 ? $this->function : null,
                            $declaring_class_storage,
                            $parent_storage,
                            $storage,
                            $parent_method_storage,
                            $fq_class_name,
                            $implementer_visibility,
                            $codeLocation,
                            $storage->suppressed_issues
                        );
                    }

                    foreach ($parent_method_storage->params as $i => $guide_param) {
                        if ($guide_param->type
                            && (!$guide_param->signature_type
                                || ($guide_param->signature_type !== $guide_param->type
                                    && $storage->inheritdoc)
                                || !$parent_storage->user_defined
                            )
                        ) {
                            if (!isset($implemented_docblock_param_types[$i])) {
                                $implemented_docblock_param_types[$i] = $guide_param->type;
                            }
                        }
                    }
                }
            }

            MethodAnalyzer::checkMethodSignatureMustOmitReturnType($storage, $codeLocation);

            if (!$context->calling_method_id || !$context->collect_initializations) {
                $context->calling_method_id = strtolower((string) $method_id);
            }
        } elseif ($this->function instanceof Function_) {
            $function_name = $this->function->name->name;
            $namespace_prefix = $this->getNamespace();
            $cased_method_id = ($namespace_prefix !== null ? $namespace_prefix . '\\' : '') . $function_name;
            $context->calling_function_id = strtolower($cased_method_id);
        } else { // Closure
            if ($storage->return_type) {
                $closure_return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $storage->return_type,
                    $context->self,
                    $context->self,
                    $this->getParentFQCLN()
                );
            } else {
                $closure_return_type = Type::getMixed();
            }

            $closure_type = new Type\Atomic\TClosure(
                'Closure',
                $storage->params,
                $closure_return_type
            );

            if ($storage instanceof FunctionStorage) {
                $closure_type->byref_uses = $storage->byref_uses;
                $closure_type->is_pure = $storage->pure;
            }

            $type_provider->setType(
                $this->function,
                new Type\Union([
                    $closure_type,
                ])
            );
        }

        $this->suppressed_issues = $this->getSource()->getSuppressedIssues() + $storage->suppressed_issues;

        if ($storage instanceof MethodStorage && $storage->is_static) {
            $this->is_static = true;
        }

        $statements_analyzer = new StatementsAnalyzer($this, $type_provider);

        if ($byref_uses) {
            $statements_analyzer->setByRefUses($byref_uses);
        }

        if ($storage->template_types) {
            foreach ($storage->template_types as $param_name => $_) {
                $fq_classlike_name = Type::getFQCLNFromString(
                    $param_name,
                    $this->getAliases()
                );

                if ($codebase->classOrInterfaceExists($fq_classlike_name)) {
                    if (IssueBuffer::accepts(
                        new ReservedWord(
                            'Cannot use ' . $param_name . ' as template name since the class already exists',
                            new CodeLocation($this, $this->function),
                            'resource'
                        ),
                        $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        $template_types = $storage->template_types;

        if ($appearing_class_storage && $appearing_class_storage->template_types) {
            $template_types = array_merge($template_types ?: [], $appearing_class_storage->template_types);
        }

        $params = $storage->params;

        if ($storage instanceof MethodStorage) {
            $non_null_param_types = array_filter(
                $storage->params,
                function (FunctionLikeParameter $p): bool {
                    return $p->type !== null && $p->has_docblock_type;
                }
            );
        } else {
            $non_null_param_types = array_filter(
                $storage->params,
                function (FunctionLikeParameter $p): bool {
                    return $p->type !== null;
                }
            );
        }

        if ($storage instanceof MethodStorage
            && $method_id instanceof \Psalm\Internal\MethodIdentifier
            && $overridden_method_ids
        ) {
            $types_without_docblocks = array_filter(
                $storage->params,
                function (FunctionLikeParameter $p): bool {
                    return !$p->type || !$p->has_docblock_type;
                }
            );

            if ($types_without_docblocks) {
                $params = $codebase->methods->getMethodParams(
                    $method_id,
                    $this
                );
            }
        }

        if ($codebase->alter_code) {
            $this->alterParams($codebase, $storage, $params, $context);
        }

        foreach ($codebase->methods_to_rename as $original_method_id => $new_method_name) {
            if ($this->function instanceof ClassMethod
                && $this instanceof MethodAnalyzer
                && strtolower((string) $this->getMethodId()) === $original_method_id
            ) {
                $file_manipulations = [
                    new \Psalm\FileManipulation(
                        (int) $this->function->name->getAttribute('startFilePos'),
                        (int) $this->function->name->getAttribute('endFilePos') + 1,
                        $new_method_name
                    )
                ];

                \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                    $this->getFilePath(),
                    $file_manipulations
                );
            }
        }

        $check_stmts = $this->processParams(
            $statements_analyzer,
            $storage,
            $cased_method_id,
            $params,
            $context,
            $implemented_docblock_param_types,
            (bool) $non_null_param_types,
            (bool) $template_types
        );

        if ($storage->pure) {
            $context->pure = true;
        }

        if ($storage->mutation_free
            && $cased_method_id
            && !strpos($cased_method_id, '__construct')
            && !($storage instanceof MethodStorage && $storage->mutation_free_inferred)
        ) {
            $context->mutation_free = true;
        }

        if ($storage instanceof MethodStorage
            && $storage->external_mutation_free
            && !$storage->mutation_free_inferred
        ) {
            $context->external_mutation_free = true;
        }

        if ($storage->unused_docblock_params) {
            foreach ($storage->unused_docblock_params as $param_name => $param_location) {
                if (IssueBuffer::accepts(
                    new InvalidDocblockParamName(
                        'Incorrect param name $' . $param_name . ' in docblock for ' . $cased_method_id,
                        $param_location
                    )
                )) {
                }
            }
        }

        if ($storage->signature_return_type && $storage->signature_return_type_location) {
            [$start, $end] = $storage->signature_return_type_location->getSelectionBounds();

            $codebase->analyzer->addOffsetReference(
                $this->getFilePath(),
                $start,
                $end,
                (string) $storage->signature_return_type
            );
        }

        if (ReturnTypeAnalyzer::checkReturnType(
            $this->function,
            $project_analyzer,
            $this,
            $storage,
            $context
        ) === false) {
            $check_stmts = false;
        }

        if (!$check_stmts) {
            return false;
        }

        if ($context->collect_initializations || $context->collect_mutations) {
            $statements_analyzer->addSuppressedIssues([
                'DocblockTypeContradiction',
                'InvalidReturnStatement',
                'RedundantCondition',
                'RedundantConditionGivenDocblockType',
                'TypeDoesNotContainNull',
                'TypeDoesNotContainType',
                'LoopInvalidation',
            ]);

            if ($context->collect_initializations) {
                $statements_analyzer->addSuppressedIssues([
                    'UndefinedInterfaceMethod',
                    'UndefinedMethod',
                    'PossiblyUndefinedMethod',
                ]);
            }
        } elseif ($cased_method_id && strpos($cased_method_id, '__destruct')) {
            $statements_analyzer->addSuppressedIssues([
                'InvalidPropertyAssignmentValue',
                'PossiblyNullPropertyAssignmentValue',
            ]);
        }

        $time = \microtime(true);

        $project_analyzer = $statements_analyzer->getProjectAnalyzer();

        if ($codebase->alter_code
            && (isset($project_analyzer->getIssuesToFix()['MissingPureAnnotation'])
                || isset($project_analyzer->getIssuesToFix()['MissingImmutableAnnotation']))
        ) {
            $this->track_mutations = true;
        } elseif ($this->function instanceof Closure
            || $this->function instanceof ArrowFunction
        ) {
            $this->track_mutations = true;
        }

        $statements_analyzer->analyze($function_stmts, $context, $global_context, true);

        if ($codebase->alter_code
            && isset($project_analyzer->getIssuesToFix()['MissingPureAnnotation'])
            && !$this->inferred_impure
            && ($this->function instanceof Function_
                || $this->function instanceof ClassMethod)
            && $storage->params
            && !$overridden_method_ids
        ) {
            $manipulator = FunctionDocblockManipulator::getForFunction(
                $project_analyzer,
                $this->source->getFilePath(),
                $this->function
            );

            $yield_types = [];

            $inferred_return_types = ReturnTypeCollector::getReturnTypes(
                $codebase,
                $type_provider,
                $function_stmts,
                $yield_types,
                true
            );

            $inferred_return_type = $inferred_return_types
                ? \Psalm\Type::combineUnionTypeArray(
                    $inferred_return_types,
                    $codebase
                )
                : null;

            if ($inferred_return_type
                && !$inferred_return_type->isVoid()
                && !$inferred_return_type->isFalse()
                && !$inferred_return_type->isNull()
                && !$inferred_return_type->isSingleIntLiteral()
                && !$inferred_return_type->isSingleStringLiteral()
                && !$inferred_return_type->isTrue()
                && $inferred_return_type->getId() !== 'array<empty, empty>'
            ) {
                $manipulator->makePure();
            }
        }

        if ($this->inferred_has_mutation && $context->self) {
            $this->codebase->analyzer->addMutableClass($context->self);
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $project_analyzer->debug_performance
            && $cased_method_id
        ) {
            $traverser = new PhpParser\NodeTraverser;

            $node_counter = new \Psalm\Internal\PhpVisitor\NodeCounterVisitor();
            $traverser->addVisitor($node_counter);
            $traverser->traverse($function_stmts);

            if ($node_counter->count > 5) {
                $time_taken = \microtime(true) - $time;
                $codebase->analyzer->addFunctionTiming($cased_method_id, $time_taken / $node_counter->count);
            }
        }

        $this->examineParamTypes($statements_analyzer, $context, $codebase);

        foreach ($storage->params as $offset => $function_param) {
            // only complain if there's no type defined by a parent type
            if (!$function_param->type
                && $function_param->location
                && !isset($implemented_docblock_param_types[$offset])
            ) {
                if ($this->function instanceof Closure
                    || $this->function instanceof ArrowFunction
                ) {
                    IssueBuffer::accepts(
                        new MissingClosureParamType(
                            'Parameter $' . $function_param->name . ' has no provided type',
                            $function_param->location
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    );
                } else {
                    IssueBuffer::accepts(
                        new MissingParamType(
                            'Parameter $' . $function_param->name . ' has no provided type',
                            $function_param->location
                        ),
                        $storage->suppressed_issues + $this->getSuppressedIssues()
                    );
                }
            }
        }

        if ($this->function instanceof Closure
            || $this->function instanceof ArrowFunction
        ) {
            $this->verifyReturnType(
                $function_stmts,
                $statements_analyzer,
                $storage->return_type,
                $this->source->getFQCLN(),
                $storage->return_type_location,
                $context->has_returned,
                $global_context && $global_context->inside_call
            );

            $closure_yield_types = [];

            $closure_return_types = ReturnTypeCollector::getReturnTypes(
                $codebase,
                $type_provider,
                $function_stmts,
                $closure_yield_types,
                true
            );

            $closure_return_type = $closure_return_types
                ? \Psalm\Type::combineUnionTypeArray(
                    $closure_return_types,
                    $codebase
                )
                : null;

            $closure_yield_type = $closure_yield_types
                ? \Psalm\Type::combineUnionTypeArray(
                    $closure_yield_types,
                    $codebase
                )
                : null;

            if ($closure_return_type || $closure_yield_type) {
                if ($closure_yield_type) {
                    $closure_return_type = $closure_yield_type;
                }

                if ($function_type = $statements_analyzer->node_data->getType($this->function)) {
                    /**
                     * @var Type\Atomic\TClosure
                     */
                    $closure_atomic = \array_values($function_type->getAtomicTypes())[0];

                    if (($storage->return_type === $storage->signature_return_type)
                        && (!$storage->return_type
                            || $storage->return_type->hasMixed()
                            || UnionTypeComparator::isContainedBy(
                                $codebase,
                                $closure_return_type,
                                $storage->return_type
                            ))
                    ) {
                        $closure_atomic->return_type = $closure_return_type;
                    }

                    $closure_atomic->is_pure = !$this->inferred_impure;
                }
            }
        }

        if ($codebase->collect_references
            && !$context->collect_initializations
            && !$context->collect_mutations
            && $codebase->find_unused_variables
            && $context->check_variables
        ) {
            $this->checkParamReferences(
                $statements_analyzer,
                $storage,
                $appearing_class_storage,
                $context
            );
        }

        foreach ($storage->throws as $expected_exception => $_) {
            if (($expected_exception === 'self'
                    || $expected_exception === 'static')
                && $context->self
            ) {
                $expected_exception = $context->self;
            }

            if (isset($storage->throw_locations[$expected_exception])) {
                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $expected_exception,
                    $storage->throw_locations[$expected_exception],
                    $context->self,
                    $context->calling_method_id,
                    $statements_analyzer->getSuppressedIssues(),
                    false,
                    false,
                    true,
                    true
                )) {
                    $input_type = new Type\Union([new TNamedObject($expected_exception)]);
                    $container_type = new Type\Union([new TNamedObject('Exception'), new TNamedObject('Throwable')]);

                    if (!UnionTypeComparator::isContainedBy($codebase, $input_type, $container_type)) {
                        if (IssueBuffer::accepts(
                            new \Psalm\Issue\InvalidThrow(
                                'Class supplied for @throws ' . $expected_exception
                                    . ' does not implement Throwable',
                                $storage->throw_locations[$expected_exception],
                                $expected_exception
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }

                    if ($codebase->alter_code) {
                        $codebase->classlikes->handleDocblockTypeInMigration(
                            $codebase,
                            $this,
                            $input_type,
                            $storage->throw_locations[$expected_exception],
                            $context->calling_method_id
                        );
                    }
                }
            }
        }

        foreach ($statements_analyzer->getUncaughtThrows($context) as $possibly_thrown_exception => $codelocations) {
            $is_expected = false;

            foreach ($storage->throws as $expected_exception => $_) {
                if ($expected_exception === $possibly_thrown_exception
                    || $codebase->classExtendsOrImplements($possibly_thrown_exception, $expected_exception)
                ) {
                    $is_expected = true;
                    break;
                }
            }

            if (!$is_expected) {
                foreach ($codelocations as $codelocation) {
                    // issues are suppressed in ThrowAnalyzer, CallAnalyzer, etc.
                    if (IssueBuffer::accepts(
                        new MissingThrowsDocblock(
                            $possibly_thrown_exception . ' is thrown but not caught - please either catch'
                                . ' or add a @throws annotation',
                            $codelocation
                        )
                    )) {
                        // fall through
                    }
                }
            }
        }

        if ($codebase->taint_flow_graph
            && $this->function instanceof ClassMethod
            && $cased_method_id
            && $storage->specialize_call
            && isset($context->vars_in_scope['$this'])
            && $context->vars_in_scope['$this']->parent_nodes
        ) {
            $method_source = DataFlowNode::getForMethodReturn(
                (string) $method_id,
                $cased_method_id,
                $storage->location
            );

            $codebase->taint_flow_graph->addNode($method_source);

            foreach ($context->vars_in_scope['$this']->parent_nodes as $parent_node) {
                $codebase->taint_flow_graph->addPath(
                    $parent_node,
                    $method_source,
                    '$this'
                );
            }
        }

        if ($add_mutations) {
            if ($this->return_vars_in_scope !== null) {
                $context->vars_in_scope = TypeAnalyzer::combineKeyedTypes(
                    $context->vars_in_scope,
                    $this->return_vars_in_scope
                );
            }

            if ($this->return_vars_possibly_in_scope !== null) {
                $context->vars_possibly_in_scope = array_merge(
                    $context->vars_possibly_in_scope,
                    $this->return_vars_possibly_in_scope
                );
            }

            foreach ($context->vars_in_scope as $var => $_) {
                if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                    unset($context->vars_in_scope[$var]);
                }
            }

            foreach ($context->vars_possibly_in_scope as $var => $_) {
                if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                    unset($context->vars_possibly_in_scope[$var]);
                }
            }

            if ($hash
                && $real_method_id
                && $this instanceof MethodAnalyzer
                && !$context->collect_initializations
            ) {
                $new_hash = md5($real_method_id . '::' . $context->getScopeSummary());

                if ($new_hash === $hash) {
                    self::$no_effects_hashes[$hash] = true;
                }
            }
        }

        $plugin_classes = $codebase->config->after_functionlike_checks;

        if ($plugin_classes) {
            $file_manipulations = [];

            foreach ($plugin_classes as $plugin_fq_class_name) {
                if ($plugin_fq_class_name::afterStatementAnalysis(
                    $this->function,
                    $storage,
                    $this,
                    $codebase,
                    $file_manipulations
                ) === false) {
                    return false;
                }
            }

            if ($file_manipulations) {
                \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                    $this->getFilePath(),
                    $file_manipulations
                );
            }
        }

        return null;
    }

    private function checkParamReferences(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
        ?\Psalm\Storage\ClassLikeStorage $class_storage,
        Context $context
    ) : void {
        $codebase = $statements_analyzer->getCodebase();

        $unused_params = [];

        foreach ($statements_analyzer->getUnusedVarLocations() as [$var_name, $original_location]) {
            if (!array_key_exists(substr($var_name, 1), $storage->param_lookup)) {
                continue;
            }

            if (strpos($var_name, '$_') === 0 || (strpos($var_name, '$unused') === 0 && $var_name !== '$unused')) {
                continue;
            }

            $position = array_search(substr($var_name, 1), array_keys($storage->param_lookup), true);

            if ($position === false) {
                throw new \UnexpectedValueException('$position should not be false here');
            }

            if ($storage->params[$position]->by_ref) {
                continue;
            }

            $did_match_param = false;

            foreach ($this->function->params as $param) {
                if ($param->var->getAttribute('endFilePos') === $original_location->raw_file_end) {
                    $did_match_param = true;
                    break;
                }
            }

            if (!$did_match_param) {
                continue;
            }

            $assignment_node = DataFlowNode::getForAssignment($var_name, $original_location);

            if ($statements_analyzer->data_flow_graph instanceof \Psalm\Internal\Codebase\VariableUseGraph
                && $statements_analyzer->data_flow_graph->isVariableUsed($assignment_node)
            ) {
                continue;
            }

            if (!($storage instanceof MethodStorage)
                || !$storage->cased_name
                || $storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                if ($this->function instanceof Closure
                    || $this->function instanceof ArrowFunction
                ) {
                    if (IssueBuffer::accepts(
                        new UnusedClosureParam(
                            'Param ' . $var_name . ' is never referenced in this method',
                            $original_location
                        ),
                        $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UnusedParam(
                            'Param ' . $var_name . ' is never referenced in this method',
                            $original_location
                        ),
                        $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } else {
                $fq_class_name = (string)$context->self;

                $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                $method_name_lc = strtolower($storage->cased_name);

                if ($storage->abstract) {
                    continue;
                }

                if (isset($class_storage->overridden_method_ids[$method_name_lc])) {
                    $parent_method_id = end($class_storage->overridden_method_ids[$method_name_lc]);

                    if ($parent_method_id) {
                        $parent_method_storage = $codebase->methods->getStorage($parent_method_id);

                        // if the parent method has a param at that position and isn't abstract
                        if (!$parent_method_storage->abstract
                            && isset($parent_method_storage->params[$position])
                        ) {
                            continue;
                        }
                    }
                }

                $unused_params[$position] = $original_location;
            }
        }

        if ($storage instanceof MethodStorage
            && $this instanceof MethodAnalyzer
            && $class_storage
            && $storage->cased_name
            && $storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE
        ) {
            $method_id_lc = strtolower((string) $this->getMethodId());

            foreach ($storage->params as $i => $_) {
                if (!isset($unused_params[$i])) {
                    $codebase->file_reference_provider->addMethodParamUse(
                        $method_id_lc,
                        $i,
                        $method_id_lc
                    );

                    $method_name_lc = strtolower($storage->cased_name);

                    if (!isset($class_storage->overridden_method_ids[$method_name_lc])) {
                        continue;
                    }

                    foreach ($class_storage->overridden_method_ids[$method_name_lc] as $parent_method_id) {
                        $codebase->file_reference_provider->addMethodParamUse(
                            strtolower((string) $parent_method_id),
                            $i,
                            $method_id_lc
                        );
                    }
                }
            }
        }
    }

    /**
     * @param array<int, \Psalm\Storage\FunctionLikeParameter> $params
     * @param array<int, Type\Union> $implemented_docblock_param_types
     */
    private function processParams(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
        ?string $cased_method_id,
        array $params,
        Context $context,
        array $implemented_docblock_param_types,
        bool $non_null_param_types,
        bool $has_template_types
    ) : bool {
        $check_stmts = true;
        $codebase = $statements_analyzer->getCodebase();
        $project_analyzer = $statements_analyzer->getProjectAnalyzer();

        foreach ($params as $offset => $function_param) {
            $signature_type = $function_param->signature_type;
            $signature_type_location = $function_param->signature_type_location;

            if ($signature_type && $signature_type_location && $signature_type->hasObjectType()) {
                $referenced_type = $signature_type;
                if ($referenced_type->isNullable()) {
                    $referenced_type = clone $referenced_type;
                    $referenced_type->removeType('null');
                }
                [$start, $end] = $signature_type_location->getSelectionBounds();
                $codebase->analyzer->addOffsetReference(
                    $this->getFilePath(),
                    $start,
                    $end,
                    (string) $referenced_type
                );
            }

            if ($signature_type) {
                $signature_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $signature_type,
                    $context->self,
                    $context->self,
                    $this->getParentFQCLN()
                );
            }

            if ($function_param->type) {
                $is_signature_type = $function_param->type === $function_param->signature_type;

                if ($is_signature_type
                    && $storage instanceof MethodStorage
                    && $storage->inheritdoc
                    && isset($implemented_docblock_param_types[$offset])
                ) {
                    $param_type = clone $implemented_docblock_param_types[$offset];
                } else {
                    $param_type = clone $function_param->type;
                }

                $param_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $param_type,
                    $context->self,
                    $context->self,
                    $this->getParentFQCLN()
                );

                if ($function_param->type_location) {
                    if ($param_type->check(
                        $this,
                        $function_param->type_location,
                        $storage->suppressed_issues,
                        [],
                        false,
                        false,
                        $this->function instanceof ClassMethod
                            && strtolower($this->function->name->name) !== '__construct'
                    ) === false) {
                        $check_stmts = false;
                    }
                }
            } else {
                if (!$non_null_param_types && isset($implemented_docblock_param_types[$offset])) {
                    $param_type = clone $implemented_docblock_param_types[$offset];

                    $param_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                        $codebase,
                        $param_type,
                        $context->self,
                        $context->self,
                        $this->getParentFQCLN()
                    );
                } else {
                    $param_type = Type::getMixed();
                }
            }

            if ($param_type->hasTemplate() && $param_type->isSingle()) {
                /** @var Type\Atomic\TTemplateParam */
                $template_type = \array_values($param_type->getAtomicTypes())[0];

                if ($template_type->as->getTemplateTypes()) {
                    $param_type = $template_type->as;
                }
            }

            $var_type = $param_type;

            if ($function_param->is_variadic) {
                $var_type = new Type\Union([
                    new Type\Atomic\TList($param_type),
                ]);
            }

            if ($statements_analyzer->data_flow_graph
                && $function_param->location
            ) {
                $param_assignment = DataFlowNode::getForAssignment(
                    '$' . $function_param->name,
                    $function_param->location
                );

                $statements_analyzer->data_flow_graph->addNode($param_assignment);

                if ($cased_method_id) {
                    $type_source = DataFlowNode::getForMethodArgument(
                        $cased_method_id,
                        $cased_method_id,
                        $offset,
                        $function_param->location,
                        null
                    );

                    $statements_analyzer->data_flow_graph->addPath($type_source, $param_assignment, 'param');
                }

                if ($function_param->by_ref
                    && $codebase->find_unused_variables
                ) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $param_assignment,
                        new DataFlowNode('variable-use', 'variable use', null),
                        'variable-use'
                    );
                }

                if ($storage->variadic) {
                    $this->param_nodes += [$param_assignment->id => $param_assignment];
                }

                $var_type->parent_nodes += [$param_assignment->id => $param_assignment];
            }

            $context->vars_in_scope['$' . $function_param->name] = $var_type;
            $context->vars_possibly_in_scope['$' . $function_param->name] = true;

            if ($function_param->by_ref) {
                $context->vars_in_scope['$' . $function_param->name]->by_ref = true;
            }

            $parser_param = $this->function->getParams()[$offset];

            if ($function_param->location) {
                $statements_analyzer->registerVariable(
                    '$' . $function_param->name,
                    $function_param->location,
                    null
                );
            }

            if (!$function_param->type_location || !$function_param->location) {
                if ($parser_param->default) {
                    ExpressionAnalyzer::analyze($statements_analyzer, $parser_param->default, $context);
                }

                continue;
            }

            if ($signature_type) {
                $union_comparison_result = new TypeComparisonResult();

                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $param_type,
                    $signature_type,
                    false,
                    false,
                    $union_comparison_result
                ) && !$union_comparison_result->type_coerced_from_mixed
                ) {
                    if ($codebase->alter_code
                        && isset($project_analyzer->getIssuesToFix()['MismatchingDocblockParamType'])
                    ) {
                        $this->addOrUpdateParamType($project_analyzer, $function_param->name, $signature_type, true);

                        continue;
                    }

                    if (IssueBuffer::accepts(
                        new MismatchingDocblockParamType(
                            'Parameter $' . $function_param->name . ' has wrong type \'' . $param_type .
                                '\', should be \'' . $signature_type . '\'',
                            $function_param->type_location
                        ),
                        $storage->suppressed_issues,
                        true
                    )) {
                        // do nothing
                    }

                    if ($signature_type->check(
                        $this,
                        $function_param->type_location,
                        $storage->suppressed_issues,
                        [],
                        false
                    ) === false) {
                        $check_stmts = false;
                    }

                    continue;
                }
            }

            if ($parser_param->default) {
                ExpressionAnalyzer::analyze($statements_analyzer, $parser_param->default, $context);

                $default_type = $statements_analyzer->node_data->getType($parser_param->default);

                if ($default_type
                    && !$default_type->hasMixed()
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $default_type,
                        $param_type,
                        false,
                        false,
                        null,
                        true
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidParamDefault(
                            'Default value type ' . $default_type->getId() . ' for argument ' . ($offset + 1)
                                . ' of method ' . $cased_method_id
                                . ' does not match the given type ' . $param_type->getId(),
                            $function_param->type_location
                        )
                    )) {
                        // fall through
                    }
                }
            }

            if ($has_template_types) {
                $substituted_type = clone $param_type;
                if ($substituted_type->check(
                    $this->source,
                    $function_param->type_location,
                    $this->suppressed_issues,
                    [],
                    false
                ) === false) {
                    $check_stmts = false;
                }
            } else {
                if ($param_type->isVoid()) {
                    if (IssueBuffer::accepts(
                        new ReservedWord(
                            'Parameter cannot be void',
                            $function_param->type_location,
                            'void'
                        ),
                        $this->suppressed_issues
                    )) {
                        // fall through
                    }
                }

                if ($param_type->check(
                    $this->source,
                    $function_param->type_location,
                    $this->suppressed_issues,
                    [],
                    false
                ) === false) {
                    $check_stmts = false;
                }
            }

            if ($codebase->collect_locations) {
                if ($function_param->type_location !== $function_param->signature_type_location &&
                    $function_param->signature_type_location &&
                    $function_param->signature_type
                ) {
                    if ($function_param->signature_type->check(
                        $this->source,
                        $function_param->signature_type_location,
                        $this->suppressed_issues,
                        [],
                        false
                    ) === false) {
                        $check_stmts = false;
                    }
                }
            }

            if ($function_param->by_ref) {
                // register by ref params as having been used, to avoid false positives
                // @todo change the assignment analysis *just* for byref params
                // so that we don't have to do this
                $context->hasVariable('$' . $function_param->name);
            }
        }

        return $check_stmts;
    }

    /**
     * @param \Psalm\Storage\FunctionLikeParameter[] $params
     */
    private function alterParams(
        Codebase $codebase,
        FunctionLikeStorage $storage,
        array $params,
        Context $context
    ) : void {
        foreach ($this->function->params as $param) {
            $param_name_node = null;

            if ($param->type instanceof PhpParser\Node\Name) {
                $param_name_node = $param->type;
            } elseif ($param->type instanceof PhpParser\Node\NullableType
                && $param->type->type instanceof PhpParser\Node\Name
            ) {
                $param_name_node = $param->type->type;
            }

            if ($param_name_node) {
                $resolved_name = ClassLikeAnalyzer::getFQCLNFromNameObject($param_name_node, $this->getAliases());

                $parent_fqcln = $this->getParentFQCLN();

                if ($resolved_name === 'self' && $context->self) {
                    $resolved_name = (string) $context->self;
                } elseif ($resolved_name === 'parent' && $parent_fqcln) {
                    $resolved_name = $parent_fqcln;
                }

                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $this,
                    $param_name_node,
                    $resolved_name,
                    $context->calling_method_id,
                    false,
                    true
                );
            }
        }

        if ($this->function->returnType) {
            $return_name_node = null;

            if ($this->function->returnType instanceof PhpParser\Node\Name) {
                $return_name_node = $this->function->returnType;
            } elseif ($this->function->returnType instanceof PhpParser\Node\NullableType
                && $this->function->returnType->type instanceof PhpParser\Node\Name
            ) {
                $return_name_node = $this->function->returnType->type;
            }

            if ($return_name_node) {
                $resolved_name = ClassLikeAnalyzer::getFQCLNFromNameObject($return_name_node, $this->getAliases());

                $parent_fqcln = $this->getParentFQCLN();

                if ($resolved_name === 'self' && $context->self) {
                    $resolved_name = (string) $context->self;
                } elseif ($resolved_name === 'parent' && $parent_fqcln) {
                    $resolved_name = $parent_fqcln;
                }

                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $this,
                    $return_name_node,
                    $resolved_name,
                    $context->calling_method_id,
                    false,
                    true
                );
            }
        }

        if ($storage->return_type
            && $storage->return_type_location
            && $storage->return_type_location !== $storage->signature_return_type_location
        ) {
            $replace_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $storage->return_type,
                $context->self,
                'static',
                $this->getParentFQCLN(),
                false
            );

            $codebase->classlikes->handleDocblockTypeInMigration(
                $codebase,
                $this,
                $replace_type,
                $storage->return_type_location,
                $context->calling_method_id
            );
        }

        foreach ($params as $function_param) {
            if ($function_param->type
                && $function_param->type_location
                && $function_param->type_location !== $function_param->signature_type_location
                && $function_param->type_location->file_path === $this->getFilePath()
            ) {
                $replace_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                    $codebase,
                    $function_param->type,
                    $context->self,
                    'static',
                    $this->getParentFQCLN(),
                    false
                );

                $codebase->classlikes->handleDocblockTypeInMigration(
                    $codebase,
                    $this,
                    $replace_type,
                    $function_param->type_location,
                    $context->calling_method_id
                );
            }
        }
    }

    /**
     * @param array<PhpParser\Node\Stmt> $function_stmts
     */
    public function verifyReturnType(
        array $function_stmts,
        StatementsAnalyzer $statements_analyzer,
        ?Type\Union $return_type = null,
        ?string $fq_class_name = null,
        ?CodeLocation $return_type_location = null,
        bool $did_explicitly_return = false,
        bool $closure_inside_call = false
    ): void {
        ReturnTypeAnalyzer::verifyReturnType(
            $this->function,
            $function_stmts,
            $statements_analyzer,
            $statements_analyzer->node_data,
            $this,
            $return_type,
            $fq_class_name,
            $return_type_location,
            [],
            $did_explicitly_return,
            $closure_inside_call
        );
    }

    public function addOrUpdateParamType(
        ProjectAnalyzer $project_analyzer,
        string $param_name,
        Type\Union $inferred_return_type,
        bool $docblock_only = false
    ): void {
        $manipulator = FunctionDocblockManipulator::getForFunction(
            $project_analyzer,
            $this->source->getFilePath(),
            $this->function
        );

        $codebase = $project_analyzer->getCodebase();
        $is_final = true;
        $fqcln = $this->source->getFQCLN();

        if ($fqcln !== null && $this->function instanceof ClassMethod) {
            $class_storage = $codebase->classlike_storage_provider->get($fqcln);
            $is_final = $this->function->isFinal() || $class_storage->final;
        }

        $allow_native_type = !$docblock_only
            && $codebase->php_major_version >= 7
            && (
                $codebase->allow_backwards_incompatible_changes
                || $is_final
                || !$this->function instanceof PhpParser\Node\Stmt\ClassMethod
            );

        $manipulator->setParamType(
            $param_name,
            $allow_native_type
                ? $inferred_return_type->toPhpString(
                    $this->source->getNamespace(),
                    $this->source->getAliasedClassesFlipped(),
                    $this->source->getFQCLN(),
                    $project_analyzer->getCodebase()->php_major_version,
                    $project_analyzer->getCodebase()->php_minor_version
                ) : null,
            $inferred_return_type->toNamespacedString(
                $this->source->getNamespace(),
                $this->source->getAliasedClassesFlipped(),
                $this->source->getFQCLN(),
                false
            ),
            $inferred_return_type->toNamespacedString(
                $this->source->getNamespace(),
                $this->source->getAliasedClassesFlipped(),
                $this->source->getFQCLN(),
                true
            )
        );
    }

    /**
     * Adds return types for the given function
     *
     * @param   string  $return_type
     *
     */
    public function addReturnTypes(Context $context): void
    {
        if ($this->return_vars_in_scope !== null) {
            $this->return_vars_in_scope = TypeAnalyzer::combineKeyedTypes(
                $context->vars_in_scope,
                $this->return_vars_in_scope
            );
        } else {
            $this->return_vars_in_scope = $context->vars_in_scope;
        }

        if ($this->return_vars_possibly_in_scope !== null) {
            $this->return_vars_possibly_in_scope = array_merge(
                $context->vars_possibly_in_scope,
                $this->return_vars_possibly_in_scope
            );
        } else {
            $this->return_vars_possibly_in_scope = $context->vars_possibly_in_scope;
        }
    }

    public function examineParamTypes(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        Codebase $codebase,
        PhpParser\Node $stmt = null
    ): void {
        $storage = $this->getFunctionLikeStorage($statements_analyzer);

        foreach ($storage->params as $param) {
            if ($param->by_ref && isset($context->vars_in_scope['$' . $param->name]) && !$param->is_variadic) {
                $actual_type = $context->vars_in_scope['$' . $param->name];
                $param_out_type = $param->out_type ?: $param->type;

                if ($param_out_type && !$actual_type->hasMixed() && $param->location) {
                    if (!UnionTypeComparator::isContainedBy(
                        $codebase,
                        $actual_type,
                        $param_out_type,
                        $actual_type->ignore_nullable_issues,
                        $actual_type->ignore_falsable_issues
                    )
                    ) {
                        if (IssueBuffer::accepts(
                            new ReferenceConstraintViolation(
                                'Variable ' . '$' . $param->name . ' is limited to values of type '
                                    . $param_out_type->getId()
                                    . ' because it is passed by reference, '
                                    . $actual_type->getId() . ' type found. Use @param-out to specify '
                                    . 'a different output type',
                                $stmt
                                    ? new CodeLocation($this, $stmt)
                                    : $param->location
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }
    }

    public function getMethodName(): ?string
    {
        if ($this->function instanceof ClassMethod) {
            return (string)$this->function->name;
        }

        return null;
    }

    public function getCorrectlyCasedMethodId(?string $context_self = null): string
    {
        if ($this->function instanceof ClassMethod) {
            $function_name = (string)$this->function->name;

            return ($context_self ?: $this->source->getFQCLN()) . '::' . $function_name;
        }

        if ($this->function instanceof Function_) {
            $namespace = $this->source->getNamespace();

            return ($namespace ? $namespace . '\\' : '') . $this->function->name;
        }

        if (!$this instanceof ClosureAnalyzer) {
            throw new \UnexpectedValueException('This is weird');
        }

        return $this->getClosureId();
    }

    public function getFunctionLikeStorage(?StatementsAnalyzer $statements_analyzer = null): FunctionLikeStorage
    {
        $codebase = $this->codebase;

        if ($this->function instanceof ClassMethod && $this instanceof MethodAnalyzer) {
            $method_id = $this->getMethodId();
            $codebase_methods = $codebase->methods;

            try {
                return $codebase_methods->getStorage($method_id);
            } catch (\UnexpectedValueException $e) {
                $declaring_method_id = $codebase_methods->getDeclaringMethodId($method_id);

                if ($declaring_method_id === null) {
                    throw new \UnexpectedValueException('Cannot get storage for function that doesn‘t exist');
                }

                // happens for fake constructors
                return $codebase_methods->getStorage($declaring_method_id);
            }
        }

        if ($this instanceof FunctionAnalyzer) {
            $function_id = $this->getFunctionId();
        } elseif ($this instanceof ClosureAnalyzer) {
            $function_id = $this->getClosureId();
        } else {
            throw new \UnexpectedValueException('This is weird');
        }

        return $codebase->functions->getStorage($statements_analyzer, $function_id);
    }

    /** @return non-empty-string */
    public function getId() : string
    {
        if ($this instanceof MethodAnalyzer) {
            return (string) $this->getMethodId();
        }

        if ($this instanceof FunctionAnalyzer) {
            return $this->getFunctionId();
        }

        if ($this instanceof ClosureAnalyzer) {
            return $this->getClosureId();
        }

        throw new \UnexpectedValueException('This is weird');
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped(): array
    {
        if ($this->source instanceof NamespaceAnalyzer ||
            $this->source instanceof FileAnalyzer ||
            $this->source instanceof ClassLikeAnalyzer
        ) {
            return $this->source->getAliasedClassesFlipped();
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array
    {
        if ($this->source instanceof NamespaceAnalyzer ||
            $this->source instanceof FileAnalyzer ||
            $this->source instanceof ClassLikeAnalyzer
        ) {
            return $this->source->getAliasedClassesFlippedReplaceable();
        }

        return [];
    }

    public function getFQCLN(): ?string
    {
        return $this->source->getFQCLN();
    }

    public function getClassName(): ?string
    {
        return $this->source->getClassName();
    }

    /**
     * @return array<string, array<string, array{Type\Union}>>|null
     */
    public function getTemplateTypeMap(): ?array
    {
        if ($this->source instanceof ClassLikeAnalyzer) {
            return ($this->source->getTemplateTypeMap() ?: [])
                + ($this->storage->template_types ?: []);
        }

        return $this->storage->template_types;
    }

    public function getParentFQCLN(): ?string
    {
        return $this->source->getParentFQCLN();
    }

    public function getNodeTypeProvider() : \Psalm\NodeTypeProvider
    {
        return $this->source->getNodeTypeProvider();
    }

    public function isStatic(): bool
    {
        return $this->is_static;
    }

    public function getSource(): StatementsSource
    {
        return $this->source;
    }

    public function getCodebase() : Codebase
    {
        return $this->codebase;
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues(): array
    {
        return $this->suppressed_issues;
    }

    /**
     * @param array<int, string> $new_issues
     */
    public function addSuppressedIssues(array $new_issues): void
    {
        if (isset($new_issues[0])) {
            $new_issues = \array_combine($new_issues, $new_issues);
        }

        $this->suppressed_issues = $new_issues + $this->suppressed_issues;
    }

    /**
     * @param array<int, string> $new_issues
     */
    public function removeSuppressedIssues(array $new_issues): void
    {
        if (isset($new_issues[0])) {
            $new_issues = \array_combine($new_issues, $new_issues);
        }

        $this->suppressed_issues = \array_diff_key($this->suppressed_issues, $new_issues);
    }

    /**
     * Adds a suppressed issue, useful when creating a method checker from scratch
     *
     */
    public function addSuppressedIssue(string $issue_name): void
    {
        $this->suppressed_issues[] = $issue_name;
    }

    public static function clearCache(): void
    {
        self::$no_effects_hashes = [];
    }

    public function getLocalReturnType(Type\Union $storage_return_type, bool $final = false): Type\Union
    {
        if ($this->local_return_type) {
            return $this->local_return_type;
        }

        $this->local_return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
            $this->codebase,
            $storage_return_type,
            $this->getFQCLN(),
            $this->getFQCLN(),
            $this->getParentFQCLN(),
            true,
            true,
            $final
        );

        return $this->local_return_type;
    }
}
