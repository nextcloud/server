<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\AtomicPropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\AbstractMethodCall;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\InternalClass;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function count;
use function in_array;
use function strtolower;
use function array_map;
use function explode;
use function strpos;
use function is_string;
use function strlen;
use function substr;
use Psalm\Internal\DataFlow\TaintSource;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Codebase\TaintFlowGraph;
use function array_filter;

/**
 * @internal
 */
class StaticCallAnalyzer extends CallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context
    ) : bool {
        $method_id = null;

        $lhs_type = null;

        $file_analyzer = $statements_analyzer->getFileAnalyzer();
        $codebase = $statements_analyzer->getCodebase();
        $source = $statements_analyzer->getSource();

        $config = $codebase->config;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            $fq_class_name = null;

            if (count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if ($stmt->class->parts[0] === 'parent') {
                    $child_fq_class_name = $context->self;

                    $class_storage = $child_fq_class_name
                        ? $codebase->classlike_storage_provider->get($child_fq_class_name)
                        : null;

                    if (!$class_storage || !$class_storage->parent_class) {
                        if (IssueBuffer::accepts(
                            new ParentNotFound(
                                'Cannot call method on parent as this class does not extend another',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return true;
                    }

                    $fq_class_name = $class_storage->parent_class;

                    $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                    $fq_class_name = $class_storage->name;
                } elseif ($context->self) {
                    if ($stmt->class->parts[0] === 'static' && isset($context->vars_in_scope['$this'])) {
                        $fq_class_name = (string) $context->vars_in_scope['$this'];
                        $lhs_type = clone $context->vars_in_scope['$this'];
                    } else {
                        $fq_class_name = $context->self;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new NonStaticSelfCall(
                            'Cannot use ' . $stmt->class->parts[0] . ' outside class context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return true;
                }

                if ($context->isPhantomClass($fq_class_name)) {
                    return true;
                }
            } elseif ($context->check_classes) {
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

                if ($context->isPhantomClass($fq_class_name)) {
                    return true;
                }

                $does_class_exist = false;

                if ($context->self) {
                    $self_storage = $codebase->classlike_storage_provider->get($context->self);

                    if (isset($self_storage->used_traits[strtolower($fq_class_name)])) {
                        $fq_class_name = $context->self;
                        $does_class_exist = true;
                    }
                }

                if (!isset($context->phantom_classes[strtolower($fq_class_name)])
                    && !$does_class_exist
                ) {
                    $does_class_exist = ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_class_name,
                        new CodeLocation($source, $stmt->class),
                        !$context->collect_initializations
                            && !$context->collect_mutations
                            ? $context->self
                            : null,
                        !$context->collect_initializations
                            && !$context->collect_mutations
                            ? $context->calling_method_id
                            : null,
                        $statements_analyzer->getSuppressedIssues(),
                        false,
                        false,
                        false
                    );
                }

                if (!$does_class_exist) {
                    return $does_class_exist !== false;
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
                    $fq_class_name
                );
            }

            if ($fq_class_name && !$lhs_type) {
                $lhs_type = new Type\Union([new TNamedObject($fq_class_name)]);
            }
        } else {
            $was_inside_use = $context->inside_use;
            $context->inside_use = true;
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context);
            $context->inside_use = $was_inside_use;
            $lhs_type = $statements_analyzer->node_data->getType($stmt->class) ?: Type::getMixed();
        }

        if (!$lhs_type) {
            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                true,
                $context
            ) === false) {
                return false;
            }

            return true;
        }

        $has_mock = false;
        $moved_call = false;

        foreach ($lhs_type->getAtomicTypes() as $lhs_type_part) {
            $intersection_types = [];

            if ($lhs_type_part instanceof TNamedObject) {
                $fq_class_name = $lhs_type_part->value;

                if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($source, $stmt->class),
                    !$context->collect_initializations
                        && !$context->collect_mutations
                        ? $context->self
                        : null,
                    !$context->collect_initializations
                        && !$context->collect_mutations
                        ? $context->calling_method_id
                        : null,
                    $statements_analyzer->getSuppressedIssues(),
                    $stmt->class instanceof PhpParser\Node\Name
                        && count($stmt->class->parts) === 1
                        && in_array(strtolower($stmt->class->parts[0]), ['self', 'static'], true)
                )) {
                    return false;
                }

                $intersection_types = $lhs_type_part->extra_types;
            } elseif ($lhs_type_part instanceof Type\Atomic\TClassString
                && $lhs_type_part->as_type
            ) {
                $fq_class_name = $lhs_type_part->as_type->value;

                if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($source, $stmt->class),
                    $context->self,
                    $context->calling_method_id,
                    $statements_analyzer->getSuppressedIssues(),
                    false
                )) {
                    return false;
                }

                $intersection_types = $lhs_type_part->as_type->extra_types;
            } elseif ($lhs_type_part instanceof Type\Atomic\TDependentGetClass
                && !$lhs_type_part->as_type->hasObject()
            ) {
                $fq_class_name = 'object';

                if ($lhs_type_part->as_type->hasObjectType()
                    && $lhs_type_part->as_type->isSingle()
                ) {
                    foreach ($lhs_type_part->as_type->getAtomicTypes() as $typeof_type_atomic) {
                        if ($typeof_type_atomic instanceof Type\Atomic\TNamedObject) {
                            $fq_class_name = $typeof_type_atomic->value;
                        }
                    }
                }

                if ($fq_class_name === 'object') {
                    continue;
                }
            } elseif ($lhs_type_part instanceof Type\Atomic\TLiteralClassString) {
                $fq_class_name = $lhs_type_part->value;

                if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $statements_analyzer,
                    $fq_class_name,
                    new CodeLocation($source, $stmt->class),
                    $context->self,
                    $context->calling_method_id,
                    $statements_analyzer->getSuppressedIssues(),
                    false
                )) {
                    return false;
                }
            } elseif ($lhs_type_part instanceof Type\Atomic\TTemplateParam
                && !$lhs_type_part->as->isMixed()
                && !$lhs_type_part->as->hasObject()
            ) {
                $fq_class_name = null;

                foreach ($lhs_type_part->as->getAtomicTypes() as $generic_param_type) {
                    if (!$generic_param_type instanceof TNamedObject) {
                        continue 2;
                    }

                    $fq_class_name = $generic_param_type->value;
                    break;
                }

                if (!$fq_class_name) {
                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Type ' . $lhs_type_part->as . ' cannot be called as a class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            (string) $lhs_type_part
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    continue;
                }
            } else {
                if ($lhs_type_part instanceof Type\Atomic\TMixed
                    || $lhs_type_part instanceof Type\Atomic\TTemplateParam
                    || $lhs_type_part instanceof Type\Atomic\TClassString
                ) {
                    if ($stmt->name instanceof PhpParser\Node\Identifier) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($stmt->name->name),
                            $context->calling_method_id ?: $statements_analyzer->getFileName()
                        );
                    }

                    if (IssueBuffer::accepts(
                        new MixedMethodCall(
                            'Cannot call method on an unknown class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    continue;
                }

                if ($lhs_type_part instanceof Type\Atomic\TString) {
                    if ($config->allow_string_standin_for_class
                        && !$lhs_type_part instanceof Type\Atomic\TNumericString
                    ) {
                        continue;
                    }

                    if (IssueBuffer::accepts(
                        new InvalidStringClass(
                            'String cannot be used as a class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    continue;
                }

                if ($lhs_type_part instanceof Type\Atomic\TNull
                    && $lhs_type->ignore_nullable_issues
                ) {
                    continue;
                }

                if (IssueBuffer::accepts(
                    new UndefinedClass(
                        'Type ' . $lhs_type_part . ' cannot be called as a class',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        (string) $lhs_type_part
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                continue;
            }

            $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

            $is_mock = ExpressionAnalyzer::isMock($fq_class_name);

            $has_mock = $has_mock || $is_mock;

            if ($stmt->name instanceof PhpParser\Node\Identifier && !$is_mock) {
                $method_name_lc = strtolower($stmt->name->name);
                $method_id = new MethodIdentifier($fq_class_name, $method_name_lc);

                $cased_method_id = $fq_class_name . '::' . $stmt->name->name;

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

                $args = $stmt->args;

                if ($intersection_types
                    && !$codebase->methods->methodExists($method_id)
                ) {
                    foreach ($intersection_types as $intersection_type) {
                        if (!$intersection_type instanceof TNamedObject) {
                            continue;
                        }

                        $intersection_method_id = new MethodIdentifier(
                            $intersection_type->value,
                            $method_name_lc
                        );

                        if ($codebase->methods->methodExists($intersection_method_id)) {
                            $method_id = $intersection_method_id;
                            $cased_method_id = $intersection_type->value . '::' . $stmt->name->name;
                            $fq_class_name = $intersection_type->value;
                            break;
                        }
                    }
                }

                $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                $naive_method_exists = $codebase->methods->methodExists(
                    $method_id,
                    !$context->collect_initializations
                        && !$context->collect_mutations
                        ? $context->calling_method_id
                        : null,
                    $codebase->collect_locations
                        ? new CodeLocation($source, $stmt->name)
                        : null,
                    $statements_analyzer,
                    $statements_analyzer->getFilePath(),
                    false
                );

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

                if (!$naive_method_exists
                    && $class_storage->mixin_declaring_fqcln
                    && $class_storage->namedMixins
                ) {
                    foreach ($class_storage->namedMixins as $mixin) {
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
                            $mixin_candidates = [];
                            foreach ($class_storage->templatedMixins as $mixin_candidate) {
                                $mixin_candidates[] = clone $mixin_candidate;
                            }

                            foreach ($class_storage->namedMixins as $mixin_candidate) {
                                $mixin_candidates[] = clone $mixin_candidate;
                            }

                            $mixin_candidates_no_generic = array_filter($mixin_candidates, function ($check): bool {
                                return !($check instanceof Type\Atomic\TGenericObject);
                            });

                            // $mixin_candidates_no_generic will only be empty when there are TGenericObject entries.
                            // In that case, Union will be initialized with an empty array but
                            // replaced with non-empty types in the following loop.
                            /** @psalm-suppress ArgumentTypeCoercion */
                            $mixin_candidate_type = new Type\Union($mixin_candidates_no_generic);

                            foreach ($mixin_candidates as $tGenericMixin) {
                                if (!($tGenericMixin instanceof Type\Atomic\TGenericObject)) {
                                    continue;
                                }

                                $mixin_declaring_class_storage = $codebase->classlike_storage_provider->get(
                                    $class_storage->mixin_declaring_fqcln
                                );

                                $new_mixin_candidate_type = AtomicPropertyFetchAnalyzer::localizePropertyType(
                                    $codebase,
                                    new Type\Union([$lhs_type_part]),
                                    $tGenericMixin,
                                    $class_storage,
                                    $mixin_declaring_class_storage
                                );

                                foreach ($mixin_candidate_type->getAtomicTypes() as $type) {
                                    $new_mixin_candidate_type->addType($type);
                                }

                                $mixin_candidate_type = $new_mixin_candidate_type;
                            }

                            $new_lhs_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                                $codebase,
                                $mixin_candidate_type,
                                $fq_class_name,
                                $fq_class_name,
                                $class_storage->parent_class,
                                true,
                                false,
                                $class_storage->final
                            );

                            $old_data_provider = $statements_analyzer->node_data;

                            $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                            $context->vars_in_scope['$tmp_mixin_var'] = $new_lhs_type;

                            $fake_method_call_expr = new PhpParser\Node\Expr\MethodCall(
                                new PhpParser\Node\Expr\Variable(
                                    'tmp_mixin_var',
                                    $stmt->class->getAttributes()
                                ),
                                $stmt->name,
                                $stmt->args,
                                $stmt->getAttributes()
                            );

                            if (MethodCallAnalyzer::analyze(
                                $statements_analyzer,
                                $fake_method_call_expr,
                                $context
                            ) === false) {
                                return false;
                            }

                            $fake_method_call_type = $statements_analyzer->node_data->getType($fake_method_call_expr);

                            $statements_analyzer->node_data = $old_data_provider;

                            $statements_analyzer->node_data->setType($stmt, $fake_method_call_type ?: Type::getMixed());

                            return true;
                        }
                    }
                }

                if (!$naive_method_exists
                    || !MethodAnalyzer::isMethodVisible(
                        $method_id,
                        $context,
                        $statements_analyzer->getSource()
                    )
                    || $fake_method_exists
                    || (isset($class_storage->pseudo_static_methods[$method_name_lc])
                        && ($config->use_phpdoc_method_without_magic_or_parent || $class_storage->parent_class))
                ) {
                    $callstatic_id = new MethodIdentifier(
                        $fq_class_name,
                        '__callstatic'
                    );
                    if ($codebase->methods->methodExists(
                        $callstatic_id,
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
                        if ($codebase->methods->return_type_provider->has($fq_class_name)) {
                            $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                                $statements_analyzer,
                                $method_id->fq_class_name,
                                $method_id->method_name,
                                $stmt->args,
                                $context,
                                new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                                null,
                                null,
                                strtolower($stmt->name->name)
                            );

                            if ($return_type_candidate) {
                                CallAnalyzer::checkMethodArgs(
                                    $method_id,
                                    $stmt->args,
                                    null,
                                    $context,
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $statements_analyzer
                                );

                                $statements_analyzer->node_data->setType($stmt, $return_type_candidate);

                                return true;
                            }
                        }

                        if (isset($class_storage->pseudo_static_methods[$method_name_lc])) {
                            $pseudo_method_storage = $class_storage->pseudo_static_methods[$method_name_lc];

                            if (self::checkPseudoMethod(
                                $statements_analyzer,
                                $stmt,
                                $method_id,
                                $fq_class_name,
                                $args,
                                $class_storage,
                                $pseudo_method_storage,
                                $context
                            ) === false
                            ) {
                                return false;
                            }

                            if ($pseudo_method_storage->return_type) {
                                return true;
                            }
                        } else {
                            if (ArgumentsAnalyzer::analyze(
                                $statements_analyzer,
                                $args,
                                null,
                                null,
                                true,
                                $context
                            ) === false) {
                                return false;
                            }
                        }

                        $array_values = array_map(
                            function (PhpParser\Node\Arg $arg): PhpParser\Node\Expr\ArrayItem {
                                return new PhpParser\Node\Expr\ArrayItem($arg->value);
                            },
                            $args
                        );

                        $args = [
                            new PhpParser\Node\Arg(new PhpParser\Node\Scalar\String_((string) $method_id)),
                            new PhpParser\Node\Arg(new PhpParser\Node\Expr\Array_($array_values)),
                        ];

                        $method_id = new MethodIdentifier(
                            $fq_class_name,
                            '__callstatic'
                        );
                    } elseif (isset($class_storage->pseudo_static_methods[$method_name_lc])
                        && ($config->use_phpdoc_method_without_magic_or_parent || $class_storage->parent_class)
                    ) {
                        $pseudo_method_storage = $class_storage->pseudo_static_methods[$method_name_lc];

                        if (self::checkPseudoMethod(
                            $statements_analyzer,
                            $stmt,
                            $method_id,
                            $fq_class_name,
                            $args,
                            $class_storage,
                            $pseudo_method_storage,
                            $context
                        ) === false
                        ) {
                            return false;
                        }

                        if ($pseudo_method_storage->return_type) {
                            return true;
                        }
                    }

                    if (!$context->check_methods) {
                        if (ArgumentsAnalyzer::analyze(
                            $statements_analyzer,
                            $stmt->args,
                            null,
                            null,
                            true,
                            $context
                        ) === false) {
                            return false;
                        }

                        return true;
                    }
                }

                $does_method_exist = MethodAnalyzer::checkMethodExists(
                    $codebase,
                    $method_id,
                    new CodeLocation($source, $stmt),
                    $statements_analyzer->getSuppressedIssues(),
                    $context->calling_method_id
                );

                if (!$does_method_exist) {
                    if (ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $stmt->args,
                        null,
                        null,
                        true,
                        $context
                    ) === false) {
                        return false;
                    }

                    if ($codebase->alter_code && $fq_class_name && !$moved_call) {
                        $codebase->classlikes->handleClassLikeReferenceInMigration(
                            $codebase,
                            $statements_analyzer,
                            $stmt->class,
                            $fq_class_name,
                            $context->calling_method_id
                        );
                    }

                    return true;
                }

                $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                if ($class_storage->user_defined
                    && $context->self
                    && ($context->collect_mutations || $context->collect_initializations)
                ) {
                    $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

                    if (!$appearing_method_id) {
                        if (IssueBuffer::accepts(
                            new UndefinedMethod(
                                'Method ' . $method_id . ' does not exist',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                (string) $method_id
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            //
                        }

                        return true;
                    }

                    $appearing_method_class_name = $appearing_method_id->fq_class_name;

                    if ($codebase->classExtends($context->self, $appearing_method_class_name)) {
                        $old_context_include_location = $context->include_location;
                        $old_self = $context->self;
                        $context->include_location = new CodeLocation($statements_analyzer->getSource(), $stmt);
                        $context->self = $appearing_method_class_name;

                        if ($context->collect_mutations) {
                            $file_analyzer->getMethodMutations($method_id, $context);
                        } else {
                            // collecting initializations
                            $local_vars_in_scope = [];
                            $local_vars_possibly_in_scope = [];

                            foreach ($context->vars_in_scope as $var => $_) {
                                if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                                    $local_vars_in_scope[$var] = $context->vars_in_scope[$var];
                                }
                            }

                            foreach ($context->vars_possibly_in_scope as $var => $_) {
                                if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                                    $local_vars_possibly_in_scope[$var] = $context->vars_possibly_in_scope[$var];
                                }
                            }

                            if (!isset($context->initialized_methods[(string) $method_id])) {
                                if ($context->initialized_methods === null) {
                                    $context->initialized_methods = [];
                                }

                                $context->initialized_methods[(string) $method_id] = true;

                                $file_analyzer->getMethodMutations($method_id, $context);

                                foreach ($local_vars_in_scope as $var => $type) {
                                    $context->vars_in_scope[$var] = $type;
                                }

                                foreach ($local_vars_possibly_in_scope as $var => $type) {
                                    $context->vars_possibly_in_scope[$var] = $type;
                                }
                            }
                        }

                        $context->include_location = $old_context_include_location;
                        $context->self = $old_self;
                    }
                }

                if ($class_storage->deprecated && $fq_class_name !== $context->self) {
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

                if ($context->self && ! NamespaceAnalyzer::isWithin($context->self, $class_storage->internal)) {
                    if (IssueBuffer::accepts(
                        new InternalClass(
                            $fq_class_name . ' is internal to ' . $class_storage->internal
                                . ' but called from ' . $context->self,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $fq_class_name
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if (Method\MethodVisibilityAnalyzer::analyze(
                    $method_id,
                    $context,
                    $statements_analyzer->getSource(),
                    new CodeLocation($source, $stmt),
                    $statements_analyzer->getSuppressedIssues()
                ) === false) {
                    return false;
                }

                if ((!$stmt->class instanceof PhpParser\Node\Name
                        || $stmt->class->parts[0] !== 'parent'
                        || $statements_analyzer->isStatic())
                    && (
                        !$context->self
                        || $statements_analyzer->isStatic()
                        || !$codebase->classExtends($context->self, $fq_class_name)
                    )
                ) {
                    if (MethodAnalyzer::checkStatic(
                        $method_id,
                        ($stmt->class instanceof PhpParser\Node\Name
                            && strtolower($stmt->class->parts[0]) === 'self')
                            || $context->self === $fq_class_name,
                        !$statements_analyzer->isStatic(),
                        $codebase,
                        new CodeLocation($source, $stmt),
                        $statements_analyzer->getSuppressedIssues(),
                        $is_dynamic_this_method
                    ) === false) {
                        // fall through
                    }

                    if ($is_dynamic_this_method) {
                        $old_data_provider = $statements_analyzer->node_data;

                        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                        $fake_method_call_expr = new PhpParser\Node\Expr\MethodCall(
                            new PhpParser\Node\Expr\Variable(
                                'this',
                                $stmt->class->getAttributes()
                            ),
                            $stmt->name,
                            $stmt->args,
                            $stmt->getAttributes()
                        );

                        if (MethodCallAnalyzer::analyze(
                            $statements_analyzer,
                            $fake_method_call_expr,
                            $context
                        ) === false) {
                            return false;
                        }

                        $fake_method_call_type = $statements_analyzer->node_data->getType($fake_method_call_expr);

                        $statements_analyzer->node_data = $old_data_provider;

                        if ($fake_method_call_type) {
                            $statements_analyzer->node_data->setType($stmt, $fake_method_call_type);
                        }

                        return true;
                    }
                }

                if (Method\MethodCallProhibitionAnalyzer::analyze(
                    $codebase,
                    $context,
                    $method_id,
                    $statements_analyzer->getNamespace(),
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer->getSuppressedIssues()
                ) === false) {
                    // fall through
                }

                $found_generic_params = ClassTemplateParamCollector::collect(
                    $codebase,
                    $class_storage,
                    $class_storage,
                    $method_name_lc,
                    $lhs_type_part,
                    null
                );

                if ($found_generic_params
                    && $stmt->class instanceof PhpParser\Node\Name
                    && $stmt->class->parts === ['parent']
                    && $context->self
                    && ($self_class_storage = $codebase->classlike_storage_provider->get($context->self))
                    && $self_class_storage->template_type_extends
                ) {
                    foreach ($self_class_storage->template_type_extends as $template_fq_class_name => $extended_types) {
                        foreach ($extended_types as $type_key => $extended_type) {
                            if (!is_string($type_key)) {
                                continue;
                            }

                            if (isset($found_generic_params[$type_key][$template_fq_class_name])) {
                                $found_generic_params[$type_key][$template_fq_class_name][0] = clone $extended_type;
                                continue;
                            }

                            foreach ($extended_type->getAtomicTypes() as $t) {
                                if ($t instanceof Type\Atomic\TTemplateParam
                                    && isset($found_generic_params[$t->param_name][$t->defining_class])
                                ) {
                                    $found_generic_params[$type_key][$template_fq_class_name] = [
                                        $found_generic_params[$t->param_name][$t->defining_class][0]
                                    ];
                                } else {
                                    $found_generic_params[$type_key][$template_fq_class_name] = [
                                        clone $extended_type
                                    ];
                                    break;
                                }
                            }
                        }
                    }
                }

                $template_result = new \Psalm\Internal\Type\TemplateResult([], $found_generic_params ?: []);

                if (self::checkMethodArgs(
                    $method_id,
                    $args,
                    $template_result,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer
                ) === false) {
                    return false;
                }

                $fq_class_name = $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts === ['parent']
                    ? (string) $statements_analyzer->getFQCLN()
                    : $fq_class_name;

                $self_fq_class_name = $fq_class_name;

                $return_type_candidate = null;

                if ($codebase->methods->return_type_provider->has($fq_class_name)) {
                    $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                        $statements_analyzer,
                        $fq_class_name,
                        $stmt->name->name,
                        $stmt->args,
                        $context,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->name)
                    );
                }

                $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                if (!$return_type_candidate
                    && $declaring_method_id
                    && (string) $declaring_method_id !== (string) $method_id
                ) {
                    $declaring_fq_class_name = $declaring_method_id->fq_class_name;
                    $declaring_method_name = $declaring_method_id->method_name;

                    if ($codebase->methods->return_type_provider->has($declaring_fq_class_name)) {
                        $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                            $statements_analyzer,
                            $declaring_fq_class_name,
                            $declaring_method_name,
                            $stmt->args,
                            $context,
                            new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                            null,
                            $fq_class_name,
                            $stmt->name->name
                        );
                    }
                }

                if (!$return_type_candidate) {
                    $return_type_candidate = $codebase->methods->getMethodReturnType(
                        $method_id,
                        $self_fq_class_name,
                        $statements_analyzer,
                        $args
                    );

                    if ($return_type_candidate) {
                        $return_type_candidate = clone $return_type_candidate;

                        if ($template_result->template_types) {
                            $bindable_template_types = $return_type_candidate->getTemplateTypes();

                            foreach ($bindable_template_types as $template_type) {
                                if (!isset(
                                    $template_result->upper_bounds
                                        [$template_type->param_name]
                                        [$template_type->defining_class]
                                )) {
                                    if ($template_type->param_name === 'TFunctionArgCount') {
                                        $template_result->upper_bounds[$template_type->param_name] = [
                                            'fn-' . strtolower((string) $method_id) => [
                                                Type::getInt(false, count($stmt->args)),
                                                0
                                            ]
                                        ];
                                    } else {
                                        $template_result->upper_bounds[$template_type->param_name] = [
                                            ($template_type->defining_class) => [Type::getEmpty(), 0]
                                        ];
                                    }
                                }
                            }
                        }

                        if ($lhs_type_part instanceof Type\Atomic\TTemplateParam) {
                            $static_type = $lhs_type_part;
                        } elseif ($lhs_type_part instanceof Type\Atomic\TTemplateParamClass) {
                            $static_type = new Type\Atomic\TTemplateParam(
                                $lhs_type_part->param_name,
                                $lhs_type_part->as_type
                                    ? new Type\Union([$lhs_type_part->as_type])
                                    : Type::getObject(),
                                $lhs_type_part->defining_class
                            );
                        } else {
                            $static_type = $fq_class_name;
                        }

                        if ($template_result->upper_bounds) {
                            $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                                $codebase,
                                $return_type_candidate,
                                null,
                                null,
                                null
                            );

                            $return_type_candidate->replaceTemplateTypesWithArgTypes(
                                $template_result,
                                $codebase
                            );
                        }

                        $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                            $codebase,
                            $return_type_candidate,
                            $self_fq_class_name,
                            $static_type,
                            $class_storage->parent_class,
                            true,
                            false,
                            \is_string($static_type)
                                && $static_type !== $context->self
                        );

                        $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                            $method_id,
                            $secondary_return_type_location
                        );

                        if ($secondary_return_type_location) {
                            $return_type_location = $secondary_return_type_location;
                        }

                        // only check the type locally if it's defined externally
                        if ($return_type_location && !$config->isInProjectDirs($return_type_location->file_path)) {
                            $return_type_candidate->check(
                                $statements_analyzer,
                                new CodeLocation($source, $stmt),
                                $statements_analyzer->getSuppressedIssues(),
                                $context->phantom_classes,
                                true,
                                false,
                                false,
                                $context->calling_method_id
                            );
                        }
                    }
                }

                $method_storage = $codebase->methods->getUserMethodStorage($method_id);

                if ($method_storage) {
                    if ($method_storage->abstract
                        && $stmt->class instanceof PhpParser\Node\Name
                        && (!$context->self
                            || !\Psalm\Internal\Type\Comparator\UnionTypeComparator::isContainedBy(
                                $codebase,
                                $context->vars_in_scope['$this']
                                    ?? new Type\Union([
                                        new Type\Atomic\TNamedObject($context->self)
                                    ]),
                                new Type\Union([
                                    new Type\Atomic\TNamedObject($method_id->fq_class_name)
                                ])
                            ))
                    ) {
                        if (IssueBuffer::accepts(
                            new AbstractMethodCall(
                                'Cannot call an abstract static method ' . $method_id . ' directly',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        return true;
                    }

                    if (!$context->inside_throw) {
                        if ($context->pure && !$method_storage->pure) {
                            if (IssueBuffer::accepts(
                                new ImpureMethodCall(
                                    'Cannot call an impure method from a pure context',
                                    new CodeLocation($source, $stmt->name)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } elseif ($context->mutation_free && !$method_storage->mutation_free) {
                            if (IssueBuffer::accepts(
                                new ImpureMethodCall(
                                    'Cannot call an possibly-mutating method from a mutation-free context',
                                    new CodeLocation($source, $stmt->name)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } elseif ($statements_analyzer->getSource()
                                instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                            && $statements_analyzer->getSource()->track_mutations
                            && !$method_storage->pure
                        ) {
                            if (!$method_storage->mutation_free) {
                                $statements_analyzer->getSource()->inferred_has_mutation = true;
                            }

                            $statements_analyzer->getSource()->inferred_impure = true;
                        }
                    }

                    $generic_params = $template_result->upper_bounds;

                    if ($method_storage->assertions) {
                        self::applyAssertionsToContext(
                            $stmt->name,
                            null,
                            $method_storage->assertions,
                            $stmt->args,
                            $generic_params,
                            $context,
                            $statements_analyzer
                        );
                    }

                    if ($method_storage->if_true_assertions) {
                        $statements_analyzer->node_data->setIfTrueAssertions(
                            $stmt,
                            array_map(
                                function (Assertion $assertion) use ($generic_params) : Assertion {
                                    return $assertion->getUntemplatedCopy($generic_params, null);
                                },
                                $method_storage->if_true_assertions
                            )
                        );
                    }

                    if ($method_storage->if_false_assertions) {
                        $statements_analyzer->node_data->setIfFalseAssertions(
                            $stmt,
                            array_map(
                                function (Assertion $assertion) use ($generic_params) : Assertion {
                                    return $assertion->getUntemplatedCopy($generic_params, null);
                                },
                                $method_storage->if_false_assertions
                            )
                        );
                    }
                }

                if ($codebase->alter_code) {
                    foreach ($codebase->call_transforms as $original_pattern => $transformation) {
                        if ($declaring_method_id
                            && strtolower((string) $declaring_method_id) . '\((.*\))' === $original_pattern
                        ) {
                            if (strpos($transformation, '($1)') === strlen($transformation) - 4
                                && $stmt->class instanceof PhpParser\Node\Name
                            ) {
                                $new_method_id = substr($transformation, 0, -4);
                                $old_declaring_fq_class_name = $declaring_method_id->fq_class_name;
                                [$new_fq_class_name, $new_method_name] = explode('::', $new_method_id);

                                if ($codebase->classlikes->handleClassLikeReferenceInMigration(
                                    $codebase,
                                    $statements_analyzer,
                                    $stmt->class,
                                    $new_fq_class_name,
                                    $context->calling_method_id,
                                    strtolower($old_declaring_fq_class_name) !== strtolower($new_fq_class_name),
                                    $stmt->class->parts[0] === 'self'
                                )) {
                                    $moved_call = true;
                                }

                                $file_manipulations = [];

                                $file_manipulations[] = new \Psalm\FileManipulation(
                                    (int) $stmt->name->getAttribute('startFilePos'),
                                    (int) $stmt->name->getAttribute('endFilePos') + 1,
                                    $new_method_name
                                );

                                FileManipulationBuffer::add(
                                    $statements_analyzer->getFilePath(),
                                    $file_manipulations
                                );
                            }
                        }
                    }
                }

                if ($config->after_method_checks) {
                    $file_manipulations = [];

                    $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

                    if ($appearing_method_id !== null && $declaring_method_id) {
                        foreach ($config->after_method_checks as $plugin_fq_class_name) {
                            $plugin_fq_class_name::afterMethodCallAnalysis(
                                $stmt,
                                (string) $method_id,
                                (string) $appearing_method_id,
                                (string) $declaring_method_id,
                                $context,
                                $source,
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

                $return_type_candidate = $return_type_candidate ?: Type::getMixed();

                self::taintReturnType(
                    $statements_analyzer,
                    $stmt,
                    $method_id,
                    $cased_method_id,
                    $return_type_candidate,
                    $method_storage
                );

                if ($stmt_type = $statements_analyzer->node_data->getType($stmt)) {
                    $statements_analyzer->node_data->setType(
                        $stmt,
                        Type::combineUnionTypes($stmt_type, $return_type_candidate)
                    );
                } else {
                    $statements_analyzer->node_data->setType($stmt, $return_type_candidate);
                }
            } else {
                if ($stmt->name instanceof PhpParser\Node\Expr) {
                    $was_inside_use = $context->inside_use;
                    $context->inside_use = true;

                    ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);

                    $context->inside_use = $was_inside_use;
                }

                if (!$context->ignore_variable_method) {
                    $codebase->analyzer->addMixedMemberName(
                        strtolower($fq_class_name) . '::',
                        $context->calling_method_id ?: $statements_analyzer->getFileName()
                    );
                }

                if (ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->args,
                    null,
                    null,
                    true,
                    $context
                ) === false) {
                    return false;
                }
            }

            if ($codebase->alter_code
                && $fq_class_name
                && !$moved_call
                && $stmt->class instanceof PhpParser\Node\Name
                && !in_array($stmt->class->parts[0], ['parent', 'static'])
            ) {
                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id,
                    false,
                    $stmt->class->parts[0] === 'self'
                );
            }

            if ($codebase->store_node_types
                && $method_id
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $method_id . '()'
                );
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
                && ($stmt_type = $statements_analyzer->node_data->getType($stmt))
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $stmt_type->getId(),
                    $stmt
                );
            }
        }

        if ($method_id === null) {
            return self::checkMethodArgs(
                $method_id,
                $stmt->args,
                null,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer
            );
        }

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }

        if (!$statements_analyzer->node_data->getType($stmt)) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        return true;
    }

    private static function taintReturnType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        MethodIdentifier $method_id,
        string $cased_method_id,
        Type\Union $return_type_candidate,
        ?\Psalm\Storage\MethodStorage $method_storage
    ) : void {
        if (!$statements_analyzer->data_flow_graph instanceof TaintFlowGraph
            || \in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
        ) {
            return;
        }

        $code_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        $method_location = $method_storage
            ? ($method_storage->signature_return_type_location ?: $method_storage->location)
            : null;

        if ($method_storage && $method_storage->specialize_call) {
            $method_source = DataFlowNode::getForMethodReturn(
                (string) $method_id,
                $cased_method_id,
                $method_location,
                $code_location
            );
        } else {
            $method_source = DataFlowNode::getForMethodReturn(
                (string) $method_id,
                $cased_method_id,
                $method_location
            );
        }

        $statements_analyzer->data_flow_graph->addNode($method_source);

        $return_type_candidate->parent_nodes = [$method_source->id => $method_source];

        if ($method_storage && $method_storage->taint_source_types) {
            $method_node = TaintSource::getForMethodReturn(
                (string) $method_id,
                $cased_method_id,
                $method_storage->signature_return_type_location ?: $method_storage->location
            );

            $method_node->taints = $method_storage->taint_source_types;

            $statements_analyzer->data_flow_graph->addSource($method_node);
        }
    }

    /**
     * @param  array<int, PhpParser\Node\Arg> $args
     * @return false|null
     */
    private static function checkPseudoMethod(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        MethodIdentifier $method_id,
        string $fq_class_name,
        array $args,
        \Psalm\Storage\ClassLikeStorage $class_storage,
        \Psalm\Storage\MethodStorage $pseudo_method_storage,
        Context $context
    ): ?bool {
        if (ArgumentsAnalyzer::analyze(
            $statements_analyzer,
            $args,
            $pseudo_method_storage->params,
            (string) $method_id,
            true,
            $context
        ) === false) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        if (ArgumentsAnalyzer::checkArgumentsMatch(
            $statements_analyzer,
            $args,
            $method_id,
            $pseudo_method_storage->params,
            $pseudo_method_storage,
            null,
            null,
            new CodeLocation($statements_analyzer, $stmt),
            $context
        ) === false) {
            return false;
        }

        $method_storage = null;

        if ($statements_analyzer->data_flow_graph) {
            try {
                $method_storage = $codebase->methods->getStorage($method_id);

                ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $args,
                    $method_storage->params,
                    (string) $method_id,
                    true,
                    $context
                );

                ArgumentsAnalyzer::checkArgumentsMatch(
                    $statements_analyzer,
                    $args,
                    $method_id,
                    $method_storage->params,
                    $method_storage,
                    null,
                    null,
                    new CodeLocation($statements_analyzer, $stmt),
                    $context
                );
            } catch (\Exception $e) {
                // do nothing
            }
        }

        if ($pseudo_method_storage->return_type) {
            $return_type_candidate = clone $pseudo_method_storage->return_type;

            $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $statements_analyzer->getCodebase(),
                $return_type_candidate,
                $fq_class_name,
                $fq_class_name,
                $class_storage->parent_class
            );

            if ($method_storage) {
                self::taintReturnType(
                    $statements_analyzer,
                    $stmt,
                    $method_id,
                    (string) $method_id,
                    $return_type_candidate,
                    $method_storage
                );
            }

            $stmt_type = $statements_analyzer->node_data->getType($stmt);

            if (!$stmt_type) {
                $statements_analyzer->node_data->setType($stmt, $return_type_candidate);
            } else {
                $statements_analyzer->node_data->setType(
                    $stmt,
                    Type::combineUnionTypes(
                        $return_type_candidate,
                        $stmt_type
                    )
                );
            }
        }

        return null;
    }
}
