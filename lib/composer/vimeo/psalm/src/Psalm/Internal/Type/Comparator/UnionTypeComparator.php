<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use function get_class;
use function array_merge;

/**
 * @internal
 */
class UnionTypeComparator
{
    /**
     * Does the input param type match the given param type
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
        if ($union_comparison_result) {
            $union_comparison_result->scalar_type_match_found = true;
        }

        if ($input_type->possibly_undefined
            && !$input_type->possibly_undefined_from_try
            && !$container_type->possibly_undefined
        ) {
            return false;
        }

        if ($container_type->hasMixed() && !$container_type->isEmptyMixed()) {
            return true;
        }

        $container_has_template = $container_type->hasTemplateOrStatic();

        $input_atomic_types = \array_reverse($input_type->getAtomicTypes());

        while ($input_type_part = \array_pop($input_atomic_types)) {
            if ($input_type_part instanceof TNull && $ignore_null) {
                continue;
            }

            if ($input_type_part instanceof TFalse && $ignore_false) {
                continue;
            }

            if ($input_type_part instanceof TTemplateParam
                && !$container_has_template
                && !$input_type_part->extra_types
            ) {
                $input_atomic_types = array_merge($input_type_part->as->getAtomicTypes(), $input_atomic_types);
                continue;
            }

            $type_match_found = false;
            $scalar_type_match_found = false;
            $all_to_string_cast = true;

            $all_type_coerced = null;
            $all_type_coerced_from_mixed = null;
            $all_type_coerced_from_as_mixed = null;

            $some_type_coerced = false;
            $some_type_coerced_from_mixed = false;

            if ($input_type_part instanceof TArrayKey
                && ($container_type->hasInt() && $container_type->hasString())
            ) {
                continue;
            }

            foreach ($container_type->getAtomicTypes() as $container_type_part) {
                if ($ignore_null
                    && $container_type_part instanceof TNull
                    && !$input_type_part instanceof TNull
                ) {
                    continue;
                }

                if ($ignore_false
                    && $container_type_part instanceof TFalse
                    && !$input_type_part instanceof TFalse
                ) {
                    continue;
                }

                if ($union_comparison_result) {
                    $atomic_comparison_result = new TypeComparisonResult();
                } else {
                    $atomic_comparison_result = null;
                }

                $is_atomic_contained_by = AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    true,
                    $atomic_comparison_result
                );

                if ($input_type_part instanceof TMixed
                    && $input_type->from_template_default
                    && $input_type->from_docblock
                    && $atomic_comparison_result
                    && $atomic_comparison_result->type_coerced_from_mixed
                ) {
                    $atomic_comparison_result->type_coerced_from_as_mixed = true;
                }

                if ($atomic_comparison_result) {
                    if ($atomic_comparison_result->scalar_type_match_found !== null) {
                        $scalar_type_match_found = $atomic_comparison_result->scalar_type_match_found;
                    }

                    if ($union_comparison_result
                        && $atomic_comparison_result->type_coerced_from_scalar !== null
                    ) {
                        $union_comparison_result->type_coerced_from_scalar
                            = $atomic_comparison_result->type_coerced_from_scalar;
                    }

                    if ($is_atomic_contained_by
                        && $union_comparison_result
                        && $atomic_comparison_result->replacement_atomic_type
                    ) {
                        if (!$union_comparison_result->replacement_union_type) {
                            $union_comparison_result->replacement_union_type = clone $input_type;
                        }

                        $union_comparison_result->replacement_union_type->removeType($input_type->getKey());

                        $union_comparison_result->replacement_union_type->addType(
                            $atomic_comparison_result->replacement_atomic_type
                        );
                    }
                }

                if ($input_type_part instanceof TNumeric
                    && $container_type->hasString()
                    && $container_type->hasInt()
                    && $container_type->hasFloat()
                ) {
                    $scalar_type_match_found = false;
                    $is_atomic_contained_by = true;
                }

                if ($atomic_comparison_result) {
                    if ($atomic_comparison_result->type_coerced) {
                        $some_type_coerced = true;
                    }

                    if ($atomic_comparison_result->type_coerced_from_mixed) {
                        $some_type_coerced_from_mixed = true;
                    }

                    if ($atomic_comparison_result->type_coerced !== true || $all_type_coerced === false) {
                        $all_type_coerced = false;
                    } else {
                        $all_type_coerced = true;
                    }

                    if ($atomic_comparison_result->type_coerced_from_mixed !== true
                        || $all_type_coerced_from_mixed === false
                    ) {
                        $all_type_coerced_from_mixed = false;
                    } else {
                        $all_type_coerced_from_mixed = true;
                    }

                    if ($atomic_comparison_result->type_coerced_from_as_mixed !== true
                        || $all_type_coerced_from_as_mixed === false
                    ) {
                        $all_type_coerced_from_as_mixed = false;
                    } else {
                        $all_type_coerced_from_as_mixed = true;
                    }
                }

                if ($is_atomic_contained_by) {
                    $type_match_found = true;

                    if ($atomic_comparison_result) {
                        if ($atomic_comparison_result->to_string_cast !== true) {
                            $all_to_string_cast = false;
                        }
                    }

                    $all_type_coerced_from_mixed = false;
                    $all_type_coerced_from_as_mixed = false;
                    $all_type_coerced = false;
                }
            }

            if ($union_comparison_result) {
                // only set this flag if we're definite that the only
                // reason the type match has been found is because there
                // was a __toString cast
                if ($all_to_string_cast && $type_match_found) {
                    $union_comparison_result->to_string_cast = true;
                }

                if ($all_type_coerced) {
                    $union_comparison_result->type_coerced = true;
                }

                if ($all_type_coerced_from_mixed) {
                    $union_comparison_result->type_coerced_from_mixed = true;

                    if (($input_type->from_template_default && $input_type->from_docblock)
                        || $all_type_coerced_from_as_mixed
                    ) {
                        $union_comparison_result->type_coerced_from_as_mixed = true;
                    }
                }
            }

            if (!$type_match_found) {
                if ($union_comparison_result) {
                    if ($some_type_coerced) {
                        $union_comparison_result->type_coerced = true;
                    }

                    if ($some_type_coerced_from_mixed) {
                        $union_comparison_result->type_coerced_from_mixed = true;

                        if (($input_type->from_template_default && $input_type->from_docblock)
                            || $all_type_coerced_from_as_mixed
                        ) {
                            $union_comparison_result->type_coerced_from_as_mixed = true;
                        }
                    }

                    if (!$scalar_type_match_found) {
                        $union_comparison_result->scalar_type_match_found = false;
                    }
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Used for comparing signature typehints, uses PHP's light contravariance rules
     *
     *
     */
    public static function isContainedByInPhp(
        ?Type\Union $input_type,
        Type\Union $container_type
    ): bool {
        if (!$input_type) {
            return false;
        }

        if ($input_type->getId() === $container_type->getId()) {
            return true;
        }

        if ($input_type->isNullable() && !$container_type->isNullable()) {
            return false;
        }

        $input_type_not_null = clone $input_type;
        $input_type_not_null->removeType('null');

        $container_type_not_null = clone $container_type;
        $container_type_not_null->removeType('null');

        if ($input_type_not_null->getId() === $container_type_not_null->getId()) {
            return true;
        }

        if ($input_type_not_null->hasArray() && $container_type_not_null->hasType('iterable')) {
            return true;
        }

        return false;
    }

