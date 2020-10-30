<?php
namespace Psalm;

use function array_merge;
use function array_pop;
use function array_shift;
use function array_values;
use function explode;
use function implode;
use function preg_quote;
use function preg_replace;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\TypeCombination;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\Union;
use function stripos;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

abstract class Type
{
    /**
     * Parses a string type representation
     *
     * @param  array{int,int}|null   $php_version
     * @param  array<string, array<string, array{Type\Union}>> $template_type_map
     */
    public static function parseString(
        string $type_string,
        ?array $php_version = null,
        array $template_type_map = []
    ): Union {
        return TypeParser::parseTokens(
            TypeTokenizer::tokenize(
                $type_string
            ),
            $php_version,
            $template_type_map
        );
    }

    public static function getFQCLNFromString(
        string $class,
        Aliases $aliases
    ) : string {
        if ($class === '') {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

        if ($class[0] === '\\') {
            return substr($class, 1);
        }

        $imported_namespaces = $aliases->uses;

        if (strpos($class, '\\') !== false) {
            $class_parts = explode('\\', $class);
            $first_namespace = array_shift($class_parts);

            if (isset($imported_namespaces[strtolower($first_namespace)])) {
                return $imported_namespaces[strtolower($first_namespace)] . '\\' . implode('\\', $class_parts);
            }
        } elseif (isset($imported_namespaces[strtolower($class)])) {
            return $imported_namespaces[strtolower($class)];
        }

        $namespace = $aliases->namespace;

        return ($namespace ? $namespace . '\\' : '') . $class;
    }

    /**
     * @param array<string, string> $aliased_classes
     *
     * @psalm-pure
     */
    public static function getStringFromFQCLN(
        string $value,
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $allow_self = false
    ) : string {
        if ($allow_self && $value === $this_class) {
            return 'self';
        }

        if (isset($aliased_classes[strtolower($value)])) {
            return $aliased_classes[strtolower($value)];
        }

        if ($namespace && stripos($value, $namespace . '\\') === 0) {
            $candidate = preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $value
            );

            $candidate_parts = explode('\\', $candidate);

            if (!isset($aliased_classes[strtolower($candidate_parts[0])])) {
                return $candidate;
            }
        } elseif (!$namespace && strpos($value, '\\') === false) {
            return $value;
        }

        if (strpos($value, '\\')) {
            $parts = explode('\\', $value);

            $suffix = array_pop($parts);

            while ($parts) {
                $left = implode('\\', $parts);

                if (isset($aliased_classes[strtolower($left)])) {
                    return $aliased_classes[strtolower($left)] . '\\' . $suffix;
                }

                $suffix = array_pop($parts) . '\\' . $suffix;
            }
        }

        return '\\' . $value;
    }

    public static function getInt(bool $from_calculation = false, ?int $value = null): Union
    {
        if ($value !== null) {
            $union = new Union([new TLiteralInt($value)]);
        } else {
            $union = new Union([new TInt()]);
        }

        $union->from_calculation = $from_calculation;

        return $union;
    }

    /**
     * @param int|null $value
     *
     */
    public static function getPositiveInt(bool $from_calculation = false): Union
    {
        $union = new Union([new Type\Atomic\TPositiveInt()]);
        $union->from_calculation = $from_calculation;

        return $union;
    }

    public static function getNumeric(): Union
    {
        $type = new TNumeric;

        return new Union([$type]);
    }

    public static function getString(?string $value = null): Union
    {
        $type = null;

        if ($value !== null) {
            $config = \Psalm\Config::getInstance();

            if ($config->string_interpreters) {
                foreach ($config->string_interpreters as $string_interpreter) {
                    if ($type = $string_interpreter::getTypeFromValue($value)) {
                        break;
                    }
                }
            }

            if (!$type) {
                if (strlen($value) < $config->max_string_length) {
                    $type = new TLiteralString($value);
                } else {
                    $type = new Type\Atomic\TNonEmptyString();
                }
            }
        }

        if (!$type) {
            $type = new TString();
        }

        return new Union([$type]);
    }

    public static function getSingleLetter(): Union
    {
        $type = new TSingleLetter;

        return new Union([$type]);
    }

    public static function getClassString(string $extends = 'object'): Union
    {
        return new Union([
            new TClassString(
                $extends,
                $extends === 'object'
                    ? null
                    : new TNamedObject($extends)
            ),
        ]);
    }

    public static function getLiteralClassString(string $class_type): Union
    {
        $type = new TLiteralClassString($class_type);

        return new Union([$type]);
    }

    public static function getNull(): Union
    {
        $type = new TNull;

        return new Union([$type]);
    }

    public static function getMixed(bool $from_loop_isset = false): Union
    {
        $type = new TMixed($from_loop_isset);

        return new Union([$type]);
    }

    public static function getScalar(): Union
    {
        $type = new TScalar();

        return new Union([$type]);
    }

    public static function getEmpty(): Union
    {
        $type = new TEmpty();

        return new Union([$type]);
    }

