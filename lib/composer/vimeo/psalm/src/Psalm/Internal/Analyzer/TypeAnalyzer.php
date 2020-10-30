<?php
namespace Psalm\Internal\Analyzer;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Type;

use function array_merge;
use function array_keys;
use function array_unique;

/**
 * @internal
 */
class TypeAnalyzer
{
    /**
     * Does the input param type match the given param type
     *
     * @deprecated in favour of UnionTypeComparator
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function isContainedBy(
        Codebase $codebase,
        Type\Union $input_type,
        Type\Union $container_type,
        bool $ignore_null = false,
        bool $ignore_false = false,
        ?TypeComparisonResult $union_comparison_result = null,
        bool $allow_interface_equality = false
    ) : bool {
        return UnionTypeComparator::isContainedBy(
            $codebase,
            $input_type,
            $container_type,
            $ignore_null,
            $ignore_false,
            $union_comparison_result,
            $allow_interface_equality
        );
    }

    /**
     * Does the input param atomic type match the given param atomic type
     *
     * @deprecated in favour of AtomicTypeComparator
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function isAtomicContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        bool $allow_interface_equality = false,
        bool $allow_float_int_equality = true,
        ?TypeComparisonResult $atomic_comparison_result = null
    ) : bool {
        return AtomicTypeComparator::isContainedBy(
            $codebase,
            $input_type_part,
            $container_type_part,
            $allow_interface_equality,
            $allow_float_int_equality,
            $atomic_comparison_result
        );
    }

    /**
     * Takes two arrays of types and merges them
     *
     * @param  array<string, Type\Union>  $new_types
     * @param  array<string, Type\Union>  $existing_types
     *
     * @return array<string, Type\Union>
     */
    public static function combineKeyedTypes(array $new_types, array $existing_types): array
    {
        $keys = array_merge(array_keys($new_types), array_keys($existing_types));
        $keys = array_unique($keys);

        $result_types = [];

        if (empty($new_types)) {
            return $existing_types;
        }

        if (empty($existing_types)) {
            return $new_types;
        }

        foreach ($keys as $key) {
            if (!isset($existing_types[$key])) {
                $result_types[$key] = $new_types[$key];
                continue;
            }

            if (!isset($new_types[$key])) {
                $result_types[$key] = $existing_types[$key];
                continue;
            }

            $existing_var_types = $existing_types[$key];
            $new_var_types = $new_types[$key];

            if ($new_var_types->getId() === $existing_var_types->getId()) {
                $result_types[$key] = $new_var_types;
            } else {
                $result_types[$key] = Type::combineUnionTypes($new_var_types, $existing_var_types);
            }
        }

        return $result_types;
    }
}
