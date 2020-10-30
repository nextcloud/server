<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use function array_merge;
use function strtolower;
use function in_array;

/**
 * @internal
 */
class ObjectComparator
{
    /**
     * @param  TNamedObject|TTemplateParam|TIterable  $input_type_part
     * @param  TNamedObject|TTemplateParam|TIterable  $container_type_part
     *
     */
    public static function isShallowlyContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ): bool {
        $intersection_input_types = $input_type_part->extra_types ?: [];
        $intersection_input_types[$input_type_part->getKey(false)] = $input_type_part;

        if ($input_type_part instanceof TTemplateParam) {
            foreach ($input_type_part->as->getAtomicTypes() as $g) {
                if ($g instanceof TNamedObject && $g->extra_types) {
                    $intersection_input_types = array_merge(
                        $intersection_input_types,
                        $g->extra_types
                    );
                }
            }
        }

        $intersection_container_types = $container_type_part->extra_types ?: [];
        $intersection_container_types[$container_type_part->getKey(false)] = $container_type_part;

        if ($container_type_part instanceof TTemplateParam) {
            foreach ($container_type_part->as->getAtomicTypes() as $g) {
                if ($g instanceof TNamedObject && $g->extra_types) {
                    $intersection_container_types = array_merge(
                        $intersection_container_types,
                        $g->extra_types
                    );
                }
            }
        }

        foreach ($intersection_container_types as $container_type_key => $intersection_container_type) {
            $container_was_static = false;

            if ($intersection_container_type instanceof TIterable) {
                $intersection_container_type_lower = 'iterable';
            } elseif ($intersection_container_type instanceof TObjectWithProperties) {
                $intersection_container_type_lower = 'object';
            } elseif ($intersection_container_type instanceof TTemplateParam) {
                if (!$allow_interface_equality) {
                    if (isset($intersection_input_types[$container_type_key])) {
                        continue;
                    }

                    if (\substr($intersection_container_type->defining_class, 0, 3) === 'fn-') {
                        foreach ($intersection_input_types as $intersection_input_type) {
                            if ($intersection_input_type instanceof TTemplateParam
                                && \substr($intersection_input_type->defining_class, 0, 3) === 'fn-'
                                && $intersection_input_type->defining_class
                                    !== $intersection_container_type->defining_class
                            ) {
                                continue 2;
                            }
                        }
                    }

                    return false;
                }

                if ($intersection_container_type->as->isMixed()) {
                    continue;
                }

                $intersection_container_type_lower = null;

                foreach ($intersection_container_type->as->getAtomicTypes() as $g) {
                    if ($g instanceof TNull) {
                        continue;
                    }

                    if ($g instanceof TObject) {
                        continue 2;
                    }

                    if (!$g instanceof TNamedObject) {
                        continue 2;
                    }

                    $intersection_container_type_lower = strtolower($g->value);
                }

                if ($intersection_container_type_lower === null) {
                    return false;
                }
            } else {
                $container_was_static = $intersection_container_type->was_static;

                $intersection_container_type_lower = strtolower(
                    $codebase->classlikes->getUnAliasedName(
                        $intersection_container_type->value
                    )
                );
            }

            foreach ($intersection_input_types as $intersection_input_key => $intersection_input_type) {
                $input_was_static = false;

                if ($intersection_input_type instanceof TIterable) {
                    $intersection_input_type_lower = 'iterable';
                } elseif ($intersection_input_type instanceof TObjectWithProperties) {
                    $intersection_input_type_lower = 'object';
                } elseif ($intersection_input_type instanceof TTemplateParam) {
                    if ($intersection_input_type->as->isMixed()) {
                        continue;
                    }

                    $intersection_input_type_lower = null;

                    foreach ($intersection_input_type->as->getAtomicTypes() as $g) {
                        if ($g instanceof TNull) {
                            continue;
                        }

                        if (!$g instanceof TNamedObject) {
                            continue 2;
                        }

                        $intersection_input_type_lower = strtolower($g->value);
                    }

                    if ($intersection_input_type_lower === null) {
                        return false;
                    }
                } else {
                    $input_was_static = $intersection_input_type->was_static;

                    $intersection_input_type_lower = strtolower(
                        $codebase->classlikes->getUnAliasedName(
                            $intersection_input_type->value
                        )
                    );
                }

                if ($intersection_container_type instanceof TTemplateParam
                    && $intersection_input_type instanceof TTemplateParam
                ) {
                    if ($intersection_container_type->param_name !== $intersection_input_type->param_name
                        || ((string)$intersection_container_type->defining_class
                            !== (string)$intersection_input_type->defining_class
                            && \substr($intersection_input_type->defining_class, 0, 3) !== 'fn-'
                            && \substr($intersection_container_type->defining_class, 0, 3) !== 'fn-')
                    ) {
                        if (\substr($intersection_input_type->defining_class, 0, 3) !== 'fn-') {
                            $input_class_storage = $codebase->classlike_storage_provider->get(
                                $intersection_input_type->defining_class
                            );

                            if (isset($input_class_storage->template_type_extends
                                    [$intersection_container_type->defining_class]
                                    [$intersection_container_type->param_name])
                            ) {
                                continue;
                            }
                        }

                        return false;
                    }
                }

                if (!$intersection_container_type instanceof TTemplateParam
                    || $intersection_input_type instanceof TTemplateParam
                ) {
                    if ($intersection_container_type_lower === $intersection_input_type_lower) {
                        if ($container_was_static
                            && !$input_was_static
                            && !$intersection_input_type instanceof TTemplateParam
                        ) {
                            if ($atomic_comparison_result) {
                                $atomic_comparison_result->type_coerced = true;
                            }

                            continue;
                        }

                        continue 2;
                    }

                    if ($intersection_input_type_lower === 'generator'
                        && in_array($intersection_container_type_lower, ['iterator', 'traversable', 'iterable'], true)
                    ) {
                        continue 2;
                    }

                    if ($intersection_container_type_lower === 'iterable') {
                        if ($intersection_input_type_lower === 'traversable'
                            || ($codebase->classlikes->classExists($intersection_input_type_lower)
                                && $codebase->classlikes->classImplements(
                                    $intersection_input_type_lower,
                                    'Traversable'
                                ))
                            || ($codebase->classlikes->interfaceExists($intersection_input_type_lower)
                                && $codebase->classlikes->interfaceExtends(
                                    $intersection_input_type_lower,
                                    'Traversable'
                                ))
                        ) {
                            continue 2;
                        }
                    }

                    if ($intersection_input_type_lower === 'traversable'
                        && $intersection_container_type_lower === 'iterable'
                    ) {
                        continue 2;
                    }

                    $input_type_is_interface = $codebase->interfaceExists($intersection_input_type_lower);
                    $container_type_is_interface = $codebase->interfaceExists($intersection_container_type_lower);

                    if ($allow_interface_equality
                        && $container_type_is_interface
                        && ($input_type_is_interface || !isset($intersection_container_types[$intersection_input_key]))
                    ) {
                        continue 2;
                    }

                    if ($codebase->classExists($intersection_input_type_lower)
                        && $codebase->classOrInterfaceExists($intersection_container_type_lower)
                        && $codebase->classExtendsOrImplements(
                            $intersection_input_type_lower,
                            $intersection_container_type_lower
                        )
                    ) {
                        if ($container_was_static && !$input_was_static) {
                            if ($atomic_comparison_result) {
                                $atomic_comparison_result->type_coerced = true;
                            }

                            continue;
                        }

                        continue 2;
                    }

                    if ($input_type_is_interface
                        && $codebase->interfaceExtends(
                            $intersection_input_type_lower,
                            $intersection_container_type_lower
                        )
                    ) {
                        continue 2;
                    }
                }

                if (ExpressionAnalyzer::isMock($intersection_input_type_lower)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
}
