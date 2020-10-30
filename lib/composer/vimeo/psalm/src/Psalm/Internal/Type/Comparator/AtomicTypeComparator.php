<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use function get_class;
use function array_merge;
use function strtolower;
use function array_values;
use function count;

/**
 * @internal
 */
class AtomicTypeComparator
{
    /**
     * Does the input param atomic type match the given param atomic type
     */
    public static function isContainedBy(
        Codebase $codebase,
        Type\Atomic $input_type_part,
        Type\Atomic $container_type_part,
        bool $allow_interface_equality = false,
        bool $allow_float_int_equality = true,
        ?TypeComparisonResult $atomic_comparison_result = null
    ) : bool {

        if (($container_type_part instanceof TTemplateParam
                || ($container_type_part instanceof TNamedObject
                    && isset($container_type_part->extra_types)))
            && ($input_type_part instanceof TTemplateParam
                || ($input_type_part instanceof TNamedObject
                    && isset($input_type_part->extra_types)))
        ) {
            return ObjectComparator::isShallowlyContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result
            );
        }

        if ($container_type_part instanceof TMixed
            || ($container_type_part instanceof TTemplateParam
                && $container_type_part->as->isMixed()
                && !$container_type_part->extra_types
                && $input_type_part instanceof TMixed)
        ) {
            if (get_class($container_type_part) === TEmptyMixed::class
                && get_class($input_type_part) === TMixed::class
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->type_coerced_from_mixed = true;
                }

                return false;
            }