    public static function getBool(): Union
    {
        $type = new TBool;

        return new Union([$type]);
    }

    public static function getFloat(?float $value = null): Union
    {
        if ($value !== null) {
            $type = new TLiteralFloat($value);
        } else {
            $type = new TFloat();
        }

        return new Union([$type]);
    }

    public static function getObject(): Union
    {
        $type = new TObject;

        return new Union([$type]);
    }

    public static function getClosure(): Union
    {
        $type = new Type\Atomic\TClosure('Closure');

        return new Union([$type]);
    }

    public static function getArrayKey(): Union
    {
        $type = new TArrayKey();

        return new Union([$type]);
    }

    public static function getArray(): Union
    {
        $type = new TArray(
            [
                new Type\Union([new TArrayKey]),
                new Type\Union([new TMixed]),
            ]
        );

        return new Union([$type]);
    }

    public static function getEmptyArray(): Union
    {
        $array_type = new TArray(
            [
                new Type\Union([new TEmpty]),
                new Type\Union([new TEmpty]),
            ]
        );

        return new Type\Union([
            $array_type,
        ]);
    }

    public static function getList(): Union
    {
        $type = new TList(new Type\Union([new TMixed]));

        return new Union([$type]);
    }

    public static function getNonEmptyList(): Union
    {
        $type = new Type\Atomic\TNonEmptyList(new Type\Union([new TMixed]));

        return new Union([$type]);
    }

    public static function getVoid(): Union
    {
        $type = new TVoid;

        return new Union([$type]);
    }

    public static function getFalse(): Union
    {
        $type = new TFalse;

        return new Union([$type]);
    }

    public static function getTrue(): Union
    {
        $type = new TTrue;

        return new Union([$type]);
    }

    public static function getResource(): Union
    {
        return new Union([new TResource]);
    }

    /**
     * @param non-empty-list<Type\Union> $union_types
     */
    public static function combineUnionTypeArray(array $union_types, ?Codebase $codebase) : Type\Union
    {
        $first_type = array_pop($union_types);

        foreach ($union_types as $type) {
            $first_type = self::combineUnionTypes($first_type, $type, $codebase);
        }

        return $first_type;
    }

    /**
     * Combines two union types into one
     *
     * @param  int    $literal_limit any greater number of literal types than this
     *                               will be merged to a scalar
     *
     */
    public static function combineUnionTypes(
        Union $type_1,
        Union $type_2,
        ?Codebase $codebase = null,
        bool $overwrite_empty_array = false,
        bool $allow_mixed_union = true,
        int $literal_limit = 500
    ): Union {
        if ($type_1 === $type_2) {
            return $type_1;
        }

        if ($type_1->isVanillaMixed() && $type_2->isVanillaMixed()) {
            $combined_type = Type::getMixed();
        } else {
            $both_failed_reconciliation = false;

            if ($type_1->failed_reconciliation) {
                if ($type_2->failed_reconciliation) {
                    $both_failed_reconciliation = true;
                } else {
                    $type_2 = clone $type_2;
                    $type_2->parent_nodes += $type_1->parent_nodes;

                    return $type_2;
                }
            } elseif ($type_2->failed_reconciliation) {
                $type_1 = clone $type_1;
                $type_1->parent_nodes += $type_2->parent_nodes;

                return $type_1;
            }

            $combined_type = TypeCombination::combineTypes(
                array_merge(
                    array_values($type_1->getAtomicTypes()),
                    array_values($type_2->getAtomicTypes())
                ),
                $codebase,
                $overwrite_empty_array,
                $allow_mixed_union,
                $literal_limit
            );

            if (!$type_1->initialized || !$type_2->initialized) {
                $combined_type->initialized = false;
            }

            if ($type_1->possibly_undefined_from_try || $type_2->possibly_undefined_from_try) {
                $combined_type->possibly_undefined_from_try = true;
            }

            if ($type_1->from_docblock || $type_2->from_docblock) {
                $combined_type->from_docblock = true;
            }

            if ($type_1->from_calculation || $type_2->from_calculation) {
                $combined_type->from_calculation = true;
            }

            if ($type_1->ignore_nullable_issues || $type_2->ignore_nullable_issues) {
                $combined_type->ignore_nullable_issues = true;
            }

            if ($type_1->ignore_falsable_issues || $type_2->ignore_falsable_issues) {
                $combined_type->ignore_falsable_issues = true;
            }

            if ($type_1->had_template && $type_2->had_template) {
                $combined_type->had_template = true;
            }

            if ($type_1->reference_free && $type_2->reference_free) {
                $combined_type->reference_free = true;
            }

            if ($both_failed_reconciliation) {
                $combined_type->failed_reconciliation = true;
            }
        }

        if ($type_1->possibly_undefined || $type_2->possibly_undefined) {
            $combined_type->possibly_undefined = true;
        }

        if ($type_1->parent_nodes || $type_2->parent_nodes) {
            $combined_type->parent_nodes = $type_1->parent_nodes + $type_2->parent_nodes;
        }

        if ($type_1->by_ref || $type_2->by_ref) {
            $combined_type->by_ref = true;
        }

        return $combined_type;
    }

