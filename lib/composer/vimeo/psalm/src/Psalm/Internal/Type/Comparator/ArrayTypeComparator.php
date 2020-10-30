<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;

/**
 * @internal
 */
class ArrayTypeComparator
{
    /**
     * @param TArray|TKeyedArray|TList|TClassStringMap $input_type_part
     * @param TArray|TKeyedArray|TList|TClassStringMap $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ) : bool {
        $all_types_contain = true;

        if ($container_type_part instanceof TKeyedArray
            && $input_type_part instanceof TArray
        ) {
            $all_string_int_literals = true;

            $properties = [];

            foreach ($input_type_part->type_params[0]->getAtomicTypes() as $atomic_key_type) {
                if ($atomic_key_type instanceof TLiteralString || $atomic_key_type instanceof TLiteralInt) {
                    $properties[$atomic_key_type->value] = clone $input_type_part->type_params[1];
                    $properties[$atomic_key_type->value]->possibly_undefined = true;
                } else {
                    $all_string_int_literals = false;
                }
            }

            if ($all_string_int_literals && $properties) {
                $input_type_part = new TKeyedArray($properties);

                return KeyedArrayComparator::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $atomic_comparison_result
                );
            }
        }

        if ($container_type_part instanceof TList
            && $input_type_part instanceof TKeyedArray
        ) {
            if ($input_type_part->is_list) {
                $input_type_part = $input_type_part->getList();
            } else {
                return false;
            }
        }

        if ($container_type_part instanceof TList
            && $input_type_part instanceof TClassStringMap
        ) {
            return false;
        }

        if ($container_type_part instanceof TList
            && $input_type_part instanceof TArray
            && $input_type_part->type_params[1]->isEmpty()
        ) {
            return !$container_type_part instanceof TNonEmptyList;
        }

        if ($input_type_part instanceof TList
            && $container_type_part instanceof TList
        ) {
            if (!UnionTypeComparator::isContainedBy(
                $codebase,
                $input_type_part->type_param,
                $container_type_part->type_param,
                $input_type_part->type_param->ignore_nullable_issues,
                $input_type_part->type_param->ignore_falsable_issues,
                $atomic_comparison_result,
                $allow_interface_equality
            )) {
                return false;
            }

            return $input_type_part instanceof TNonEmptyList
                || !$container_type_part instanceof TNonEmptyList;
        }

        if ($container_type_part instanceof TKeyedArray) {
            $generic_container_type_part = $container_type_part->getGenericArrayType();

            $container_type_part = $generic_container_type_part;
        }

        if ($input_type_part instanceof TKeyedArray) {
            $input_type_part = $input_type_part->getGenericArrayType();
        }

        if ($input_type_part instanceof TClassStringMap) {
            $input_type_part = new TArray([
                $input_type_part->getStandinKeyParam(),
                clone $input_type_part->value_param
            ]);
        }

        if ($container_type_part instanceof TClassStringMap) {
            $container_type_part = new TArray([
                $container_type_part->getStandinKeyParam(),
                clone $container_type_part->value_param
            ]);
        }

        if ($container_type_part instanceof TList) {
            $all_types_contain = false;

            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            $container_type_part = new TArray([Type::getInt(), clone $container_type_part->type_param]);
        }

        if ($input_type_part instanceof TList) {
            if ($input_type_part instanceof TNonEmptyList) {
                $input_type_part = new TNonEmptyArray([Type::getInt(), clone $input_type_part->type_param]);
            } else {
                $input_type_part = new TArray([Type::getInt(), clone $input_type_part->type_param]);
            }
        }

        foreach ($input_type_part->type_params as $i => $input_param) {
            if ($i > 1) {
                break;
            }

            $container_param = $container_type_part->type_params[$i];

            if ($i === 0
                && $input_param->hasMixed()
                && $container_param->hasString()
                && $container_param->hasInt()
            ) {
                continue;
            }

            if ($input_param->isEmpty()
                && $container_type_part instanceof Type\Atomic\TNonEmptyArray
            ) {
                return false;
            }

            $param_comparison_result = new TypeComparisonResult();

            if (!$input_param->isEmpty() &&
                !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_param,
                    $container_param,
                    $input_param->ignore_nullable_issues,
                    $input_param->ignore_falsable_issues,
                    $param_comparison_result,
                    $allow_interface_equality
                )
            ) {
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
            }
        }

        if ($container_type_part instanceof Type\Atomic\TNonEmptyArray
            && !$input_type_part instanceof Type\Atomic\TNonEmptyArray
        ) {
            if ($all_types_contain && $atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
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
