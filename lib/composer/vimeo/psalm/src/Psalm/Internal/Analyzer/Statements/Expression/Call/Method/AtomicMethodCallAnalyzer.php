<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentMapPopulator;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\MixedPropertyTypeCoercion;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
use Psalm\Issue\PropertyTypeCoercion;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function array_values;
use function array_shift;
use function get_class;
use function strtolower;
use function array_map;
use function array_merge;
use function explode;
use function in_array;
use function count;

class AtomicMethodCallAnalyzer extends CallAnalyzer
{
    /**
     * @param  Type\Atomic\TNamedObject|Type\Atomic\TTemplateParam  $static_type
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Codebase $codebase,
        Context $context,
        Type\Atomic $lhs_type_part,
        ?Type\Atomic $static_type,
        bool $is_intersection,
        ?string $lhs_var_id,
        AtomicMethodCallAnalysisResult $result
    ) : void {
        $config = $codebase->config;

        if ($lhs_type_part instanceof Type\Atomic\TTemplateParam
            && !$lhs_type_part->as->isMixed()
        ) {
            $extra_types = $lhs_type_part->extra_types;

            $lhs_type_part = array_values(
                $lhs_type_part->as->getAtomicTypes()
            )[0];

            $lhs_type_part->from_docblock = true;

            if ($lhs_type_part instanceof TNamedObject) {
                $lhs_type_part->extra_types = $extra_types;
            } elseif ($lhs_type_part instanceof Type\Atomic\TObject && $extra_types) {
                $lhs_type_part = array_shift($extra_types);
                if ($extra_types) {
                    $lhs_type_part->extra_types = $extra_types;
                }
            }

            $result->has_mixed_method_call = true;
        }

        $source = $statements_analyzer->getSource();

        if (!$lhs_type_part instanceof TNamedObject) {
            self::handleInvalidClass(
                $statements_analyzer,
                $codebase,
                $stmt,
                $lhs_type_part,
                $lhs_var_id,
                $context,
                $is_intersection,
                $result
            );

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

        $result->has_valid_method_call_type = true;

        $fq_class_name = $lhs_type_part->value;

        $is_mock = ExpressionAnalyzer::isMock($fq_class_name);

        $result->has_mock = $result->has_mock || $is_mock;

        if ($fq_class_name === 'static') {
            $fq_class_name = (string) $context->self;
        }

        if ($is_mock ||
            $context->isPhantomClass($fq_class_name)
        ) {
            $result->return_type = Type::getMixed();

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                true,
                $context
            );

            return;
        }

        if ($lhs_var_id === '$this') {
            $does_class_exist = true;
        } else {
            $does_class_exist = ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($source, $stmt->var),
                $context->self,
                $context->calling_method_id,
                $statements_analyzer->getSuppressedIssues(),
                true,
                false,
                true,
                $lhs_type_part->from_docblock
            );
        }

        if (!$does_class_exist) {
            return;
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $result->check_visibility = $result->check_visibility && !$class_storage->override_method_visibility;

        $intersection_types = $lhs_type_part->getIntersectionTypes();

        $all_intersection_return_type = null;
        $all_intersection_existent_method_ids = [];

        if ($intersection_types) {
            foreach ($intersection_types as $intersection_type) {
                $intersection_result = clone $result;

                /** @var ?Type\Union */
                $intersection_result->return_type = null;

                self::analyze(
                    $statements_analyzer,
                    $stmt,
                    $codebase,
                    $context,
                    $intersection_type,
                    $lhs_type_part,
                    true,
                    $lhs_var_id,
                    $intersection_result
                );

                $result->returns_by_ref = $intersection_result->returns_by_ref;
                $result->has_mock = $intersection_result->has_mock;
                $result->has_valid_method_call_type = $intersection_result->has_valid_method_call_type;
                $result->has_mixed_method_call = $intersection_result->has_mixed_method_call;
                $result->invalid_method_call_types = $intersection_result->invalid_method_call_types;
                $result->check_visibility = $intersection_result->check_visibility;
                $result->too_many_arguments = $intersection_result->too_many_arguments;