            return true;
        }

        if ($input_type_part instanceof TNever || $input_type_part instanceof Type\Atomic\TEmpty) {
            return true;
        }

        if ($input_type_part instanceof TMixed
            || ($input_type_part instanceof TTemplateParam
                && $input_type_part->as->isMixed()
                && !$input_type_part->extra_types)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;
            }

            return false;
        }

        if ($input_type_part instanceof TNull) {
            if ($container_type_part instanceof TNull) {
                return true;
            }

            if ($container_type_part instanceof TTemplateParam
                && ($container_type_part->as->isNullable() || $container_type_part->as->isMixed())
            ) {
                return true;
            }

            return false;
        }

        if ($container_type_part instanceof TNull) {
            return false;
        }

        if ($input_type_part instanceof Scalar && $container_type_part instanceof Scalar) {
            return ScalarTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $allow_float_int_equality,
                $atomic_comparison_result
            );
        }

        if ($input_type_part instanceof Type\Atomic\TCallableKeyedArray
            && $container_type_part instanceof TArray
        ) {
            return ArrayTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result
            );
        }

        if (($container_type_part instanceof Type\Atomic\TCallable
                && $input_type_part instanceof Type\Atomic\TCallable)
            || ($container_type_part instanceof Type\Atomic\TClosure
                && $input_type_part instanceof Type\Atomic\TClosure)
        ) {
            return CallableTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result
            );
        }

        if ($container_type_part instanceof Type\Atomic\TClosure && $input_type_part instanceof TCallable) {
            if (CallableTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result
            ) === false
            ) {
                return false;
            }

            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof Type\Atomic\TClosure) {
            if (!$input_type_part instanceof Type\Atomic\TClosure) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->type_coerced_from_mixed = true;
                }

                return false;
            }

            return CallableTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result
            );
        }

        if ($container_type_part instanceof TCallable && $input_type_part instanceof Type\Atomic\TClosure) {
            return CallableTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result
            );
        }

        if ($input_type_part instanceof TNamedObject &&
            $input_type_part->value === 'Closure' &&
            $container_type_part instanceof TCallable
        ) {
            return true;
        }

        if ($input_type_part instanceof TObject &&
            $container_type_part instanceof TCallable
        ) {
            return true;
        }

        if ($input_type_part instanceof Type\Atomic\TCallableObject &&
            $container_type_part instanceof TObject
        ) {
            return true;
        }

        if (($container_type_part instanceof TKeyedArray
                && $input_type_part instanceof TKeyedArray)
            || ($container_type_part instanceof TObjectWithProperties
                && $input_type_part instanceof TObjectWithProperties)
        ) {
            return KeyedArrayComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result
            );
        }

        if (($input_type_part instanceof TArray
                || $input_type_part instanceof TList
                || $input_type_part instanceof TKeyedArray
                || $input_type_part instanceof TClassStringMap)
            && ($container_type_part instanceof TArray
                || $container_type_part instanceof TList
                || $container_type_part instanceof TKeyedArray
                || $container_type_part instanceof TClassStringMap)
        ) {
            return ArrayTypeComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result
            );
        }

        if (($input_type_part instanceof TNamedObject
                || ($input_type_part instanceof TTemplateParam
                    && $input_type_part->as->hasObjectType())
                || $input_type_part instanceof TIterable)
            && ($container_type_part instanceof TNamedObject
                || ($container_type_part instanceof TTemplateParam
                    && $container_type_part->isObjectType())
                || $container_type_part instanceof TIterable)
            && ObjectComparator::isShallowlyContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result
            )
        ) {
            if ($container_type_part instanceof TGenericObject || $container_type_part instanceof TIterable) {
                return GenericTypeComparator::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $atomic_comparison_result
                );
            }

            if ($container_type_part instanceof TNamedObject
                && $input_type_part instanceof TNamedObject
                && $container_type_part->was_static
                && !$input_type_part->was_static
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            if ($atomic_comparison_result) {
                $atomic_comparison_result->to_string_cast = false;
            }

            return true;
        }

        if (get_class($input_type_part) === TObject::class
            && get_class($container_type_part) === TObject::class
        ) {
            return true;
        }

        if ($container_type_part instanceof TTemplateParam && $input_type_part instanceof TTemplateParam) {
            return UnionTypeComparator::isContainedBy(
                $codebase,
                $input_type_part->as,
                $container_type_part->as,
                false,
                false,
                $atomic_comparison_result,
                $allow_interface_equality
            );
        }

        if ($container_type_part instanceof TTemplateParam) {
            foreach ($container_type_part->as->getAtomicTypes() as $container_as_type_part) {
                if (self::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_as_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result
                )) {
                    if ($allow_interface_equality
                        || ($input_type_part instanceof TArray
                            && !$input_type_part->type_params[1]->isEmpty())
                        || $input_type_part instanceof TKeyedArray
                    ) {
                        return true;
                    }
                }
            }

            return false;
        }

        if ($container_type_part instanceof TConditional) {
            $atomic_types = array_merge(
                array_values($container_type_part->if_type->getAtomicTypes()),
                array_values($container_type_part->else_type->getAtomicTypes())
            );

            foreach ($atomic_types as $container_as_type_part) {
                if (self::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_as_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TTemplateParam) {
            if ($input_type_part->extra_types) {
                foreach ($input_type_part->extra_types as $extra_type) {
                    if (self::isContainedBy(
                        $codebase,
                        $extra_type,
                        $container_type_part,
                        $allow_interface_equality,
                        $allow_float_int_equality,
                        $atomic_comparison_result
                    )) {
                        return true;
                    }
                }
            }

            foreach ($input_type_part->as->getAtomicTypes() as $input_as_type_part) {
                if ($input_as_type_part instanceof TNull && $container_type_part instanceof TNull) {
                    continue;
                }

                if (self::isContainedBy(
                    $codebase,
                    $input_as_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TConditional) {
            $input_atomic_types = array_merge(
                array_values($input_type_part->if_type->getAtomicTypes()),
                array_values($input_type_part->else_type->getAtomicTypes())
            );

            foreach ($input_atomic_types as $input_as_type_part) {
                if (self::isContainedBy(
                    $codebase,
                    $input_as_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $allow_float_int_equality,
                    $atomic_comparison_result
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TNamedObject
            && $input_type_part->value === 'static'
            && $container_type_part instanceof TNamedObject
            && strtolower($container_type_part->value) === 'self'
        ) {
            return true;
        }

        if ($container_type_part instanceof TIterable) {
            if ($input_type_part instanceof TArray
                || $input_type_part instanceof TKeyedArray
                || $input_type_part instanceof TList
            ) {
                if ($input_type_part instanceof TKeyedArray) {
                    $input_type_part = $input_type_part->getGenericArrayType();
                } elseif ($input_type_part instanceof TList) {
                    $input_type_part = new TArray([Type::getInt(), $input_type_part->type_param]);
                }

                $all_types_contain = true;

                foreach ($input_type_part->type_params as $i => $input_param) {
                    $container_param_offset = $i - (2 - count($container_type_part->type_params));

                    if ($container_param_offset === -1) {
                        continue;
                    }

                    $container_param = $container_type_part->type_params[$container_param_offset];

                    if ($i === 0
                        && $input_param->hasMixed()
                        && $container_param->hasString()
                        && $container_param->hasInt()
                    ) {
                        continue;
                    }

                    $array_comparison_result = new TypeComparisonResult();

                    if (!$input_param->isEmpty()
                        && !UnionTypeComparator::isContainedBy(
                            $codebase,
                            $input_param,
                            $container_param,
                            $input_param->ignore_nullable_issues,
                            $input_param->ignore_falsable_issues,
                            $array_comparison_result,
                            $allow_interface_equality
                        )
                        && !$array_comparison_result->type_coerced_from_scalar
                    ) {
                        if ($atomic_comparison_result && $array_comparison_result->type_coerced_from_mixed) {
                            $atomic_comparison_result->type_coerced_from_mixed = true;
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

            if ($input_type_part->hasTraversableInterface($codebase)) {
                return true;
            }
        }

        if ($container_type_part instanceof TString || $container_type_part instanceof TScalar) {
            if ($input_type_part instanceof TNamedObject) {
                // check whether the object has a __toString method
                if ($codebase->classOrInterfaceExists($input_type_part->value)) {
                    if ($codebase->php_major_version >= 8
                        && ($input_type_part->value === 'Stringable'
                            || ($codebase->classlikes->classExists($input_type_part->value)
                                && $codebase->classlikes->classImplements($input_type_part->value, 'Stringable'))
                            || $codebase->classlikes->interfaceExtends($input_type_part->value, 'Stringable'))
                    ) {
                        if ($atomic_comparison_result) {
                            $atomic_comparison_result->to_string_cast = true;
                        }

                        return true;
                    }

                    if ($codebase->methods->methodExists(
                        new \Psalm\Internal\MethodIdentifier(
                            $input_type_part->value,
                            '__tostring'
                        )
                    )) {
                        if ($atomic_comparison_result) {
                            $atomic_comparison_result->to_string_cast = true;
                        }

                        return true;
                    }
                }

                // PHP 5.6 doesn't support this natively, so this introduces a bug *just* when checking PHP 5.6 code
                if ($input_type_part->value === 'ReflectionType') {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->to_string_cast = true;
                    }

                    return true;
                }
            } elseif ($input_type_part instanceof TObjectWithProperties
                && isset($input_type_part->methods['__toString'])
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->to_string_cast = true;
                }

                return true;
            }
        }

        if ($container_type_part instanceof TCallable &&
            (
                $input_type_part instanceof TLiteralString
                || $input_type_part instanceof TCallableString
                || $input_type_part instanceof TArray
                || $input_type_part instanceof TKeyedArray
                || $input_type_part instanceof TList
                || (
                    $input_type_part instanceof TNamedObject &&
                    $codebase->classOrInterfaceExists($input_type_part->value) &&
                    $codebase->methodExists($input_type_part->value . '::__invoke')
                )
            )
        ) {
            return CallableTypeComparator::isNotExplicitlyCallableTypeCallable(
                $codebase,
                $input_type_part,
                $container_type_part,
                $atomic_comparison_result
            );
        }

        if ($container_type_part instanceof TObject
            && $input_type_part instanceof TNamedObject
        ) {
            if ($container_type_part instanceof TObjectWithProperties
                && $input_type_part->value !== 'stdClass'
            ) {
                return KeyedArrayComparator::isContainedByObjectWithProperties(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $atomic_comparison_result
                );
            }

            return true;
        }

        if ($container_type_part instanceof TNamedObject
            && $input_type_part instanceof TNamedObject
            && $container_type_part->was_static
            && !$input_type_part->was_static
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($input_type_part instanceof TObject && $container_type_part instanceof TNamedObject) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TNamedObject
            && $input_type_part instanceof TNamedObject
            && $codebase->classOrInterfaceExists($input_type_part->value)
            && (
                (
                    $codebase->classExists($container_type_part->value)
                    && $codebase->classExtendsOrImplements(
                        $container_type_part->value,
                        $input_type_part->value
                    )
                )
                ||
                (
                    $codebase->interfaceExists($container_type_part->value)
                    && $codebase->interfaceExtends(
                        $container_type_part->value,
                        $input_type_part->value
                    )
                )
            )
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        return $input_type_part->getKey() === $container_type_part->getKey();
    }

    /**
     * Does the input param atomic type match the given param atomic type
     */
    public static function canBeIdentical(
        Codebase $codebase,
        Type\Atomic $type1_part,
        Type\Atomic $type2_part
    ) : bool {
        if ((get_class($type1_part) === TList::class
                && $type2_part instanceof Type\Atomic\TNonEmptyList)
            || (get_class($type2_part) === TList::class
                && $type1_part instanceof Type\Atomic\TNonEmptyList)
        ) {
            return UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $type1_part->type_param,
                $type2_part->type_param
            );
        }

        if ((get_class($type1_part) === TArray::class
                && $type2_part instanceof Type\Atomic\TNonEmptyArray)
            || (get_class($type2_part) === TArray::class
                && $type1_part instanceof Type\Atomic\TNonEmptyArray)
        ) {
            return UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $type1_part->type_params[0],
                $type2_part->type_params[0]
            )
            && UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $type1_part->type_params[1],
                $type2_part->type_params[1]
            );
        }

        $first_comparison_result = new TypeComparisonResult();
        $second_comparison_result = new TypeComparisonResult();

        $either_contains = (AtomicTypeComparator::isContainedBy(
            $codebase,
            $type1_part,
            $type2_part,
            true,
            false,
            $first_comparison_result
        )
            && !$first_comparison_result->to_string_cast
        ) || (AtomicTypeComparator::isContainedBy(
            $codebase,
            $type2_part,
            $type1_part,
            true,
            false,
            $second_comparison_result
        )
            && !$second_comparison_result->to_string_cast
        ) || ($first_comparison_result->type_coerced
            && $second_comparison_result->type_coerced
        );

        if ($either_contains) {
            return true;
        }

        return false;
    }
}
