<?php
namespace Psalm\Internal\Analyzer\FunctionLike;

use PhpParser;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\InterfaceAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\SourceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FunctionDocblockManipulator;
use Psalm\Issue\InvalidFalsableReturnType;
use Psalm\Issue\InvalidNullableReturnType;
use Psalm\Issue\InvalidParent;
use Psalm\Issue\InvalidReturnType;
use Psalm\Issue\InvalidToString;
use Psalm\Issue\LessSpecificReturnType;
use Psalm\Issue\MismatchingDocblockReturnType;
use Psalm\Issue\MissingClosureReturnType;
use Psalm\Issue\MissingReturnType;
use Psalm\Issue\MixedInferredReturnType;
use Psalm\Issue\MixedReturnTypeCoercion;
use Psalm\Issue\MoreSpecificReturnType;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use function strtolower;
use function substr;
use function count;
use function in_array;

/**
 * @internal
 */
class ReturnTypeAnalyzer
{
    /**
     * @param Closure|Function_|ClassMethod|ArrowFunction $function
     * @param PhpParser\Node\Stmt[] $function_stmts
     * @param string[]            $compatible_method_ids
     *
     * @return  false|null
     */
    public static function verifyReturnType(
        FunctionLike $function,
        array $function_stmts,
        SourceAnalyzer $source,
        \Psalm\Internal\Provider\NodeDataProvider $type_provider,
        FunctionLikeAnalyzer $function_like_analyzer,
        ?Type\Union $return_type = null,
        ?string $fq_class_name = null,
        ?CodeLocation $return_type_location = null,
        array $compatible_method_ids = [],
        bool $did_explicitly_return = false,
        bool $closure_inside_call = false
    ): ?bool {
        $suppressed_issues = $function_like_analyzer->getSuppressedIssues();
        $codebase = $source->getCodebase();
        $project_analyzer = $source->getProjectAnalyzer();

        $function_like_storage = null;

        if ($source instanceof StatementsAnalyzer) {
            $function_like_storage = $function_like_analyzer->getFunctionLikeStorage($source);
        } elseif ($source instanceof \Psalm\Internal\Analyzer\ClassAnalyzer
            || $source instanceof \Psalm\Internal\Analyzer\TraitAnalyzer
        ) {
            $function_like_storage = $function_like_analyzer->getFunctionLikeStorage();
        }

        $cased_method_id = $function_like_analyzer->getCorrectlyCasedMethodId();

        if (!$function->getStmts() &&
            (
                $function instanceof ClassMethod &&
                ($source instanceof InterfaceAnalyzer || $function->isAbstract())
            )
        ) {
            if (!$return_type) {
                if (IssueBuffer::accepts(
                    new MissingReturnType(
                        'Method ' . $cased_method_id . ' does not have a return type',
                        new CodeLocation($function_like_analyzer, $function->name, null, true)
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            }

            return null;
        }

        $is_to_string = $function instanceof ClassMethod && strtolower($function->name->name) === '__tostring';

        if ($function instanceof ClassMethod
            && substr($function->name->name, 0, 2) === '__'
            && !$is_to_string
            && !$return_type
        ) {
            // do not check __construct, __set, __get, __call etc.
            return null;
        }

        if (!$return_type_location) {
            $return_type_location = new CodeLocation(
                $function_like_analyzer,
                $function instanceof Closure || $function instanceof ArrowFunction ? $function : $function->name
            );
        }

        $inferred_yield_types = [];

        $inferred_return_type_parts = ReturnTypeCollector::getReturnTypes(
            $codebase,
            $type_provider,
            $function_stmts,
            $inferred_yield_types,
            true
        );

        if (!$inferred_return_type_parts) {
            $did_explicitly_return = true;
        }

        if ((!$return_type || $return_type->from_docblock)
            && ScopeAnalyzer::getControlActions(
                $function_stmts,
                $type_provider,
                $codebase->config->exit_functions
            ) !== [ScopeAnalyzer::ACTION_END]
            && !$inferred_yield_types
            && count($inferred_return_type_parts)
            && !$did_explicitly_return
        ) {
            // only add null if we have a return statement elsewhere and it wasn't void
            foreach ($inferred_return_type_parts as $inferred_return_type_part) {
                if (!$inferred_return_type_part->isVoid()) {
                    $atomic_null = new Type\Atomic\TNull();
                    $atomic_null->from_docblock = true;
                    $inferred_return_type_parts[] = new Type\Union([$atomic_null]);
                    break;
                }
            }
        }

        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        if ($return_type
            && (!$return_type->from_docblock
                || ($return_type->isNullable()
                    && !$return_type->hasTemplate()
                    && !$return_type->getAtomicTypes()['null']->from_docblock
                )
            )
            && !$return_type->isVoid()
            && !$inferred_yield_types
            && (!$function_like_storage || !$function_like_storage->has_yield)
            && ScopeAnalyzer::getControlActions(
                $function_stmts,
                $type_provider,
                $codebase->config->exit_functions
            ) !== [ScopeAnalyzer::ACTION_END]
        ) {
            if (IssueBuffer::accepts(
                new InvalidReturnType(
                    'Not all code paths of ' . $cased_method_id . ' end in a return statement, return type '
                        . $return_type . ' expected',
                    $return_type_location
                ),
                $suppressed_issues
            )) {
                return false;
            }

            return null;
        }

        if ($return_type
            && $return_type->isNever()
            && !$inferred_yield_types
            && ScopeAnalyzer::getControlActions(
                $function_stmts,
                $type_provider,
                $codebase->config->exit_functions,
                [],
                false
            ) !== [ScopeAnalyzer::ACTION_END]
        ) {
            if (IssueBuffer::accepts(
                new InvalidReturnType(
                    $cased_method_id . ' is not expected to return any values but it does, '
                        . 'either implicitly or explicitly',
                    $return_type_location
                ),
                $suppressed_issues
            )) {
                return false;
            }

            return null;
        }

        $inferred_return_type = $inferred_return_type_parts
            ? \Psalm\Type::combineUnionTypeArray($inferred_return_type_parts, $codebase)
            : Type::getVoid();
        $inferred_yield_type = $inferred_yield_types
            ? \Psalm\Type::combineUnionTypeArray($inferred_yield_types, $codebase)
            : null;

        if ($inferred_yield_type) {
            $inferred_return_type = $inferred_yield_type;
        }

        $unsafe_return_type = false;

        // prevent any return types that do not return a value from being used in PHP typehints
        if ($codebase->alter_code
            && $inferred_return_type->isNullable()
            && !$inferred_yield_types
        ) {
            foreach ($inferred_return_type_parts as $inferred_return_type_part) {
                if ($inferred_return_type_part->isVoid()) {
                    $unsafe_return_type = true;
                    break;
                }
            }
        }

        $inferred_return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
            $codebase,
            $inferred_return_type,
            $source->getFQCLN(),
            $source->getFQCLN(),
            $source->getParentFQCLN()
        );

        // hack until we have proper yield type collection
        if ($function_like_storage
            && $function_like_storage->has_yield
            && !$inferred_yield_type
            && !$inferred_return_type->isVoid()
        ) {
            $inferred_return_type = new Type\Union([new Type\Atomic\TNamedObject('Generator')]);
        }

        if ($is_to_string) {
            if (!$inferred_return_type->hasMixed() &&
                !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $inferred_return_type,
                    Type::getString(),
                    $inferred_return_type->ignore_nullable_issues,
                    $inferred_return_type->ignore_falsable_issues
                )
            ) {
                if (IssueBuffer::accepts(
                    new InvalidToString(
                        '__toString methods must return a string, ' . $inferred_return_type . ' returned',
                        $return_type_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            }

            return null;
        }

        if (!$return_type) {
            if ($function instanceof Closure || $function instanceof ArrowFunction) {
                if (!$closure_inside_call || $inferred_return_type->isMixed()) {
                    if ($codebase->alter_code
                        && isset($project_analyzer->getIssuesToFix()['MissingClosureReturnType'])
                        && !in_array('MissingClosureReturnType', $suppressed_issues)
                    ) {
                        if ($inferred_return_type->hasMixed() || $inferred_return_type->isNull()) {
                            return null;
                        }

                        self::addOrUpdateReturnType(
                            $function,
                            $project_analyzer,
                            $inferred_return_type,
                            $source,
                            ($project_analyzer->only_replace_php_types_with_non_docblock_types
                                    || $unsafe_return_type)
                                && $inferred_return_type->from_docblock,
                            $function_like_storage
                        );

                        return null;
                    }

                    if (IssueBuffer::accepts(
                        new MissingClosureReturnType(
                            'Closure does not have a return type, expecting ' . $inferred_return_type->getId(),
                            new CodeLocation($function_like_analyzer, $function, null, true)
                        ),
                        $suppressed_issues,
                        !$inferred_return_type->hasMixed() && !$inferred_return_type->isNull()
                    )) {
                        // fall through
                    }
                }

                return null;
            }

            if ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['MissingReturnType'])
                && !in_array('MissingReturnType', $suppressed_issues)
            ) {
                if ($inferred_return_type->hasMixed() || $inferred_return_type->isNull()) {
                    return null;
                }

                self::addOrUpdateReturnType(
                    $function,
                    $project_analyzer,
                    $inferred_return_type,
                    $source,
                    $compatible_method_ids
                        || !$did_explicitly_return
                        || (($project_analyzer->only_replace_php_types_with_non_docblock_types
                                || $unsafe_return_type)
                            && $inferred_return_type->from_docblock),
                    $function_like_storage
                );

                return null;
            }

            if (IssueBuffer::accepts(
                new MissingReturnType(
                    'Method ' . $cased_method_id . ' does not have a return type' .
                      (!$inferred_return_type->hasMixed() ? ', expecting ' . $inferred_return_type->getId() : ''),
                    new CodeLocation($function_like_analyzer, $function->name, null, true)
                ),
                $suppressed_issues,
                !$inferred_return_type->hasMixed() && !$inferred_return_type->isNull()
            )) {
                // fall through
            }

            return null;
        }

        $self_fq_class_name = $fq_class_name ?: $source->getFQCLN();

        $parent_class = null;

        if ($self_fq_class_name) {
            $classlike_storage = $codebase->classlike_storage_provider->get($self_fq_class_name);
            $parent_class = $classlike_storage->parent_class;
        }

        // passing it through fleshOutTypes eradicates errant $ vars
        $declared_return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
            $codebase,
            $return_type,
            $self_fq_class_name,
            $self_fq_class_name,
            $parent_class,
            true,
            true,
            $function_like_storage instanceof MethodStorage && $function_like_storage->final
        );

        if (!$inferred_return_type_parts
            && !$inferred_yield_types
            && (!$function_like_storage || !$function_like_storage->has_yield)
        ) {
            if ($declared_return_type->isVoid() || $declared_return_type->isNever()) {
                return null;
            }

            if (ScopeAnalyzer::onlyThrowsOrExits($type_provider, $function_stmts)) {
                // if there's a single throw statement, it's presumably an exception saying this method is not to be
                // used
                return null;
            }

            if ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['InvalidReturnType'])
                && !in_array('InvalidReturnType', $suppressed_issues)
            ) {
                self::addOrUpdateReturnType(
                    $function,
                    $project_analyzer,
                    Type::getVoid(),
                    $source,
                    $compatible_method_ids
                        || (($project_analyzer->only_replace_php_types_with_non_docblock_types
                                || $unsafe_return_type)
                            && $inferred_return_type->from_docblock)
                );

                return null;
            }

            if (!$declared_return_type->from_docblock || !$declared_return_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new InvalidReturnType(
                        'No return statements were found for method ' . $cased_method_id .
                            ' but return type \'' . $declared_return_type . '\' was expected',
                        $return_type_location
                    ),
                    $suppressed_issues,
                    true
                )) {
                    return false;
                }
            }

            return null;
        }

        if (!$declared_return_type->hasMixed()) {
            if ($inferred_return_type->isVoid()
                && ($declared_return_type->isVoid() || ($function_like_storage && $function_like_storage->has_yield))
            ) {
                return null;
            }

            if ($inferred_return_type->hasMixed() || $inferred_return_type->isEmpty()) {
                if (IssueBuffer::accepts(
                    new MixedInferredReturnType(
                        'Could not verify return type \'' . $declared_return_type . '\' for ' .
                            $cased_method_id,
                        $return_type_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }

                return null;
            }

            $union_comparison_results = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

            if (!UnionTypeComparator::isContainedBy(
                $codebase,
                $inferred_return_type,
                $declared_return_type,
                true,
                true,
                $union_comparison_results
            )) {
                // is the declared return type more specific than the inferred one?
                if ($union_comparison_results->type_coerced) {
                    if ($union_comparison_results->type_coerced_from_mixed) {
                        if (!$union_comparison_results->type_coerced_from_as_mixed) {
                            if (IssueBuffer::accepts(
                                new MixedReturnTypeCoercion(
                                    'The declared return type \'' . $declared_return_type->getId() . '\' for '
                                        . $cased_method_id . ' is more specific than the inferred return type '
                                        . '\'' . $inferred_return_type->getId() . '\'',
                                    $return_type_location
                                ),
                                $suppressed_issues
                            )) {
                                return false;
                            }
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new MoreSpecificReturnType(
                                'The declared return type \'' . $declared_return_type->getId() . '\' for '
                                    . $cased_method_id . ' is more specific than the inferred return type '
                                    . '\'' . $inferred_return_type->getId() . '\'',
                                $return_type_location
                            ),
                            $suppressed_issues
                        )) {
                            return false;
                        }
                    }
                } else {
                    if ($codebase->alter_code
                        && isset($project_analyzer->getIssuesToFix()['InvalidReturnType'])
                        && !in_array('InvalidReturnType', $suppressed_issues)
                    ) {
                        self::addOrUpdateReturnType(
                            $function,
                            $project_analyzer,
                            $inferred_return_type,
                            $source,
                            ($project_analyzer->only_replace_php_types_with_non_docblock_types
                                    || $unsafe_return_type)
                                && $inferred_return_type->from_docblock,
                            $function_like_storage
                        );

                        return null;
                    }

                    if (IssueBuffer::accepts(
                        new InvalidReturnType(
                            'The declared return type \''
                                . $declared_return_type->getId()
                                . '\' for ' . $cased_method_id
                                . ' is incorrect, got \''
                                . $inferred_return_type->getId() . '\'',
                            $return_type_location
                        ),
                        $suppressed_issues,
                        true
                    )) {
                        return false;
                    }
                }
            } elseif ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['LessSpecificReturnType'])
                && !in_array('LessSpecificReturnType', $suppressed_issues)
                && !($function_like_storage instanceof MethodStorage && $function_like_storage->inheritdoc)
            ) {
                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $declared_return_type,
                    $inferred_return_type,
                    false,
                    false
                )) {
                    self::addOrUpdateReturnType(
                        $function,
                        $project_analyzer,
                        $inferred_return_type,
                        $source,
                        $compatible_method_ids
                            || (($project_analyzer->only_replace_php_types_with_non_docblock_types
                                    || $unsafe_return_type)
                                && $inferred_return_type->from_docblock),
                        $function_like_storage
                    );

                    return null;
                }
            } elseif (!$inferred_return_type->hasMixed()
                && ((!$inferred_return_type->isNullable() && $declared_return_type->isNullable())
                    || (!$inferred_return_type->isFalsable() && $declared_return_type->isFalsable()))
            ) {
                if ($function instanceof Function_
                    || $function instanceof Closure
                    || $function instanceof ArrowFunction
                    || $function->isPrivate()
                ) {
                    $check_for_less_specific_type = true;
                } elseif ($source instanceof StatementsAnalyzer) {
                    if ($function_like_storage instanceof MethodStorage) {
                        $check_for_less_specific_type = !$function_like_storage->overridden_somewhere;
                    } else {
                        $check_for_less_specific_type = false;
                    }
                } else {
                    $check_for_less_specific_type = false;
                }

                if ($check_for_less_specific_type) {
                    if (IssueBuffer::accepts(
                        new LessSpecificReturnType(
                            'The inferred return type \'' . $inferred_return_type . '\' for ' . $cased_method_id .
                                ' is more specific than the declared return type \'' . $declared_return_type . '\'',
                            $return_type_location
                        ),
                        $suppressed_issues,
                        !($function_like_storage instanceof MethodStorage && $function_like_storage->inheritdoc)
                    )) {
                        return false;
                    }
                }
            }

            if (!$inferred_return_type->ignore_nullable_issues
                && $inferred_return_type->isNullable()
                && !$declared_return_type->isNullable()
                && !$declared_return_type->hasTemplate()
                && !$declared_return_type->isVoid()
            ) {
                if ($codebase->alter_code
                    && isset($project_analyzer->getIssuesToFix()['InvalidNullableReturnType'])
                    && !in_array('InvalidNullableReturnType', $suppressed_issues)
                    && !$inferred_return_type->isNull()
                ) {
                    self::addOrUpdateReturnType(
                        $function,
                        $project_analyzer,
                        $inferred_return_type,
                        $source,
                        ($project_analyzer->only_replace_php_types_with_non_docblock_types
                                || $unsafe_return_type)
                            && $inferred_return_type->from_docblock,
                        $function_like_storage
                    );

                    return null;
                }

                if (IssueBuffer::accepts(
                    new InvalidNullableReturnType(
                        'The declared return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                            ' is not nullable, but \'' . $inferred_return_type . '\' contains null',
                        $return_type_location
                    ),
                    $suppressed_issues,
                    !$inferred_return_type->isNull()
                )) {
                    return false;
                }
            }

            if (!$inferred_return_type->ignore_falsable_issues
                && $inferred_return_type->isFalsable()
                && !$declared_return_type->isFalsable()
                && !$declared_return_type->hasBool()
                && !$declared_return_type->hasScalar()
            ) {
                if ($codebase->alter_code
                    && isset($project_analyzer->getIssuesToFix()['InvalidFalsableReturnType'])
                ) {
                    self::addOrUpdateReturnType(
                        $function,
                        $project_analyzer,
                        $inferred_return_type,
                        $source,
                        ($project_analyzer->only_replace_php_types_with_non_docblock_types
                                || $unsafe_return_type)
                            && $inferred_return_type->from_docblock,
                        $function_like_storage
                    );

                    return null;
                }

                if (IssueBuffer::accepts(
                    new InvalidFalsableReturnType(
                        'The declared return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                            ' does not allow false, but \'' . $inferred_return_type . '\' contains false',
                        $return_type_location
                    ),
                    $suppressed_issues,
                    true
                )) {
                    return false;
                }
            }
        }

        return null;
    }

    /**
     * @param Closure|Function_|ClassMethod|ArrowFunction $function
     *
     * @return false|null
     */
    public static function checkReturnType(
        FunctionLike $function,
        ProjectAnalyzer $project_analyzer,
        FunctionLikeAnalyzer $function_like_analyzer,
        FunctionLikeStorage $storage,
        Context $context
    ): ?bool {
        $codebase = $project_analyzer->getCodebase();

        if (!$storage->return_type || !$storage->return_type_location) {
            return null;
        }

        $parent_class = null;

        $classlike_storage = null;

        if ($context->self) {
            $classlike_storage = $codebase->classlike_storage_provider->get($context->self);
            $parent_class = $classlike_storage->parent_class;
        }

        if (!$storage->signature_return_type || $storage->signature_return_type === $storage->return_type) {
            foreach ($storage->return_type->getAtomicTypes() as $type) {
                if ($type instanceof Type\Atomic\TNamedObject
                    && 'parent' === $type->value
                    && null === $parent_class
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidParent(
                            'Cannot use parent as a return type when class has no parent',
                            $storage->return_type_location
                        ),
                        $storage->suppressed_issues
                    )) {
                        return false;
                    }
                    return null;
                }
            }

            $fleshed_out_return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $storage->return_type,
                $classlike_storage ? $classlike_storage->name : null,
                $classlike_storage ? $classlike_storage->name : null,
                $parent_class
            );

            $fleshed_out_return_type->check(
                $function_like_analyzer,
                $storage->return_type_location,
                $storage->suppressed_issues,
                [],
                false
            );

            return null;
        }

        $fleshed_out_signature_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
            $codebase,
            $storage->signature_return_type,
            $classlike_storage ? $classlike_storage->name : null,
            $classlike_storage ? $classlike_storage->name : null,
            $parent_class
        );

        if ($fleshed_out_signature_type->check(
            $function_like_analyzer,
            $storage->signature_return_type_location ?: $storage->return_type_location,
            $storage->suppressed_issues,
            [],
            false
        ) === false) {
            return false;
        }

        if ($function instanceof Closure || $function instanceof ArrowFunction) {
            return null;
        }

        $fleshed_out_return_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
            $codebase,
            $storage->return_type,
            $classlike_storage ? $classlike_storage->name : null,
            $classlike_storage ? $classlike_storage->name : null,
            $parent_class,
            true,
            true
        );

        if ($fleshed_out_return_type->check(
            $function_like_analyzer,
            $storage->return_type_location,
            $storage->suppressed_issues,
            [],
            false,
            $storage instanceof MethodStorage && $storage->inherited_return_type
        ) === false) {
            return false;
        }

        if ($classlike_storage && $context->self) {
            $class_template_params = ClassTemplateParamCollector::collect(
                $codebase,
                $classlike_storage,
                $codebase->classlike_storage_provider->get($context->self),
                strtolower($function->name->name),
                new Type\Atomic\TNamedObject($context->self),
                '$this'
            );

            $class_template_params = $class_template_params ?: [];

            if ($class_template_params) {
                $template_result = new \Psalm\Internal\Type\TemplateResult(
                    $class_template_params,
                    []
                );

                $fleshed_out_return_type = \Psalm\Internal\Type\UnionTemplateHandler::replaceTemplateTypesWithStandins(
                    $fleshed_out_return_type,
                    $template_result,
                    $codebase,
                    null,
                    null,
                    null
                );
            }
        }

        if (!UnionTypeComparator::isContainedBy(
            $codebase,
            $fleshed_out_return_type,
            $fleshed_out_signature_type
        )
        ) {
            if ($codebase->alter_code
                && isset($project_analyzer->getIssuesToFix()['MismatchingDocblockReturnType'])
            ) {
                self::addOrUpdateReturnType(
                    $function,
                    $project_analyzer,
                    $storage->signature_return_type,
                    $function_like_analyzer->getSource()
                );

                return null;
            }

            if (IssueBuffer::accepts(
                new MismatchingDocblockReturnType(
                    'Docblock has incorrect return type \'' . $storage->return_type->getId() .
                        '\', should be \'' . $storage->signature_return_type->getId() . '\'',
                    $storage->return_type_location
                ),
                $storage->suppressed_issues,
                true
            )) {
                return false;
            }
        }

        return null;
    }

    /**
     * @param Closure|Function_|ClassMethod|ArrowFunction $function
     *
     */
    private static function addOrUpdateReturnType(
        FunctionLike $function,
        ProjectAnalyzer $project_analyzer,
        Type\Union $inferred_return_type,
        StatementsSource $source,
        bool $docblock_only = false,
        ?FunctionLikeStorage $function_like_storage = null
    ): void {
        $manipulator = FunctionDocblockManipulator::getForFunction(
            $project_analyzer,
            $source->getFilePath(),
            $function
        );

        $codebase = $project_analyzer->getCodebase();
        $is_final = true;
        $fqcln = $source->getFQCLN();

        if ($fqcln !== null && $function instanceof ClassMethod) {
            $class_storage = $codebase->classlike_storage_provider->get($fqcln);
            $is_final = $function->isFinal() || $class_storage->final;
        }

        $allow_native_type = !$docblock_only
            && $codebase->php_major_version >= 7
            && (
                $codebase->allow_backwards_incompatible_changes
                || $is_final
                || !$function instanceof PhpParser\Node\Stmt\ClassMethod
            );

        $manipulator->setReturnType(
            $allow_native_type
                ? (string) $inferred_return_type->toPhpString(
                    $source->getNamespace(),
                    $source->getAliasedClassesFlipped(),
                    $source->getFQCLN(),
                    $codebase->php_major_version,
                    $codebase->php_minor_version
                ) : null,
            $inferred_return_type->toNamespacedString(
                $source->getNamespace(),
                $source->getAliasedClassesFlipped(),
                $source->getFQCLN(),
                false
            ),
            $inferred_return_type->toNamespacedString(
                $source->getNamespace(),
                $source->getAliasedClassesFlipped(),
                $source->getFQCLN(),
                true
            ),
            $inferred_return_type->canBeFullyExpressedInPhp(),
            $function_like_storage ? $function_like_storage->return_type_description : null
        );
    }
}