    /**
     * Used for comparing docblock types to signature types before we know about all types
     *
     */
    public static function isSimplyContainedBy(
        Type\Union $input_type,
        Type\Union $container_type
    ) : bool {
        if ($input_type->getId() === $container_type->getId()) {
            return true;
        }

        if ($input_type->isNullable() && !$container_type->isNullable()) {
            return false;
        }

        $input_type_not_null = clone $input_type;
        $input_type_not_null->removeType('null');

        $container_type_not_null = clone $container_type;
        $container_type_not_null->removeType('null');

        foreach ($input_type->getAtomicTypes() as $input_key => $input_type_part) {
            foreach ($container_type->getAtomicTypes() as $container_key => $container_type_part) {
                if (get_class($container_type_part) === TNamedObject::class
                    && $input_type_part instanceof TNamedObject
                    && $input_type_part->value === $container_type_part->value
                ) {
                    continue 2;
                }

                if ($input_key === $container_key) {
                    continue 2;
                }
            }

            return false;
        }



        return true;
    }

    /**
     * Does the input param type match the given param type
     */
    public static function canBeContainedBy(
        Codebase $codebase,
        Type\Union $input_type,
        Type\Union $container_type,
        bool $ignore_null = false,
        bool $ignore_false = false,
        array &$matching_input_keys = []
    ): bool {
        if ($container_type->hasMixed()) {
            return true;
        }

        if ($input_type->possibly_undefined && !$container_type->possibly_undefined) {
            return false;
        }

        foreach ($container_type->getAtomicTypes() as $container_type_part) {
            if ($container_type_part instanceof TNull && $ignore_null) {
                continue;
            }

            if ($container_type_part instanceof TFalse && $ignore_false) {
                continue;
            }

            foreach ($input_type->getAtomicTypes() as $input_type_part) {
                $atomic_comparison_result = new TypeComparisonResult();
                $is_atomic_contained_by = AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    false,
                    false,
                    $atomic_comparison_result
                );

                if (($is_atomic_contained_by && !$atomic_comparison_result->to_string_cast)
                    || $atomic_comparison_result->type_coerced_from_mixed
                ) {
                    $matching_input_keys[$input_type_part->getKey()] = true;
                }
            }
        }

        return !!$matching_input_keys;
    }

    /**
     * Can any part of the $type1 be equal to any part of $type2
     *
     */
    public static function canExpressionTypesBeIdentical(
        Codebase $codebase,
        Type\Union $type1,
        Type\Union $type2
    ): bool {
        if ($type1->hasMixed() || $type2->hasMixed()) {
            return true;
        }

        if ($type1->isNullable() && $type2->isNullable()) {
            return true;
        }

        foreach ($type1->getAtomicTypes() as $type1_part) {
            foreach ($type2->getAtomicTypes() as $type2_part) {
                $either_contains = AtomicTypeComparator::canBeIdentical(
                    $codebase,
                    $type1_part,
                    $type2_part
                );

                if ($either_contains) {
                    return true;
                }
            }
        }

        return false;
    }
}
