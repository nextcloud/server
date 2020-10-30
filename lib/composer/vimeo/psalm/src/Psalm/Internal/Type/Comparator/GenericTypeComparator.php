<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use function count;
use function is_string;
use function array_fill;

/**
 * @internal
 */
class GenericTypeComparator
{
    /**
     * @param TGenericObject|Titerable $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        bool $allow_interface_equality = false,
        ?TypeComparisonResult $atomic_comparison_result = null
    ) : bool {
        $all_types_contain = true;

        if ($container_type_part instanceof TIterable
            && !$container_type_part->extra_types
            && !$input_type_part instanceof TIterable
        ) {
            $container_type_part = new TGenericObject(
                'Traversable',
                $container_type_part->type_params
            );
        }

        if (!$input_type_part instanceof TGenericObject && !$input_type_part instanceof TIterable) {
            if ($input_type_part instanceof TNamedObject
                && $codebase->classExists($input_type_part->value)
            ) {
                $class_storage = $codebase->classlike_storage_provider->get($input_type_part->value);

                $container_class = $container_type_part->value;

                // attempt to transform it
                if (isset($class_storage->template_type_extends[$container_class])) {
                    $extends_list = $class_storage->template_type_extends[$container_class];

                    $generic_params = [];

                    foreach ($extends_list as $key => $value) {
                        if (is_string($key)) {
                            $generic_params[] = $value;
                        }
                    }

                    if (!$generic_params) {
                        return false;
                    }

                    $input_type_part = new TGenericObject(
                        $input_type_part->value,
                        $generic_params
                    );
                }
            }

            if (!$input_type_part instanceof TGenericObject) {
                if ($input_type_part instanceof TNamedObject) {
                    $input_type_part = new TGenericObject(
                        $input_type_part->value,
                        array_fill(0, count($container_type_part->type_params), Type::getMixed())
                    );
                } else {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced = true;
                        $atomic_comparison_result->type_coerced_from_mixed = true;
                    }

                    return false;
                }
            }
        }

        $container_type_params_covariant = [];

        $input_type_params = \Psalm\Internal\Type\UnionTemplateHandler::getMappedGenericTypeParams(
            $codebase,
            $input_type_part,
            $container_type_part,
            $container_type_params_covariant
        );

        foreach ($input_type_params as $i => $input_param) {
            if (!isset($container_type_part->type_params[$i])) {
                break;
            }

            $container_param = $container_type_part->type_params[$i];

            if ($input_param->isEmpty()) {
                if ($atomic_comparison_result) {
                    if (!$atomic_comparison_result->replacement_atomic_type) {
                        $atomic_comparison_result->replacement_atomic_type = clone $input_type_part;
                    }

                    if ($atomic_comparison_result->replacement_atomic_type instanceof TGenericObject) {
                        /** @psalm-suppress PropertyTypeCoercion */
                        $atomic_comparison_result->replacement_atomic_type->type_params[$i]
                            = clone $container_param;
                    }
                }

                continue;
            }

            $param_comparison_result = new TypeComparisonResult();

            if (!UnionTypeComparator::isContainedBy(
                $codebase,
                $input_param,
                $container_param,
                $input_param->ignore_nullable_issues,
                $input_param->ignore_falsable_issues,
                $param_comparison_result,
                $allow_interface_equality
            )) {
                if ($input_type_part->value === 'Generator'
                    && $i === 2
                    && $param_comparison_result->type_coerced_from_mixed
                ) {
                    continue;
                }

                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced
                        = $param_comparison_result->type_coerced === true
                            && $atomic_comparison_result->type_coerced !== false;

                    $atomic_comparison_result->type_coerced_from_mixed
                        = $param_comparison_result->type_coerced_from_mixed === true
                            && $atomic_comparison_result->type_coerced_from_mixed !== false;

                    $atomic_comparison_result->type_coerced_from_as_mixed
                        = $param_comparison_result->type_coerced_from_as_mixed === true
                            && $atomic_comparison_result->type_coerced_from_as_mixed !== false;

                    $atomic_comparison_result->to_string_cast
                        = $param_comparison_result->to_string_cast === true
                            && $atomic_comparison_result->to_string_cast !== false;

                    $atomic_comparison_result->type_coerced_from_scalar
                        = $param_comparison_result->type_coerced_from_scalar === true
                            && $atomic_comparison_result->type_coerced_from_scalar !== false;

                    $atomic_comparison_result->scalar_type_match_found
                        = $param_comparison_result->scalar_type_match_found === true
                            && $atomic_comparison_result->scalar_type_match_found !== false;
                }

                if (!$param_comparison_result->type_coerced_from_as_mixed) {
                    $all_types_contain = false;
                }
            } elseif (!$input_type_part instanceof TIterable
                && !$container_type_part instanceof TIterable
                && !$container_param->hasTemplate()
                && !$input_param->hasTemplate()
            ) {
                if ($input_param->hasEmptyArray()
                    || $input_param->hasLiteralValue()
                ) {
                    if ($atomic_comparison_result) {
                        if (!$atomic_comparison_result->replacement_atomic_type) {
                            $atomic_comparison_result->replacement_atomic_type = clone $input_type_part;
                        }

                        if ($atomic_comparison_result->replacement_atomic_type instanceof TGenericObject) {
                            /** @psalm-suppress PropertyTypeCoercion */
                            $atomic_comparison_result->replacement_atomic_type->type_params[$i]
                                = clone $container_param;
                        }
                    }
                } else {
                    if (!($container_type_params_covariant[$i] ?? false)
                        && !$container_param->had_template
                    ) {
                        // Make sure types are basically the same
                        if (!UnionTypeComparator::isContainedBy(
                            $codebase,
                            $container_param,
                            $input_param,
                            $container_param->ignore_nullable_issues,
                            $container_param->ignore_falsable_issues,
                            $param_comparison_result,
                            $allow_interface_equality
                        ) || $param_comparison_result->type_coerced
                        ) {
                            if ($container_param->hasFormerStaticObject()
                                && $input_param->isFormerStaticObject()
                                && UnionTypeComparator::isContainedBy(
                                    $codebase,
                                    $input_param,
                                    $container_param,
                                    $container_param->ignore_nullable_issues,
                                    $container_param->ignore_falsable_issues,
                                    $param_comparison_result,
                                    $allow_interface_equality
                                )
                            ) {
                                // do nothing
                            } else {
                                if ($container_param->hasMixed() || $container_param->isArrayKey()) {
                                    if ($atomic_comparison_result) {
                                        $atomic_comparison_result->type_coerced_from_mixed = true;
                                    }
                                } else {
                                    $all_types_contain = false;
                                }

                                if ($atomic_comparison_result) {
                                    $atomic_comparison_result->type_coerced = false;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($all_types_contain) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->to_string_cast = false;
            }

            return true;
        }

        return false;
    }
}