    /**
     * Combines two union types into one via an intersection
     *
     *
     */
    public static function intersectUnionTypes(
        Union $type_1,
        Union $type_2,
        Codebase $codebase
    ): ?Union {
        $intersection_performed = false;

        if ($type_1->isMixed() && $type_2->isMixed()) {
            $combined_type = Type::getMixed();
        } else {
            $both_failed_reconciliation = false;

            if ($type_1->failed_reconciliation) {
                if ($type_2->failed_reconciliation) {
                    $both_failed_reconciliation = true;
                } else {
                    return $type_2;
                }
            } elseif ($type_2->failed_reconciliation) {
                return $type_1;
            }

            if ($type_1->isMixed() && !$type_2->isMixed()) {
                $combined_type = clone $type_2;
                $intersection_performed = true;
            } elseif (!$type_1->isMixed() && $type_2->isMixed()) {
                $combined_type = clone $type_1;
                $intersection_performed = true;
            } else {
                $combined_type = clone $type_1;

                foreach ($combined_type->getAtomicTypes() as $t1_key => $type_1_atomic) {
                    foreach ($type_2->getAtomicTypes() as $t2_key => $type_2_atomic) {
                        if ($type_1_atomic instanceof TNamedObject
                            && $type_2_atomic instanceof TNamedObject
                        ) {
                            if (AtomicTypeComparator::isContainedBy(
                                $codebase,
                                $type_2_atomic,
                                $type_1_atomic
                            )) {
                                $combined_type->removeType($t1_key);
                                $combined_type->addType(clone $type_2_atomic);
                                $intersection_performed = true;
                            } elseif (AtomicTypeComparator::isContainedBy(
                                $codebase,
                                $type_1_atomic,
                                $type_2_atomic
                            )) {
                                $combined_type->removeType($t2_key);
                                $combined_type->addType(clone $type_1_atomic);
                                $intersection_performed = true;
                            }
                        }

                        if (($type_1_atomic instanceof TIterable
                                || $type_1_atomic instanceof TNamedObject
                                || $type_1_atomic instanceof TTemplateParam
                                || $type_1_atomic instanceof TObjectWithProperties)
                            && ($type_2_atomic instanceof TIterable
                                || $type_2_atomic instanceof TNamedObject
                                || $type_2_atomic instanceof TTemplateParam
                                || $type_2_atomic instanceof TObjectWithProperties)
                        ) {
                            if (!$type_1_atomic->extra_types) {
                                $type_1_atomic->extra_types = [];
                            }

                            $intersection_performed = true;

                            $type_2_atomic_clone = clone $type_2_atomic;

                            $type_2_atomic_clone->extra_types = [];

                            $type_1_atomic->extra_types[$type_2_atomic_clone->getKey()] = $type_2_atomic_clone;

                            $type_2_atomic_intersection_types = $type_2_atomic->getIntersectionTypes();

                            if ($type_2_atomic_intersection_types) {
                                foreach ($type_2_atomic_intersection_types as $type_2_intersection_type) {
                                    $type_1_atomic->extra_types[$type_2_intersection_type->getKey()]
                                        = clone $type_2_intersection_type;
                                }
                            }
                        }

                        if ($type_1_atomic instanceof TObject && $type_2_atomic instanceof TNamedObject) {
                            $combined_type->removeType($t1_key);
                            $combined_type->addType(clone $type_2_atomic);
                            $intersection_performed = true;
                        } elseif ($type_2_atomic instanceof TObject && $type_1_atomic instanceof TNamedObject) {
                            $combined_type->removeType($t2_key);
                            $combined_type->addType(clone $type_1_atomic);
                            $intersection_performed = true;
                        }
                    }
                }
            }

            if (!$type_1->initialized && !$type_2->initialized) {
                $combined_type->initialized = false;
            }

            if ($type_1->possibly_undefined_from_try && $type_2->possibly_undefined_from_try) {
                $combined_type->possibly_undefined_from_try = true;
            }

            if ($type_1->from_docblock && $type_2->from_docblock) {
                $combined_type->from_docblock = true;
            }

            if ($type_1->from_calculation && $type_2->from_calculation) {
                $combined_type->from_calculation = true;
            }

            if ($type_1->ignore_nullable_issues && $type_2->ignore_nullable_issues) {
                $combined_type->ignore_nullable_issues = true;
            }

            if ($type_1->ignore_falsable_issues && $type_2->ignore_falsable_issues) {
                $combined_type->ignore_falsable_issues = true;
            }

            if ($both_failed_reconciliation) {
                $combined_type->failed_reconciliation = true;
            }
        }

        if (!$intersection_performed && $type_1->getId() !== $type_2->getId()) {
            return null;
        }

        if ($type_1->possibly_undefined && $type_2->possibly_undefined) {
            $combined_type->possibly_undefined = true;
        }

        return $combined_type;
    }
}
