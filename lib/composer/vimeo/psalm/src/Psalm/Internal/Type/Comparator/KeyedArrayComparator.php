<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TNamedObject;
use function is_string;

/**
 * @internal
 */
class KeyedArrayComparator
{
    /**
     * @param TKeyedArray|TObjectWithProperties $input_type_part
     * @param TKeyedArray|TObjectWithProperties $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ) : bool {
        $all_types_contain = true;

        foreach ($container_type_part->properties as $key => $container_property_type) {
            if (!isset($input_type_part->properties[$key])) {
                if (!$container_property_type->possibly_undefined) {
                    $all_types_contain = false;
                }

                continue;
            }

            $input_property_type = $input_type_part->properties[$key];

            $property_type_comparison = new TypeComparisonResult();

            if (!$input_property_type->isEmpty()
                && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_property_type,
                    $container_property_type,
                    $input_property_type->ignore_nullable_issues,
                    $input_property_type->ignore_falsable_issues,
                    $property_type_comparison,
                    $allow_interface_equality
                )
                && !$property_type_comparison->type_coerced_from_scalar
            ) {
                $inverse_property_type_comparison = new TypeComparisonResult();

                if ($atomic_comparison_result) {
                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $container_property_type,
                        $input_property_type,
                        false,
                        false,
                        $inverse_property_type_comparison,
                        $allow_interface_equality
                    )
                    || $inverse_property_type_comparison->type_coerced_from_scalar
                    ) {
                        $atomic_comparison_result->type_coerced = true;
                    }
                }

                $all_types_contain = false;
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

    public static function isContainedByObjectWithProperties(
        Codebase $codebase,
        TNamedObject $input_type_part,
        TObjectWithProperties $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ) : bool {
        $all_types_contain = true;

        foreach ($container_type_part->properties as $property_name => $container_property_type) {
            if (!is_string($property_name)) {
                continue;
            }

            if (!$codebase->classlikes->classOrInterfaceExists($input_type_part->value)) {
                $all_types_contain = false;

                continue;
            }

            if (!$codebase->properties->propertyExists(
                $input_type_part->value . '::$' . $property_name,
                true
            )) {
                $all_types_contain = false;

                continue;
            }

            $property_declaring_class = (string) $codebase->properties->getDeclaringClassForProperty(
                $input_type_part . '::$' . $property_name,
                true
            );

            $class_storage = $codebase->classlike_storage_provider->get($property_declaring_class);

            $input_property_storage = $class_storage->properties[$property_name];

            $input_property_type = $input_property_storage->type ?: Type::getMixed();

            $property_type_comparison = new TypeComparisonResult();

            if (!$input_property_type->isEmpty()
                && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_property_type,
                    $container_property_type,
                    false,
                    false,
                    $property_type_comparison,
                    $allow_interface_equality
                )
                && !$property_type_comparison->type_coerced_from_scalar
            ) {
                $inverse_property_type_comparison = new TypeComparisonResult();

                if (UnionTypeComparator::isContainedBy(
                    $codebase,
                    $container_property_type,
                    $input_property_type,
                    false,
                    false,
                    $inverse_property_type_comparison,
                    $allow_interface_equality
                )
                || $inverse_property_type_comparison->type_coerced_from_scalar
                ) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced = true;
                    }
                }

                $all_types_contain = false;
            }
        }

        return $all_types_contain;
    }
}
