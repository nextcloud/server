<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\ClassAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\AbstractInstantiation;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InterfaceInstantiation;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\UnsafeInstantiation;
use Psalm\Issue\UndefinedClass;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function in_array;
use function strtolower;
use function implode;
use function array_values;
use function is_string;

/**
 * @internal
 */
class NewAnalyzer extends \Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\New_ $stmt,
        Context $context
    ) : bool {
        $fq_class_name = null;

        $codebase = $statements_analyzer->getCodebase();
        $config = $codebase->config;

        $can_extend = false;

        $from_static = false;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)) {
                $aliases = $statements_analyzer->getAliases();

                if ($context->calling_method_id
                    && !$stmt->class instanceof PhpParser\Node\Name\FullyQualified
                ) {
                    $codebase->file_reference_provider->addMethodReferenceToClassMember(
                        $context->calling_method_id,
                        'use:' . $stmt->class->parts[0] . ':' . \md5($statements_analyzer->getFilePath())
                    );
                }

                $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $aliases
                );

                $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);
            } elseif ($context->self !== null) {
                switch ($stmt->class->parts[0]) {
                    case 'self':
                        $class_storage = $codebase->classlike_storage_provider->get($context->self);
                        $fq_class_name = $class_storage->name;
                        break;

                    case 'parent':
                        $fq_class_name = $context->parent;
                        break;

                    case 'static':
                        // @todo maybe we can do better here
                        $class_storage = $codebase->classlike_storage_provider->get($context->self);
                        $fq_class_name = $class_storage->name;

                        if (!$class_storage->final) {
                            $can_extend = true;
                            $from_static = true;
                        }

                        break;
                }
            }

            if ($codebase->store_node_types
                && $fq_class_name
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $codebase->classlikes->classExists($fq_class_name)
                        ? $fq_class_name
                        : '*' . implode('\\', $stmt->class->parts)
                );
            }
        } elseif ($stmt->class instanceof PhpParser\Node\Stmt\Class_) {
            $statements_analyzer->analyze([$stmt->class], $context);
            $fq_class_name = ClassAnalyzer::getAnonymousClassName($stmt->class, $statements_analyzer->getFilePath());
        } else {
            $was_inside_use = $context->inside_use;
            $context->inside_use = true;
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context);
            $context->inside_use = $was_inside_use;

            if ($stmt_class_type = $statements_analyzer->node_data->getType($stmt->class)) {
                $has_single_class = $stmt_class_type->isSingleStringLiteral();

                if ($has_single_class) {
                    $fq_class_name = $stmt_class_type->getSingleStringLiteral()->value;
                } else {
                    if (self::checkMethodArgs(
                        null,
                        $stmt->args,
                        null,
                        $context,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $statements_analyzer
                    ) === false) {
                        return false;
                    }
                }

                $new_type = null;

                foreach ($stmt_class_type->getAtomicTypes() as $lhs_type_part) {
                    if ($lhs_type_part instanceof Type\Atomic\TTemplateParamClass) {
                        if (!$statements_analyzer->node_data->getType($stmt)) {
                            $new_type_part = new Type\Atomic\TTemplateParam(
                                $lhs_type_part->param_name,
                                $lhs_type_part->as_type
                                    ? new Type\Union([$lhs_type_part->as_type])
                                    : Type::getObject(),
                                $lhs_type_part->defining_class
                            );

                            if (!$lhs_type_part->as_type) {
                                if (IssueBuffer::accepts(
                                    new MixedMethodCall(
                                        'Cannot call constructor on an unknown class',
                                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }

                            if ($new_type) {
                                $new_type = Type::combineUnionTypes(
                                    $new_type,
                                    new Type\Union([$new_type_part])
                                );
                            } else {
                                $new_type = new Type\Union([$new_type_part]);
                            }

                            if ($lhs_type_part->as_type
                                && $codebase->classlikes->classExists($lhs_type_part->as_type->value)
                            ) {
                                $as_storage = $codebase->classlike_storage_provider->get(
                                    $lhs_type_part->as_type->value
                                );

                                if (!$as_storage->preserve_constructor_signature) {
                                    if (IssueBuffer::accepts(
                                        new UnsafeInstantiation(
                                            'Cannot safely instantiate class ' . $lhs_type_part->as_type->value
                                                . ' with "new $class_name" as'
                                                . ' its constructor might change in child classes',
                                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                                        ),
                                        $statements_analyzer->getSuppressedIssues()
                                    )) {
                                        // fall through
                                    }
                                }
                            }
                        }

                        if ($lhs_type_part->as_type) {
                            $codebase->methods->methodExists(
                                new \Psalm\Internal\MethodIdentifier(
                                    $lhs_type_part->as_type->value,
                                    '__construct'
                                ),
                                $context->calling_method_id,
                                $codebase->collect_locations
                                    ? new CodeLocation($statements_analyzer->getSource(), $stmt) : null,
                                $statements_analyzer,
                                $statements_analyzer->getFilePath()
                            );
                        }

                        continue;
                    }

                    if ($lhs_type_part instanceof Type\Atomic\TLiteralClassString
                        || $lhs_type_part instanceof Type\Atomic\TClassString
                        || $lhs_type_part instanceof Type\Atomic\TDependentGetClass
                    ) {
                        if (!$statements_analyzer->node_data->getType($stmt)) {
                            if ($lhs_type_part instanceof Type\Atomic\TClassString) {
                                $generated_type = $lhs_type_part->as_type
                                    ? clone $lhs_type_part->as_type
                                    : new Type\Atomic\TObject();

                                if ($lhs_type_part->as_type
                                    && $codebase->classlikes->classExists($lhs_type_part->as_type->value)
                                ) {
                                    $as_storage = $codebase->classlike_storage_provider->get(
                                        $lhs_type_part->as_type->value
                                    );

                                    if (!$as_storage->preserve_constructor_signature) {
                                        if (IssueBuffer::accepts(
                                            new UnsafeInstantiation(
                                                'Cannot safely instantiate class ' . $lhs_type_part->as_type->value
                                                    . ' with "new $class_name" as'
                                                    . ' its constructor might change in child classes',
                                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                                            ),
                                            $statements_analyzer->getSuppressedIssues()
                                        )) {
                                            // fall through
                                        }
                                    }
                                }
                            } elseif ($lhs_type_part instanceof Type\Atomic\TDependentGetClass) {
                                $generated_type = new Type\Atomic\TObject();

                                if ($lhs_type_part->as_type->hasObjectType()
                                    && $lhs_type_part->as_type->isSingle()
                                ) {
                                    foreach ($lhs_type_part->as_type->getAtomicTypes() as $typeof_type_atomic) {
                                        if ($typeof_type_atomic instanceof Type\Atomic\TNamedObject) {
                                            $generated_type = new Type\Atomic\TNamedObject(
                                                $typeof_type_atomic->value
                                            );
                                        }
                                    }
                                }
                            } else {
                                $generated_type =  new Type\Atomic\TNamedObject(
                                    $lhs_type_part->value
                                );
                            }

                            if ($lhs_type_part instanceof Type\Atomic\TClassString) {
                                $can_extend = true;
                            }

                            if ($generated_type instanceof Type\Atomic\TObject) {
                                if (IssueBuffer::accepts(
                                    new MixedMethodCall(
                                        'Cannot call constructor on an unknown class',
                                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }

                            if ($new_type) {
                                $new_type = Type::combineUnionTypes(
                                    $new_type,
                                    new Type\Union([$generated_type])
                                );
                            } else {
                                $new_type = new Type\Union([$generated_type]);
                            }
                        }

                        continue;
                    }

                    if ($lhs_type_part instanceof Type\Atomic\TString) {
                        if ($config->allow_string_standin_for_class
                            && !$lhs_type_part instanceof Type\Atomic\TNumericString
                        ) {
                            // do nothing
                        } elseif (IssueBuffer::accepts(
                            new InvalidStringClass(
                                'String cannot be used as a class',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } elseif ($lhs_type_part instanceof Type\Atomic\TMixed
                        || $lhs_type_part instanceof Type\Atomic\TTemplateParam
                    ) {
                        if (IssueBuffer::accepts(
                            new MixedMethodCall(
                                'Cannot call constructor on an unknown class',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } elseif ($lhs_type_part instanceof Type\Atomic\TFalse
                        && $stmt_class_type->ignore_falsable_issues
                    ) {
                        // do nothing
                    } elseif ($lhs_type_part instanceof Type\Atomic\TNull
                        && $stmt_class_type->ignore_nullable_issues
                    ) {
                        // do nothing
                    } elseif (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Type ' . $lhs_type_part . ' cannot be called as a class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            (string)$lhs_type_part
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    if ($new_type) {
                        $new_type = Type::combineUnionTypes(
                            $new_type,
                            Type::getObject()
                        );
                    } else {
                        $new_type = Type::getObject();
                    }
                }

                if (!$has_single_class) {
                    if ($new_type) {
                        $statements_analyzer->node_data->setType($stmt, $new_type);
                    }

                    ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $stmt->args,
                        null,
                        null,
                        true,
                        $context
                    );

                    return true;
                }
            } else {
                ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->args,
                    null,
                    null,
                    true,
                    $context
                );

                return true;
            }
        }

        if ($fq_class_name) {
            if ($codebase->alter_code
                && $stmt->class instanceof PhpParser\Node\Name
                && !in_array($stmt->class->parts[0], ['parent', 'static'])
            ) {
                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id
                );
            }

            if ($context->check_classes) {
                if ($context->isPhantomClass($fq_class_name)) {
                    ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $stmt->args,
                        null,
                        null,
                        true,
                        $context
                    );

                    return true;
                }

                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                    $context->self,
                    $context->calling_method_id,
                    $statements_analyzer->getSuppressedIssues(),
                    false
                ) === false) {
                    ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $stmt->args,
                        null,
                        null,
                        true,
                        $context
                    );

                    return true;
                }

                if ($codebase->interfaceExists($fq_class_name)) {
                    if (IssueBuffer::accepts(
                        new InterfaceInstantiation(
                            'Interface ' . $fq_class_name . ' cannot be instantiated',
                            new CodeLocation($statements_analyzer->getSource(), $stmt->class)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                    }

                    return true;
                }
            }

            if ($stmt->class instanceof PhpParser\Node\Stmt\Class_) {
                $result_atomic_type = new Type\Atomic\TAnonymousClassInstance($fq_class_name);
            } else {
                $result_atomic_type = new TNamedObject($fq_class_name);
                $result_atomic_type->was_static = $from_static;
            }

            $statements_analyzer->node_data->setType(
                $stmt,
                new Type\Union([$result_atomic_type])
            );

            if (strtolower($fq_class_name) !== 'stdclass' &&
                $codebase->classlikes->classExists($fq_class_name)
            ) {
                $storage = $codebase->classlike_storage_provider->get($fq_class_name);

                if ($from_static && !$storage->preserve_constructor_signature) {
                    if (IssueBuffer::accepts(
                        new UnsafeInstantiation(
                            'Cannot safely instantiate class ' . $fq_class_name . ' with "new static" as'
                                . ' its constructor might change in child classes',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                // if we're not calling this constructor via new static()
                if ($storage->abstract && !$can_extend) {
                    if (IssueBuffer::accepts(
                        new AbstractInstantiation(
                            'Unable to instantiate a abstract class ' . $fq_class_name,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return true;
                    }
                }

                if ($storage->deprecated && strtolower($fq_class_name) !== strtolower((string) $context->self)) {
                    if (IssueBuffer::accepts(
                        new DeprecatedClass(
                            $fq_class_name . ' is marked deprecated',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $fq_class_name
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }


                if ($context->self
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                    && !NamespaceAnalyzer::isWithin($context->self, $storage->internal)
                ) {
                    if (IssueBuffer::accepts(
                        new InternalClass(
                            $fq_class_name . ' is internal to ' . $storage->internal
                                . ' but called from ' . $context->self,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $fq_class_name
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $method_id = new \Psalm\Internal\MethodIdentifier($fq_class_name, '__construct');

                if ($codebase->methods->methodExists(
                    $method_id,
                    $context->calling_method_id,
                    $codebase->collect_locations ? new CodeLocation($statements_analyzer->getSource(), $stmt) : null,
                    $statements_analyzer,
                    $statements_analyzer->getFilePath()
                )) {
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

                    $template_result = new \Psalm\Internal\Type\TemplateResult([], []);

                    if (self::checkMethodArgs(
                        $method_id,
                        $stmt->args,
                        $template_result,
                        $context,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $statements_analyzer
                    ) === false) {
                        return false;
                    }

                    if (Method\MethodVisibilityAnalyzer::analyze(
                        $method_id,
                        $context,
                        $statements_analyzer->getSource(),
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $statements_analyzer->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                    if ($declaring_method_id) {
                        $method_storage = $codebase->methods->getStorage($declaring_method_id);

                        if (!$method_storage->external_mutation_free && !$context->inside_throw) {
                            if ($context->pure) {
                                if (IssueBuffer::accepts(
                                    new ImpureMethodCall(
                                        'Cannot call an impure constructor from a pure context',
                                        new CodeLocation($statements_analyzer, $stmt)
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

                    $generic_param_types = null;

                    if ($storage->template_types) {
                        $declaring_fq_class_name = $declaring_method_id
                            ? $declaring_method_id->fq_class_name
                            : $fq_class_name;

                        foreach ($storage->template_types as $template_name => $base_type) {
                            if (isset($template_result->upper_bounds[$template_name][$fq_class_name])) {
                                $generic_param_type
                                    = $template_result->upper_bounds[$template_name][$fq_class_name][0];
                            } elseif ($storage->template_type_extends && $template_result->upper_bounds) {
                                $generic_param_type = self::getGenericParamForOffset(
                                    $declaring_fq_class_name,
                                    $template_name,
                                    $storage->template_type_extends,
                                    $template_result->upper_bounds
                                );
                            } else {
                                if ($fq_class_name === 'SplObjectStorage') {
                                    $generic_param_type = Type::getEmpty();
                                } else {
                                    $generic_param_type = array_values($base_type)[0][0];
                                }
                            }

                            $generic_param_type->had_template = true;

                            $generic_param_types[] = $generic_param_type;
                        }
                    }

                    if ($generic_param_types) {
                        $result_atomic_type = new Type\Atomic\TGenericObject(
                            $fq_class_name,
                            $generic_param_types
                        );

                        $result_atomic_type->was_static = $from_static;

                        $statements_analyzer->node_data->setType(
                            $stmt,
                            new Type\Union([$result_atomic_type])
                        );
                    }
                } elseif ($stmt->args) {
                    if (IssueBuffer::accepts(
                        new TooManyArguments(
                            'Class ' . $fq_class_name . ' has no __construct, but arguments were passed',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $fq_class_name . '::__construct'
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($storage->external_mutation_free) {
                    /** @psalm-suppress UndefinedPropertyAssignment */
                    $stmt->external_mutation_free = true;
                    $stmt_type = $statements_analyzer->node_data->getType($stmt);

                    if ($stmt_type) {
                        $stmt_type->reference_free = true;
                    }
                }

                if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                    && !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                    && ($stmt_type = $statements_analyzer->node_data->getType($stmt))
                ) {
                    $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

                    $method_storage = null;

                    $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                    if ($declaring_method_id) {
                        $method_storage = $codebase->methods->getStorage($declaring_method_id);
                    }

                    if ($storage->external_mutation_free
                        || ($method_storage && $method_storage->specialize_call)
                    ) {
                        $method_source = DataFlowNode::getForMethodReturn(
                            (string) $method_id,
                            $fq_class_name . '::__construct',
                            $storage->location,
                            $code_location
                        );
                    } else {
                        $method_source = DataFlowNode::getForMethodReturn(
                            (string) $method_id,
                            $fq_class_name . '::__construct',
                            $storage->location
                        );
                    }

                    $statements_analyzer->data_flow_graph->addNode($method_source);

                    $stmt_type->parent_nodes = [$method_source->id => $method_source];
                }
            } else {
                ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->args,
                    null,
                    null,
                    true,
                    $context
                );
            }
        }



        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }

        return true;
    }

    /**
     * @param  array<string, array<int|string, Type\Union>>  $template_type_extends
     * @param  array<string, array<string, array{Type\Union}>>  $found_generic_params
     */
    private static function getGenericParamForOffset(
        string $fq_class_name,
        string $template_name,
        array $template_type_extends,
        array $found_generic_params,
        bool $mapped = false
    ): Type\Union {
        if (isset($found_generic_params[$template_name][$fq_class_name])) {
            if (!$mapped && isset($template_type_extends[$fq_class_name][$template_name])) {
                foreach ($template_type_extends[$fq_class_name][$template_name]->getAtomicTypes() as $t) {
                    if ($t instanceof Type\Atomic\TTemplateParam) {
                        if ($t->param_name !== $template_name) {
                            return $t->as;
                        }
                    }
                }
            }

            return $found_generic_params[$template_name][$fq_class_name][0];
        }

        foreach ($template_type_extends as $type_map) {
            foreach ($type_map as $extended_template_name => $extended_type) {
                foreach ($extended_type->getAtomicTypes() as $extended_atomic_type) {
                    if (is_string($extended_template_name)
                        && $extended_atomic_type instanceof Type\Atomic\TTemplateParam
                        && $extended_atomic_type->param_name === $template_name
                        && $extended_template_name !== $template_name
                    ) {
                        return self::getGenericParamForOffset(
                            $fq_class_name,
                            $extended_template_name,
                            $template_type_extends,
                            $found_generic_params,
                            true
                        );
                    }
                }
            }
        }

        return Type::getMixed();
    }
}