                $all_intersection_existent_method_ids = array_merge(
                    $all_intersection_existent_method_ids,
                    $intersection_result->existent_method_ids
                );

                if ($intersection_result->return_type) {
                    if (!$all_intersection_return_type || $all_intersection_return_type->isMixed()) {
                        $all_intersection_return_type = $intersection_result->return_type;
                    } else {
                        $all_intersection_return_type = Type::intersectUnionTypes(
                            $all_intersection_return_type,
                            $intersection_result->return_type,
                            $codebase
                        ) ?: Type::getMixed();
                    }
                }
            }
        }

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            if (!$context->ignore_variable_method) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($fq_class_name) . '::',
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                true,
                $context
            );

            $result->return_type = Type::getMixed();
            return;
        }

        $method_name_lc = strtolower($stmt->name->name);

        $method_id = new MethodIdentifier($fq_class_name, $method_name_lc);
        $cased_method_id = $fq_class_name . '::' . $stmt->name->name;

        $intersection_method_id = $intersection_types
            ? '(' . $lhs_type_part . ')'  . '::' . $stmt->name->name
            : null;

        $args = $stmt->args;

        $old_node_data = null;

        $naive_method_id = $method_id;

        $naive_method_exists = $codebase->methods->methodExists(
            $method_id,
            $context->calling_method_id,
            $codebase->collect_locations
                ? new CodeLocation($source, $stmt->name)
                : null,
            !$context->collect_initializations
                && !$context->collect_mutations
                ? $statements_analyzer
                : null,
            $statements_analyzer->getFilePath(),
            false
        );

        if ($naive_method_exists && $fq_class_name === 'Closure' && $method_name_lc === '__invoke') {
            $old_node_data = $statements_analyzer->node_data;
            $statements_analyzer->node_data = clone $statements_analyzer->node_data;

            $fake_function_call = new PhpParser\Node\Expr\FuncCall(
                $stmt->var,
                $stmt->args,
                $stmt->getAttributes()
            );

            FunctionCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_function_call,
                $context
            );

            $function_return = $statements_analyzer->node_data->getType($fake_function_call) ?: Type::getMixed();
            $statements_analyzer->node_data = $old_node_data;

            if (!$result->return_type) {
                $result->return_type = $function_return;
            } else {
                $result->return_type = Type::combineUnionTypes($function_return, $result->return_type);
            }

            return;
        }

        $fake_method_exists = false;

        if (!$naive_method_exists
            && $codebase->methods->existence_provider->has($fq_class_name)
        ) {
            $method_exists = $codebase->methods->existence_provider->doesMethodExist(
                $fq_class_name,
                $method_id->method_name,
                $source,
                null
            );

            if ($method_exists) {
                $fake_method_exists = true;
            }
        }

        if (!$naive_method_exists) {
            [$lhs_type_part, $class_storage, $naive_method_exists, $method_id, $fq_class_name]
                = self::handleMixins(
                    $class_storage,
                    $lhs_type_part,
                    $method_name_lc,
                    $codebase,
                    $context,
                    $method_id,
                    $source,
                    $stmt,
                    $statements_analyzer,
                    $fq_class_name,
                    $lhs_var_id
                );
        }

        if (($fake_method_exists
                && $codebase->methods->methodExists(new MethodIdentifier($fq_class_name, '__call')))
            || !$naive_method_exists
            || !MethodAnalyzer::isMethodVisible(
                $method_id,
                $context,
                $statements_analyzer->getSource()
            )
        ) {
            $interface_has_method = false;

            if ($class_storage->abstract && $class_storage->class_implements) {
                foreach ($class_storage->class_implements as $interface_fqcln_lc => $_) {
                    $interface_storage = $codebase->classlike_storage_provider->get($interface_fqcln_lc);

                    if (isset($interface_storage->methods[$method_name_lc])) {
                        $interface_has_method = true;
                        $fq_class_name = $interface_storage->name;
                        $method_id = new MethodIdentifier(
                            $fq_class_name,
                            $method_name_lc
                        );
                        break;
                    }
                }
            }

            if (!$interface_has_method
                && $codebase->methods->methodExists(
                    new MethodIdentifier($fq_class_name, '__call'),
                    $context->calling_method_id,
                    $codebase->collect_locations
                        ? new CodeLocation($source, $stmt->name)
                        : null,
                    !$context->collect_initializations
                        && !$context->collect_mutations
                        ? $statements_analyzer
                        : null,
                    $statements_analyzer->getFilePath()
                )
            ) {
                $new_call_context = MissingMethodCallHandler::handleMagicMethod(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $method_id,
                    $class_storage,
                    $context,
                    $config,
                    $all_intersection_return_type,
                    $result
                );

                if ($new_call_context) {
                    if ($method_id === $new_call_context->method_id) {
                        return;
                    }

                    $method_id = $new_call_context->method_id;
                    $args = $new_call_context->args;
                    $old_node_data = $statements_analyzer->node_data;
                } else {
                    return;
                }
            }
        }

        $source_source = $statements_analyzer->getSource();

        /**
         * @var \Psalm\Internal\Analyzer\ClassLikeAnalyzer|null
         */
        $classlike_source = $source_source->getSource();
        $classlike_source_fqcln = $classlike_source ? $classlike_source->getFQCLN() : null;

        if ($lhs_var_id === '$this'
            && $context->self
            && $classlike_source_fqcln
            && $fq_class_name !== $context->self
            && $codebase->methods->methodExists(
                new MethodIdentifier($context->self, $method_name_lc)
            )
        ) {
            $method_id = new MethodIdentifier($context->self, $method_name_lc);
            $cased_method_id = $context->self . '::' . $stmt->name->name;
            $fq_class_name = $context->self;
        }

        $is_interface = false;

        if ($codebase->interfaceExists($fq_class_name)) {
            $is_interface = true;
        }

        $source_method_id = $source instanceof FunctionLikeAnalyzer
            ? $source->getId()
            : null;

        $corrected_method_exists = ($naive_method_exists && $method_id === $naive_method_id)
            || ($method_id !== $naive_method_id
                && $codebase->methods->methodExists(
                    $method_id,
                    $context->calling_method_id,
                    $codebase->collect_locations && $method_id !== $source_method_id
                        ? new CodeLocation($source, $stmt->name)
                        : null
                ));

        if (!$corrected_method_exists
            || ($config->use_phpdoc_method_without_magic_or_parent
                && isset($class_storage->pseudo_methods[$method_name_lc]))
        ) {
            MissingMethodCallHandler::handleMissingOrMagicMethod(
                $statements_analyzer,
                $codebase,
                $stmt,
                $method_id,
                $is_interface,
                $context,
                $config,
                $all_intersection_return_type,
                $result
            );

            if ($all_intersection_return_type && $all_intersection_existent_method_ids) {
                $result->existent_method_ids = array_merge(
                    $result->existent_method_ids,
                    $all_intersection_existent_method_ids
                );

                if (!$result->return_type) {
                    $result->return_type = $all_intersection_return_type;
                } else {
                    $result->return_type = Type::combineUnionTypes($all_intersection_return_type, $result->return_type);
                }

                return;
            }

            if ((!$is_interface && !$config->use_phpdoc_method_without_magic_or_parent)
                || !isset($class_storage->pseudo_methods[$method_name_lc])
            ) {
                if ($is_interface) {
                    $result->non_existent_interface_method_ids[] = $intersection_method_id ?: $cased_method_id;
                } else {
                    $result->non_existent_class_method_ids[] = $intersection_method_id ?: $cased_method_id;
                }
            }

            return;
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->analyzer->addNodeReference(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $method_id . '()'
            );
        }

        if ($context->collect_initializations && $context->calling_method_id) {
            [$calling_method_class] = explode('::', $context->calling_method_id);
            $codebase->file_reference_provider->addMethodReferenceToClassMember(
                $calling_method_class . '::__construct',
                strtolower((string) $method_id)
            );
        }

        $result->existent_method_ids[] = $method_id;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable
            && ($context->collect_initializations || $context->collect_mutations)
            && $stmt->var->name === 'this'
            && $source instanceof FunctionLikeAnalyzer
        ) {
            self::collectSpecialInformation($source, $stmt->name->name, $context);
        }

        $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $parent_source = $statements_analyzer->getSource();

        $class_template_params = ClassTemplateParamCollector::collect(
            $codebase,
            $codebase->methods->getClassLikeStorageForMethod($method_id),
            $class_storage,
            $method_name_lc,
            $lhs_type_part,
            $lhs_var_id
        );

        if ($lhs_var_id === '$this' && $parent_source instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer) {
            $grandparent_source = $parent_source->getSource();

            if ($grandparent_source instanceof \Psalm\Internal\Analyzer\TraitAnalyzer) {
                $fq_trait_name = $grandparent_source->getFQCLN();

                $fq_trait_name_lc = strtolower($fq_trait_name);

                $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name_lc);

                if (isset($trait_storage->methods[$method_name_lc])) {
                    $trait_method_id = new MethodIdentifier($trait_storage->name, $method_name_lc);

                    $class_template_params = ClassTemplateParamCollector::collect(
                        $codebase,
                        $codebase->methods->getClassLikeStorageForMethod($trait_method_id),
                        $class_storage,
                        $method_name_lc,
                        $lhs_type_part,
                        $lhs_var_id
                    );
                }
            }
        }

        $template_result = new \Psalm\Internal\Type\TemplateResult([], $class_template_params ?: []);

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            ArgumentMapPopulator::recordArgumentPositions(
                $statements_analyzer,
                $stmt,
                $codebase,
                (string) $method_id
            );
        }

        if (self::checkMethodArgs(
            $method_id,
            $args,
            $template_result,
            $context,
            new CodeLocation($source, $stmt->name),
            $statements_analyzer
        ) === false) {
            return;
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        $can_memoize = false;

        $return_type_candidate = MethodCallReturnTypeFetcher::fetch(
            $statements_analyzer,
            $codebase,
            $stmt,
            $context,
            $method_id,
            $declaring_method_id,
            $naive_method_id,
            $cased_method_id,
            $lhs_type_part,
            $static_type,
            $args,
            $result,
            $template_result
        );

        $in_call_map = InternalCallMapHandler::inCallMap((string) ($declaring_method_id ?: $method_id));

        if (!$in_call_map) {
            $name_code_location = new CodeLocation($statements_analyzer, $stmt->name);

            if ($result->check_visibility) {
                if (MethodVisibilityAnalyzer::analyze(
                    $method_id,
                    $context,
                    $statements_analyzer->getSource(),
                    $name_code_location,
                    $statements_analyzer->getSuppressedIssues()
                ) === false) {
                    self::updateResultReturnType(
                        $result,
                        $return_type_candidate,
                        $all_intersection_return_type,
                        $method_name_lc,
                        $codebase
                    );

                    return;
                }
            }

            MethodCallProhibitionAnalyzer::analyze(
                $codebase,
                $context,
                $method_id,
                $statements_analyzer->getNamespace(),
                $name_code_location,
                $statements_analyzer->getSuppressedIssues()
            );

            $getter_return_type = self::getMagicGetterOrSetterProperty(
                $statements_analyzer,
                $stmt,
                $context,
                $fq_class_name
            );

            if ($getter_return_type) {
                $return_type_candidate = $getter_return_type;
            }
        }

        try {
            $method_storage = $codebase->methods->getStorage($declaring_method_id ?: $method_id);
        } catch (\UnexpectedValueException $e) {
            $method_storage = null;
        }

        if ($method_storage) {
            if (!$context->collect_mutations && !$context->collect_initializations) {
                $can_memoize = MethodCallPurityAnalyzer::analyze(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $lhs_var_id,
                    $cased_method_id,
                    $method_id,
                    $method_storage,
                    $class_storage,
                    $context,
                    $config
                );
            }

            $has_packed_arg = false;
            foreach ($args as $arg) {
                $has_packed_arg = $has_packed_arg || $arg->unpack;
            }

            if (!$has_packed_arg) {
                $has_variadic_param = $method_storage->variadic;

                foreach ($method_storage->params as $param) {
                    $has_variadic_param = $has_variadic_param || $param->is_variadic;
                }

                for ($i = count($args), $j = count($method_storage->params); $i < $j; ++$i) {
                    $param = $method_storage->params[$i];

                    if (!$param->is_optional
                        && !$param->is_variadic
                        && !$in_call_map
                    ) {
                        $result->too_few_arguments = true;
                        $result->too_few_arguments_method_ids[] = $declaring_method_id ?: $method_id;
                    }
                }

                if ($has_variadic_param || count($method_storage->params) >= count($args) || $in_call_map) {
                    $result->too_many_arguments = false;
                } else {
                    $result->too_many_arguments_method_ids[] = $declaring_method_id ?: $method_id;
                }
            }

            $class_template_params = $template_result->upper_bounds;

            if ($method_storage->assertions) {
                self::applyAssertionsToContext(
                    $stmt->name,
                    ExpressionIdentifier::getArrayVarId($stmt->var, null, $statements_analyzer),
                    $method_storage->assertions,
                    $args,
                    $class_template_params,
                    $context,
                    $statements_analyzer
                );
            }

            if ($method_storage->if_true_assertions) {
                $statements_analyzer->node_data->setIfTrueAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use (
                            $class_template_params,
                            $lhs_var_id
                        ) : Assertion {
                            return $assertion->getUntemplatedCopy(
                                $class_template_params ?: [],
                                $lhs_var_id
                            );
                        },
                        $method_storage->if_true_assertions
                    )
                );
            }

            if ($method_storage->if_false_assertions) {
                $statements_analyzer->node_data->setIfFalseAssertions(
                    $stmt,
                    array_map(
                        function (Assertion $assertion) use (
                            $class_template_params,
                            $lhs_var_id
                        ) : Assertion {
                            return $assertion->getUntemplatedCopy(
                                $class_template_params ?: [],
                                $lhs_var_id
                            );
                        },
                        $method_storage->if_false_assertions
                    )
                );
            }
        }

        if ($old_node_data) {
            $statements_analyzer->node_data = $old_node_data;
        }

        if (!$args && $lhs_var_id) {
            if ($config->memoize_method_calls || $can_memoize) {
                $method_var_id = $lhs_var_id . '->' . $method_name_lc . '()';

                if (isset($context->vars_in_scope[$method_var_id])) {
                    $return_type_candidate = clone $context->vars_in_scope[$method_var_id];

                    if ($can_memoize) {
                        /** @psalm-suppress UndefinedPropertyAssignment */
                        $stmt->pure = true;
                    }
                } else {
                    $context->vars_in_scope[$method_var_id] = $return_type_candidate;
                }
            }
        }

        if ($codebase->methods_to_rename) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            foreach ($codebase->methods_to_rename as $original_method_id => $new_method_name) {
                if ($declaring_method_id && (strtolower((string) $declaring_method_id)) === $original_method_id) {
                    $file_manipulations = [
                        new \Psalm\FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            $new_method_name
                        )
                    ];

                    \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                        $statements_analyzer->getFilePath(),
                        $file_manipulations
                    );
                }
            }
        }

        if ($config->after_method_checks) {
            $file_manipulations = [];

            $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($appearing_method_id !== null && $declaring_method_id !== null) {
                foreach ($config->after_method_checks as $plugin_fq_class_name) {
                    $plugin_fq_class_name::afterMethodCallAnalysis(
                        $stmt,
                        (string) $method_id,
                        (string) $appearing_method_id,
                        (string) $declaring_method_id,
                        $context,
                        $statements_analyzer,
                        $codebase,
                        $file_manipulations,
                        $return_type_candidate
                    );
                }
            }

            if ($file_manipulations) {
                FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
            }
        }

        self::updateResultReturnType(
            $result,
            $return_type_candidate,
            $all_intersection_return_type,
            $method_name_lc,
            $codebase
        );
    }

    private static function updateResultReturnType(
        AtomicMethodCallAnalysisResult $result,
        ?Type\Union $return_type_candidate,
        ?Type\Union $all_intersection_return_type,
        string $method_name,
        Codebase $codebase
    ) : void {
        if ($return_type_candidate) {
            if ($all_intersection_return_type) {
                $return_type_candidate = Type::intersectUnionTypes(
                    $all_intersection_return_type,
                    $return_type_candidate,
                    $codebase
                ) ?: Type::getMixed();
            }

            if (!$result->return_type) {
                $result->return_type = $return_type_candidate;
            } else {
                $result->return_type = Type::combineUnionTypes($return_type_candidate, $result->return_type);
            }
        } elseif ($all_intersection_return_type) {
            if (!$result->return_type) {
                $result->return_type = $all_intersection_return_type;
            } else {
                $result->return_type = Type::combineUnionTypes($all_intersection_return_type, $result->return_type);
            }
        } elseif ($method_name === '__tostring') {
            $result->return_type = Type::getString();
        } else {
            $result->return_type = Type::getMixed();
        }
    }

    private static function handleInvalidClass(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        Type\Atomic $lhs_type_part,
        ?string $lhs_var_id,
        Context $context,
        bool $is_intersection,
        AtomicMethodCallAnalysisResult $result
    ) : void {
        switch (get_class($lhs_type_part)) {
            case Type\Atomic\TNull::class:
            case Type\Atomic\TFalse::class:
                // handled above
                return;

            case Type\Atomic\TTemplateParam::class:
            case Type\Atomic\TEmptyMixed::class:
            case Type\Atomic\TEmpty::class:
            case Type\Atomic\TMixed::class:
            case Type\Atomic\TNonEmptyMixed::class:
            case Type\Atomic\TObject::class:
            case Type\Atomic\TObjectWithProperties::class:
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                $result->has_mixed_method_call = true;

                if ($lhs_type_part instanceof Type\Atomic\TObjectWithProperties
                    && $stmt->name instanceof PhpParser\Node\Identifier
                    && isset($lhs_type_part->methods[$stmt->name->name])
                ) {
                    $result->existent_method_ids[] = $lhs_type_part->methods[$stmt->name->name];
                } elseif (!$is_intersection) {
                    if ($stmt->name instanceof PhpParser\Node\Identifier) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($stmt->name->name),
                            $context->calling_method_id ?: $statements_analyzer->getFileName()
                        );
                    }

                    if ($context->check_methods) {
                        $message = 'Cannot determine the type of the object'
                            . ' on the left hand side of this expression';

                        if ($lhs_var_id) {
                            $message = 'Cannot determine the type of ' . $lhs_var_id;

                            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                                $message .= ' when calling method ' . $stmt->name->name;
                            }
                        }

                        if (IssueBuffer::accepts(
                            new MixedMethodCall(
                                $message,
                                new CodeLocation($statements_analyzer, $stmt->name)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }

                if (ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->args,
                    null,
                    null,
                    true,
                    $context
                ) === false) {
                    return;
                }

                $result->return_type = Type::getMixed();
                return;

            default:
                $result->invalid_method_call_types[] = (string)$lhs_type_part;
                return;
        }
    }

    /**
     * Check properties accessed with magic getters and setters.
     * If `@psalm-seal-properties` is set, they must be defined.
     * If an `@property` annotation is specified, the setter must set something with the correct
     * type.
     */
    private static function getMagicGetterOrSetterProperty(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        string $fq_class_name
    ) : ?Type\Union {
        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            return null;
        }

        $method_name = strtolower($stmt->name->name);
        if (!in_array($method_name, ['__get', '__set'], true)) {
            return null;
        }

        $codebase = $statements_analyzer->getCodebase();

        $first_arg_value = $stmt->args[0]->value;
        if (!$first_arg_value instanceof PhpParser\Node\Scalar\String_) {
            return null;
        }

        $prop_name = $first_arg_value->value;
        $property_id = $fq_class_name . '::$' . $prop_name;

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $codebase->properties->propertyExists(
            $property_id,
            $method_name === '__get',
            $statements_analyzer,
            $context,
            new CodeLocation($statements_analyzer->getSource(), $stmt)
        );

        switch ($method_name) {
            case '__set':
                // If `@psalm-seal-properties` is set, the property must be defined with
                // a `@property` annotation
                if ($class_storage->sealed_properties
                    && !isset($class_storage->pseudo_property_set_types['$' . $prop_name])
                    && IssueBuffer::accepts(
                        new UndefinedThisPropertyAssignment(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                ) {
                    // fall through
                }

                // If a `@property` annotation is set, the type of the value passed to the
                // magic setter must match the annotation.
                $second_arg_type = $statements_analyzer->node_data->getType($stmt->args[1]->value);

                if (isset($class_storage->pseudo_property_set_types['$' . $prop_name]) && $second_arg_type) {
                    $pseudo_set_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                        $codebase,
                        $class_storage->pseudo_property_set_types['$' . $prop_name],
                        $fq_class_name,
                        new Type\Atomic\TNamedObject($fq_class_name),
                        $class_storage->parent_class
                    );

                    $union_comparison_results = new \Psalm\Internal\Type\Comparator\TypeComparisonResult();

                    $type_match_found = UnionTypeComparator::isContainedBy(
                        $codebase,
                        $second_arg_type,
                        $pseudo_set_type,
                        $second_arg_type->ignore_nullable_issues,
                        $second_arg_type->ignore_falsable_issues,
                        $union_comparison_results
                    );

                    if ($union_comparison_results->type_coerced) {
                        if ($union_comparison_results->type_coerced_from_mixed) {
                            if (IssueBuffer::accepts(
                                new MixedPropertyTypeCoercion(
                                    $prop_name . ' expects \'' . $pseudo_set_type->getId() . '\', '
                                        . ' parent type `' . $second_arg_type . '` provided',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new PropertyTypeCoercion(
                                    $prop_name . ' expects \'' . $pseudo_set_type->getId() . '\', '
                                        . ' parent type `' . $second_arg_type . '` provided',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        }
                    }

                    if (!$type_match_found && !$union_comparison_results->type_coerced_from_mixed) {
                        if (UnionTypeComparator::canBeContainedBy(
                            $codebase,
                            $second_arg_type,
                            $pseudo_set_type
                        )) {
                            if (IssueBuffer::accepts(
                                new PossiblyInvalidPropertyAssignmentValue(
                                    $prop_name . ' with declared type \''
                                    . $pseudo_set_type
                                    . '\' cannot be assigned possibly different type \'' . $second_arg_type . '\'',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new InvalidPropertyAssignmentValue(
                                    $prop_name . ' with declared type \''
                                    . $pseudo_set_type
                                    . '\' cannot be assigned type \'' . $second_arg_type . '\'',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }
                }
                break;

            case '__get':
                // If `@psalm-seal-properties` is set, the property must be defined with
                // a `@property` annotation
                if ($class_storage->sealed_properties
                    && !isset($class_storage->pseudo_property_get_types['$' . $prop_name])
                    && IssueBuffer::accepts(
                        new UndefinedThisPropertyFetch(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                ) {
                    // fall through
                }

                if (isset($class_storage->pseudo_property_get_types['$' . $prop_name])) {
                    return clone $class_storage->pseudo_property_get_types['$' . $prop_name];
                }

                break;
        }

        return null;
    }

    /**
     * @param lowercase-string $method_name_lc
     * @return array{Type\Atomic, \Psalm\Storage\ClassLikeStorage, bool, MethodIdentifier, string}
     */
    private static function handleMixins(
        \Psalm\Storage\ClassLikeStorage $class_storage,
        Type\Atomic $lhs_type_part,
        string $method_name_lc,
        Codebase $codebase,
        Context $context,
        MethodIdentifier $method_id,
        \Psalm\StatementsSource $source,
        PhpParser\Node\Expr\MethodCall $stmt,
        StatementsAnalyzer $statements_analyzer,
        string $fq_class_name,
        ?string $lhs_var_id
    ) {
        $naive_method_exists = false;

        if ($class_storage->templatedMixins
            && $lhs_type_part instanceof Type\Atomic\TGenericObject
            && $class_storage->template_types
        ) {
            $template_type_keys = \array_keys($class_storage->template_types);

            foreach ($class_storage->templatedMixins as $mixin) {
                $param_position = \array_search(
                    $mixin->param_name,
                    $template_type_keys
                );

                if ($param_position !== false
                    && isset($lhs_type_part->type_params[$param_position])
                ) {
                    $current_type_param = $lhs_type_part->type_params[$param_position];
                    if ($current_type_param->isSingle()) {
                        $lhs_type_part_new = array_values(
                            $current_type_param->getAtomicTypes()
                        )[0];

                        if ($lhs_type_part_new instanceof Type\Atomic\TNamedObject) {
                            $new_method_id = new MethodIdentifier(
                                $lhs_type_part_new->value,
                                $method_name_lc
                            );

                            $mixin_class_storage = $codebase->classlike_storage_provider->get(
                                $lhs_type_part_new->value
                            );

                            if ($codebase->methods->methodExists(
                                $new_method_id,
                                $context->calling_method_id,
                                $codebase->collect_locations
                                    ? new CodeLocation($source, $stmt->name)
                                    : null,
                                !$context->collect_initializations
                                && !$context->collect_mutations
                                    ? $statements_analyzer
                                    : null,
                                $statements_analyzer->getFilePath()
                            )) {
                                $lhs_type_part = clone $lhs_type_part_new;
                                $class_storage = $mixin_class_storage;

                                $naive_method_exists = true;
                                $method_id = $new_method_id;
                            } elseif (isset($mixin_class_storage->pseudo_methods[$method_name_lc])) {
                                $lhs_type_part = clone $lhs_type_part_new;
                                $class_storage = $mixin_class_storage;
                                $method_id = $new_method_id;
                            }
                        }
                    }
                }
            }
        } elseif ($class_storage->mixin_declaring_fqcln
            && $class_storage->namedMixins
        ) {
            foreach ($class_storage->namedMixins as $mixin) {
                if (!$class_storage->mixin_declaring_fqcln) {
                    continue;
                }

                $new_method_id = new MethodIdentifier(
                    $mixin->value,
                    $method_name_lc
                );

                if ($codebase->methods->methodExists(
                    $new_method_id,
                    $context->calling_method_id,
                    $codebase->collect_locations
                        ? new CodeLocation($source, $stmt->name)
                        : null,
                    !$context->collect_initializations
                    && !$context->collect_mutations
                        ? $statements_analyzer
                        : null,
                    $statements_analyzer->getFilePath()
                )) {
                    $mixin_declaring_class_storage = $codebase->classlike_storage_provider->get(
                        $class_storage->mixin_declaring_fqcln
                    );

                    $mixin_class_template_params = ClassTemplateParamCollector::collect(
                        $codebase,
                        $mixin_declaring_class_storage,
                        $codebase->classlike_storage_provider->get($fq_class_name),
                        null,
                        $lhs_type_part,
                        $lhs_var_id
                    );

                    $lhs_type_part = clone $mixin;

                    $lhs_type_part->replaceTemplateTypesWithArgTypes(
                        new \Psalm\Internal\Type\TemplateResult([], $mixin_class_template_params ?: []),
                        $codebase
                    );

                    $lhs_type_expanded = \Psalm\Internal\Type\TypeExpander::expandUnion(
                        $codebase,
                        new Type\Union([$lhs_type_part]),
                        $mixin_declaring_class_storage->name,
                        $fq_class_name,
                        $class_storage->parent_class,
                        true,
                        false,
                        $class_storage->final
                    );

                    $new_lhs_type_part = array_values($lhs_type_expanded->getAtomicTypes())[0];

                    if ($new_lhs_type_part instanceof Type\Atomic\TNamedObject) {
                        $lhs_type_part = $new_lhs_type_part;
                    }

                    $mixin_class_storage = $codebase->classlike_storage_provider->get($mixin->value);

                    $fq_class_name = $mixin_class_storage->name;
                    $mixin_class_storage->mixin_declaring_fqcln = $class_storage->mixin_declaring_fqcln;
                    $class_storage = $mixin_class_storage;
                    $naive_method_exists = true;
                    $method_id = $new_method_id;
                }
            }
        }

        return [
            $lhs_type_part,
            $class_storage,
            $naive_method_exists,
            $method_id,
            $fq_class_name
        ];
    }
}
