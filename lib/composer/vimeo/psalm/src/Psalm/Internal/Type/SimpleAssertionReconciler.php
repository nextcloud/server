<?php
namespace Psalm\Internal\Type;

use function array_filter;
use function explode;
use function get_class;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ParadoxicalCondition;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableList;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use function strpos;
use function substr;

class SimpleAssertionReconciler extends \Psalm\Type\Reconciler
{
    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    public static function reconcile(
        string $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key = null,
        bool $negated = false,
        ?CodeLocation $code_location = null,
        array $suppressed_issues = [],
        int &$failed_reconciliation = 0,
        bool $is_equality = false,
        bool $is_strict_equality = false,
        bool $inside_loop = false
    ) : ?Type\Union {
        if ($assertion === 'mixed' && $existing_var_type->hasMixed()) {
            return $existing_var_type;
        }

        if ($assertion === 'isset') {
            $existing_var_type->removeType('null');

            if (empty($existing_var_type->getAtomicTypes())) {
                $failed_reconciliation = 2;

                if ($code_location) {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            'Cannot resolve types for ' . $key . ' on null var',
                            $code_location,
                            null
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }
                }

                return Type::getEmpty();
            }

            if ($existing_var_type->hasType('empty')) {
                $existing_var_type->removeType('empty');
                $existing_var_type->addType(new TMixed($inside_loop));
            }

            $existing_var_type->possibly_undefined = false;
            $existing_var_type->possibly_undefined_from_try = false;

            return $existing_var_type;
        }

        if ($assertion === 'array-key-exists') {
            $existing_var_type->possibly_undefined = false;

            return $existing_var_type;
        }

        if (substr($assertion, 0, 9) === 'in-array-') {
            return self::reconcileInArray(
                $codebase,
                $existing_var_type,
                substr($assertion, 9)
            );
        }

        if (substr($assertion, 0, 14) === 'has-array-key-') {
            return self::reconcileHasArrayKey(
                $existing_var_type,
                substr($assertion, 14)
            );
        }

        if ($assertion === 'falsy' || $assertion === 'empty') {
            return self::reconcileFalsyOrEmpty(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation
            );
        }

        if ($assertion === 'object') {
            return self::reconcileObject(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'resource') {
            return self::reconcileResource(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'callable') {
            return self::reconcileCallable(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'iterable') {
            return self::reconcileIterable(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'array') {
            return self::reconcileArray(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'list') {
            return self::reconcileList(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                false
            );
        }

        if ($assertion === 'non-empty-list') {
            return self::reconcileList(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                true
            );
        }

        if ($assertion === 'Traversable') {
            return self::reconcileTraversable(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'countable') {
            return self::reconcileCountable(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'string-array-access') {
            return self::reconcileStringArrayAccess(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $inside_loop
            );
        }

        if ($assertion === 'int-or-string-array-access') {
            return self::reconcileIntArrayAccess(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $inside_loop
            );
        }

        if ($assertion === 'numeric') {
            return self::reconcileNumeric(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'scalar') {
            return self::reconcileScalar(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'bool') {
            return self::reconcileBool(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'string') {
            return self::reconcileString(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                $is_strict_equality
            );
        }

        if ($assertion === 'int') {
            return self::reconcileInt(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                $is_strict_equality
            );
        }

        if ($assertion === 'positive-numeric') {
            return self::reconcilePositiveNumeric(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'float'
            && $existing_var_type->from_calculation
            && $existing_var_type->hasInt()
        ) {
            return Type::getFloat();
        }

        if ($assertion === 'non-empty-countable') {
            return self::reconcileNonEmptyCountable(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                null
            );
        }

        if (substr($assertion, 0, 13) === 'has-at-least-') {
            return self::reconcileNonEmptyCountable(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                (int) substr($assertion, 13)
            );
        }

        if (substr($assertion, 0, 12) === 'has-exactly-') {
            /** @psalm-suppress ArgumentTypeCoercion */
            return self::reconcileExactlyCountable(
                $existing_var_type,
                (int) substr($assertion, 12)
            );
        }

        if (substr($assertion, 0, 10) === 'hasmethod-') {
            return self::reconcileHasMethod(
                $codebase,
                substr($assertion, 10),
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation
            );
        }

        if ($existing_var_type->isSingle()
            && $existing_var_type->hasTemplate()
            && strpos($assertion, '-') === false
            && strpos($assertion, '(') === false
        ) {
            foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TTemplateParam) {
                    if ($atomic_type->as->hasMixed()
                        || $atomic_type->as->hasObject()
                    ) {
                        $atomic_type->as = Type::parseString($assertion);

                        return $existing_var_type;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileNonEmptyCountable(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        ?int $min_count
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        if ($existing_var_type->hasType('array')) {
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];
            $did_remove_type = false;

            if ($array_atomic_type instanceof TArray) {
                if (!$array_atomic_type instanceof Type\Atomic\TNonEmptyArray
                    || ($array_atomic_type->count < $min_count)
                ) {
                    if ($array_atomic_type->getId() === 'array<empty, empty>') {
                        $existing_var_type->removeType('array');
                    } else {
                        $non_empty_array = new Type\Atomic\TNonEmptyArray(
                            $array_atomic_type->type_params
                        );

                        if ($min_count) {
                            $non_empty_array->count = $min_count;
                        }

                        $existing_var_type->addType($non_empty_array);
                    }

                    $did_remove_type = true;
                }
            } elseif ($array_atomic_type instanceof TList) {
                if (!$array_atomic_type instanceof Type\Atomic\TNonEmptyList
                    || ($array_atomic_type->count < $min_count)
                ) {
                    $non_empty_list = new Type\Atomic\TNonEmptyList(
                        $array_atomic_type->type_param
                    );

                    if ($min_count) {
                        $non_empty_list->count = $min_count;
                    }

                    $did_remove_type = true;
                    $existing_var_type->addType($non_empty_list);
                }
            } elseif ($array_atomic_type instanceof Type\Atomic\TKeyedArray) {
                foreach ($array_atomic_type->properties as $property_type) {
                    if ($property_type->possibly_undefined) {
                        $did_remove_type = true;
                        break;
                    }
                }
            }

            if (!$is_equality
                && !$existing_var_type->hasMixed()
                && (!$did_remove_type || empty($existing_var_type->getAtomicTypes()))
            ) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        'non-empty-countable',
                        !$did_remove_type,
                        $negated,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     * @param   positive-int $count
     */
    private static function reconcileExactlyCountable(
        Union $existing_var_type,
        int $count
    ) : Union {
        if ($existing_var_type->hasType('array')) {
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];

            if ($array_atomic_type instanceof TArray) {
                $non_empty_array = new Type\Atomic\TNonEmptyArray(
                    $array_atomic_type->type_params
                );

                $non_empty_array->count = $count;

                $existing_var_type->addType(
                    $non_empty_array
                );
            } elseif ($array_atomic_type instanceof TList) {
                $non_empty_list = new Type\Atomic\TNonEmptyList(
                    $array_atomic_type->type_param
                );

                $non_empty_list->count = $count;

                $existing_var_type->addType(
                    $non_empty_list
                );
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcilePositiveNumeric(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        $did_remove_type = false;

        $positive_types = [];

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof Type\Atomic\TLiteralInt) {
                if ($atomic_type->value < 1) {
                    $did_remove_type = true;
                } else {
                    $positive_types[] = $atomic_type;
                }
            } elseif ($atomic_type instanceof Type\Atomic\TPositiveInt) {
                $positive_types[] = $atomic_type;
            } elseif (get_class($atomic_type) === TInt::class) {
                $positive_types[] = new Type\Atomic\TPositiveInt();
                $did_remove_type = true;
            } else {
                // for now allow this check everywhere else
                if (!$atomic_type instanceof Type\Atomic\TNull
                    && !$atomic_type instanceof TFalse
                ) {
                    $positive_types[] = $atomic_type;
                }

                $did_remove_type = true;
            }
        }

        if (!$is_equality
            && !$existing_var_type->hasMixed()
            && (!$did_remove_type || !$positive_types)
        ) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'positive-numeric',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($positive_types) {
            return new Type\Union($positive_types);
        }

        $failed_reconciliation = 2;

        return Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileHasMethod(
        Codebase $codebase,
        string $method_name,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $object_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TNamedObject
                && $codebase->classOrInterfaceExists($type->value)
            ) {
                $object_types[] = $type;

                if (!$codebase->methodExists($type->value . '::' . $method_name)) {
                    $match_found = false;

                    if ($type->extra_types) {
                        foreach ($type->extra_types as $extra_type) {
                            if ($extra_type instanceof TNamedObject
                                && $codebase->classOrInterfaceExists($extra_type->value)
                                && $codebase->methodExists($extra_type->value . '::' . $method_name)
                            ) {
                                $match_found = true;
                            } elseif ($extra_type instanceof Atomic\TObjectWithProperties) {
                                $match_found = true;

                                if (!isset($extra_type->methods[$method_name])) {
                                    $extra_type->methods[$method_name] = 'object::' . $method_name;
                                    $did_remove_type = true;
                                }
                            }
                        }
                    }

                    if (!$match_found) {
                        $obj = new Atomic\TObjectWithProperties(
                            [],
                            [$method_name => $type->value . '::' . $method_name]
                        );
                        $type->extra_types[$obj->getKey()] = $obj;
                        $did_remove_type = true;
                    }
                }
            } elseif ($type instanceof Atomic\TObjectWithProperties) {
                $object_types[] = $type;

                if (!isset($type->methods[$method_name])) {
                    $type->methods[$method_name] = 'object::' . $method_name;
                    $did_remove_type = true;
                }
            } elseif ($type instanceof TObject || $type instanceof TMixed) {
                $object_types[] = new Atomic\TObjectWithProperties(
                    [],
                    [$method_name =>  'object::' . $method_name]
                );
                $did_remove_type = true;
            } elseif ($type instanceof TString) {
                // we don’t know
                $object_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                $object_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if (!$object_types || !$did_remove_type) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'object with method ' . $method_name,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($object_types) {
            return new Type\Union($object_types);
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileString(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        bool $is_strict_equality
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed()) {
            if ($is_equality && !$is_strict_equality) {
                return $existing_var_type;
            }

            return Type::getString();
        }

        $string_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TString) {
                $string_types[] = $type;

                if (get_class($type) === TString::class) {
                    $type->from_docblock = false;
                }
            } elseif ($type instanceof TCallable) {
                $string_types[] = new Type\Atomic\TCallableString;
                $did_remove_type = true;
            } elseif ($type instanceof TNumeric) {
                $string_types[] = new TNumericString;
                $did_remove_type = true;
            } elseif ($type instanceof TScalar || $type instanceof TArrayKey) {
                $string_types[] = new TString;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasString() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileString(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                        $is_strict_equality
                    );

                    $string_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$string_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'string',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($string_types) {
            return new Type\Union($string_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileInt(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        bool $is_strict_equality
    ) : Union {
        if ($existing_var_type->hasMixed()) {
            if ($is_equality && !$is_strict_equality) {
                return $existing_var_type;
            }

            return Type::getInt();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $int_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TInt) {
                $int_types[] = $type;

                if (get_class($type) === TInt::class) {
                    $type->from_docblock = false;
                }

                if ($existing_var_type->from_calculation) {
                    $did_remove_type = true;
                }
            } elseif ($type instanceof TNumeric) {
                $int_types[] = new TInt;
                $did_remove_type = true;
            } elseif ($type instanceof TScalar || $type instanceof TArrayKey) {
                $int_types[] = new TInt;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasInt() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileInt(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                        $is_strict_equality
                    );

                    $int_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$int_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'int',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($int_types) {
            return new Type\Union($int_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileBool(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        if ($existing_var_type->hasMixed()) {
            return Type::getBool();
        }

        $bool_types = [];
        $did_remove_type = false;

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TBool) {
                $bool_types[] = $type;
                $type->from_docblock = false;
            } elseif ($type instanceof TScalar) {
                $bool_types[] = new TBool;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasBool() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileBool(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $bool_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$bool_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'bool',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($bool_types) {
            return new Type\Union($bool_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileScalar(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        if ($existing_var_type->hasMixed()) {
            return Type::getScalar();
        }

        $scalar_types = [];
        $did_remove_type = false;

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof Scalar) {
                $scalar_types[] = $type;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasScalar() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileScalar(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $scalar_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$scalar_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'scalar',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($scalar_types) {
            return new Type\Union($scalar_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileNumeric(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        if ($existing_var_type->hasMixed()) {
            return Type::getNumeric();
        }

        $old_var_type_string = $existing_var_type->getId();

        $numeric_types = [];
        $did_remove_type = false;

        if ($existing_var_type->hasString()) {
            $did_remove_type = true;
            $existing_var_type->removeType('string');
            $existing_var_type->addType(new TNumericString);
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TNumeric || $type instanceof TNumericString) {
                // this is a workaround for a possible issue running
                // is_numeric($a) && is_string($a)
                $did_remove_type = true;
                $numeric_types[] = $type;
            } elseif ($type->isNumericType()) {
                $numeric_types[] = $type;
            } elseif ($type instanceof TScalar) {
                $did_remove_type = true;
                $numeric_types[] = new TNumeric();
            } elseif ($type instanceof TArrayKey) {
                $did_remove_type = true;
                $numeric_types[] = new TInt();
                $numeric_types[] = new TNumericString();
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasNumeric() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileNumeric(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $numeric_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$numeric_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'numeric',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($numeric_types) {
            return new Type\Union($numeric_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileObject(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        if ($existing_var_type->hasMixed()) {
            return Type::getObject();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $object_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isObjectType()) {
                $object_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $object_types[] = new Type\Atomic\TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam
                && $type->as->isMixed()
            ) {
                $type = clone $type;
                $type->as = Type::getObject();
                $object_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasObject() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileObject(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $object_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$object_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'object',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($object_types) {
            return new Type\Union($object_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileResource(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        if ($existing_var_type->hasMixed()) {
            return Type::getResource();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $resource_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TResource) {
                $resource_types[] = $type;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$resource_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'resource',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($resource_types) {
            return new Type\Union($resource_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileCountable(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();


        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Type\Union([
                new Type\Atomic\TArray([Type::getArrayKey(), Type::getMixed()]),
                new Type\Atomic\TNamedObject('Countable'),
            ]);
        }

        $iterable_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isCountable($codebase)) {
                $iterable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $iterable_types[] = new TNamedObject('Countable');
                $did_remove_type = true;
            } elseif ($type instanceof TNamedObject || $type instanceof Type\Atomic\TIterable) {
                $countable = new TNamedObject('Countable');
                $type->extra_types[$countable->getKey()] = $countable;
                $iterable_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$iterable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'countable',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($iterable_types) {
            return new Type\Union($iterable_types);
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileIterable(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Type\Union([new Type\Atomic\TIterable]);
        }

        $iterable_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isIterable($codebase)) {
                $iterable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $iterable_types[] = new Type\Atomic\TNamedObject('Traversable');
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$iterable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'iterable',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($iterable_types) {
            return new Type\Union($iterable_types);
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileInArray(
        Codebase $codebase,
        Union $existing_var_type,
        string $assertion
    ) : Union {
        if (strpos($assertion, '::')) {
            [$fq_classlike_name, $const_name] = explode('::', $assertion);

            $class_constant_type = $codebase->classlikes->getClassConstantType(
                $fq_classlike_name,
                $const_name,
                \ReflectionProperty::IS_PRIVATE
            );

            if ($class_constant_type) {
                foreach ($class_constant_type->getAtomicTypes() as $const_type_atomic) {
                    if ($const_type_atomic instanceof Type\Atomic\TKeyedArray
                        || $const_type_atomic instanceof Type\Atomic\TArray
                    ) {
                        if ($const_type_atomic instanceof Type\Atomic\TKeyedArray) {
                            $const_type_atomic = $const_type_atomic->getGenericArrayType();
                        }

                        if (UnionTypeComparator::isContainedBy(
                            $codebase,
                            $const_type_atomic->type_params[0],
                            $existing_var_type
                        )) {
                            return clone $const_type_atomic->type_params[0];
                        }
                    }
                }
            }
        }

        $existing_var_type->removeType('null');

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileHasArrayKey(
        Union $existing_var_type,
        string $assertion
    ) : Union {
        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof Type\Atomic\TKeyedArray) {
                $is_class_string = false;

                if (strpos($assertion, '::class')) {
                    [$assertion] = explode('::', $assertion);
                    $is_class_string = true;
                }

                if (isset($atomic_type->properties[$assertion])) {
                    $atomic_type->properties[$assertion]->possibly_undefined = false;
                } else {
                    $atomic_type->properties[$assertion] = Type::getMixed();

                    if ($is_class_string) {
                        $atomic_type->class_strings[$assertion] = true;
                    }
                }
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileTraversable(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Type\Union([new Type\Atomic\TNamedObject('Traversable')]);
        }

        $traversable_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->hasTraversableInterface($codebase)) {
                $traversable_types[] = $type;
            } elseif ($type instanceof Atomic\TIterable) {
                $clone_type = clone $type;
                $traversable_types[] = new Atomic\TGenericObject('Traversable', $clone_type->type_params);
                $did_remove_type = true;
            } elseif ($type instanceof TObject) {
                $traversable_types[] = new TNamedObject('Traversable');
                $did_remove_type = true;
            } elseif ($type instanceof TNamedObject) {
                $traversable = new TNamedObject('Traversable');
                $type->extra_types[$traversable->getKey()] = $traversable;
                $traversable_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$traversable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'Traversable',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($traversable_types) {
            return new Type\Union($traversable_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileArray(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return Type::getArray();
        }

        $array_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TArray || $type instanceof TKeyedArray || $type instanceof TList) {
                $array_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $array_types[] = new TCallableKeyedArray([
                    new Union([new TClassString, new TObject]),
                    Type::getString()
                ]);

                $did_remove_type = true;
            } elseif ($type instanceof Atomic\TIterable) {
                $clone_type = clone $type;

                self::refineArrayKey($clone_type->type_params[0]);

                $array_types[] = new TArray($clone_type->type_params);

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$array_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'array',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );

                if (!$did_remove_type) {
                    $failed_reconciliation = 1;
                }
            }
        }

        if ($array_types) {
            return \Psalm\Internal\Type\TypeCombination::combineTypes($array_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    private static function refineArrayKey(Union $key_type) : void
    {
        foreach ($key_type->getAtomicTypes() as $key => $cat) {
            if ($cat instanceof TTemplateParam) {
                self::refineArrayKey($cat->as);
                $key_type->bustCache();
            } elseif ($cat instanceof TScalar || $cat instanceof TMixed) {
                $key_type->removeType($key);
                $key_type->addType(new Type\Atomic\TArrayKey());
            } elseif (!$cat instanceof TString && !$cat instanceof TInt) {
                $key_type->removeType($key);
                $key_type->addType(new Type\Atomic\TArrayKey());
            }
        }

        if (!$key_type->getAtomicTypes()) {
            // this should ideally prompt some sort of error
            $key_type->addType(new Type\Atomic\TArrayKey());
        }
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileList(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        bool $is_non_empty
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return $is_non_empty ? Type::getNonEmptyList() : Type::getList();
        }

        $array_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TList
                || ($type instanceof TKeyedArray && $type->is_list)
            ) {
                if ($is_non_empty && $type instanceof TList && !$type instanceof TNonEmptyList) {
                    $array_types[] = new TNonEmptyList($type->type_param);
                    $did_remove_type = true;
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TArray || $type instanceof TKeyedArray) {
                if ($type instanceof TKeyedArray) {
                    $type = $type->getGenericArrayType();
                }

                if ($type->type_params[0]->hasArrayKey()
                    || $type->type_params[0]->hasInt()
                ) {
                    if ($type instanceof TNonEmptyArray) {
                        $array_types[] = new TNonEmptyList($type->type_params[1]);
                    } else {
                        $array_types[] = new TList($type->type_params[1]);
                    }
                }

                $did_remove_type = true;
            } elseif ($type instanceof TCallable) {
                $array_types[] = new TCallableKeyedArray([
                    new Union([new TClassString, new TObject]),
                    Type::getString()
                ]);

                $did_remove_type = true;
            } elseif ($type instanceof Atomic\TIterable) {
                $clone_type = clone $type;
                $array_types[] = new TList($clone_type->type_params[1]);

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$array_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'array',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );

                if (!$did_remove_type) {
                    $failed_reconciliation = 1;
                }
            }
        }

        if ($array_types) {
            return \Psalm\Internal\Type\TypeCombination::combineTypes($array_types);
        }

        $failed_reconciliation = 2;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileStringArrayAccess(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $inside_loop
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Union([
                new Atomic\TNonEmptyArray([Type::getArrayKey(), Type::getMixed()]),
                new TNamedObject('ArrayAccess'),
            ]);
        }

        $array_types = [];

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isArrayAccessibleWithStringKey($codebase)) {
                if (get_class($type) === TArray::class) {
                    $array_types[] = new Atomic\TNonEmptyArray($type->type_params);
                } elseif (get_class($type) === TList::class) {
                    $array_types[] = new Atomic\TNonEmptyList($type->type_param);
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TTemplateParam) {
                $array_types[] = $type;
            }
        }

        if (!$array_types) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'string-array-access',
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($array_types) {
            return new Type\Union($array_types);
        }

        $failed_reconciliation = 2;

        return Type::getMixed($inside_loop);
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileIntArrayAccess(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $inside_loop
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed()) {
            return Type::getMixed();
        }

        $array_types = [];

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isArrayAccessibleWithIntOrStringKey($codebase)) {
                if (get_class($type) === TArray::class) {
                    $array_types[] = new Atomic\TNonEmptyArray($type->type_params);
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TTemplateParam) {
                $array_types[] = $type;
            }
        }

        if (!$array_types) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'int-or-string-array-access',
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($array_types) {
            return \Psalm\Internal\Type\TypeCombination::combineTypes($array_types);
        }

        $failed_reconciliation = 2;

        return Type::getMixed($inside_loop);
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileCallable(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ) : Union {
        if ($existing_var_type->hasMixed()) {
            return Type::parseString('callable');
        }

        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $callable_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isCallableType()) {
                $callable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $callable_types[] = new Type\Atomic\TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof TNamedObject
                && $codebase->classExists($type->value)
                && $codebase->methodExists($type->value . '::__invoke')
            ) {
                $callable_types[] = $type;
            } elseif (get_class($type) === TString::class
                || get_class($type) === Type\Atomic\TNonEmptyString::class
            ) {
                $callable_types[] = new Type\Atomic\TCallableString();
                $did_remove_type = true;
            } elseif (get_class($type) === Type\Atomic\TLiteralString::class
                && \Psalm\Internal\Codebase\InternalCallMapHandler::inCallMap($type->value)
            ) {
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TArray) {
                $type = clone $type;
                $type = new TCallableArray($type->type_params);
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TList) {
                $type = clone $type;
                $type = new TCallableList($type->type_param);
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TKeyedArray) {
                $type = clone $type;
                $type = new TCallableKeyedArray($type->properties);
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->isMixed()) {
                    $type = clone $type;
                    $type->as = new Type\Union([new Type\Atomic\TCallable]);
                }
                $callable_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$callable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'callable',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($callable_types) {
            return \Psalm\Internal\Type\TypeCombination::combineTypes($callable_types);
        }

        $failed_reconciliation = 2;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param   0|1|2    $failed_reconciliation
     */
    private static function reconcileFalsyOrEmpty(
        string $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation
    ) : Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $did_remove_type = $existing_var_type->hasDefinitelyNumericType(false)
            || $existing_var_type->hasType('iterable');

        if ($existing_var_type->hasMixed()) {
            if ($existing_var_type->isMixed()
                && $existing_var_atomic_types['mixed'] instanceof Type\Atomic\TNonEmptyMixed
            ) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            'Found a paradox when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a ' . $assertion . ' assertion',
                            $code_location
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }

                return Type::getMixed();
            }

            if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TEmptyMixed) {
                $did_remove_type = true;
                $existing_var_type->removeType('mixed');

                if (!$existing_var_atomic_types['mixed'] instanceof Type\Atomic\TNonEmptyMixed) {
                    $existing_var_type->addType(new Type\Atomic\TEmptyMixed);
                }
            } elseif ($existing_var_type->isMixed()) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new RedundantCondition(
                            'Found a redundant condition when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a ' . $assertion . ' assertion',
                            $code_location,
                            $existing_var_type->getId() . ' ' . $assertion
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }
            }

            if ($existing_var_type->isMixed()) {
                return $existing_var_type;
            }
        }

        if ($existing_var_type->hasScalar()) {
            if ($existing_var_type->isSingle()
                && $existing_var_atomic_types['scalar'] instanceof Type\Atomic\TNonEmptyScalar
            ) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new ParadoxicalCondition(
                            'Found a paradox when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a ' . $assertion . ' assertion',
                            $code_location
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }

                return Type::getScalar();
            }

            if (!$existing_var_atomic_types['scalar'] instanceof Type\Atomic\TEmptyScalar) {
                $did_remove_type = true;
                $existing_var_type->removeType('scalar');

                if (!$existing_var_atomic_types['scalar'] instanceof Type\Atomic\TNonEmptyScalar) {
                    $existing_var_type->addType(new Type\Atomic\TEmptyScalar);
                }
            } elseif ($existing_var_type->isSingle()) {
                if ($code_location
                    && $key
                    && IssueBuffer::accepts(
                        new RedundantCondition(
                            'Found a redundant condition when evaluating ' . $key
                                . ' of type ' . $existing_var_type->getId()
                                . ' and trying to reconcile it with a ' . $assertion . ' assertion',
                            $code_location,
                            $existing_var_type->getId() . ' ' . $assertion
                        ),
                        $suppressed_issues
                    )
                ) {
                    // fall through
                }
            }

            if ($existing_var_type->isSingle()) {
                return $existing_var_type;
            }
        }

        if ($existing_var_type->hasType('bool')) {
            $did_remove_type = true;
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TFalse);
        }

        if ($existing_var_type->hasType('true')) {
            $did_remove_type = true;
            $existing_var_type->removeType('true');
        }

        if ($existing_var_type->hasString()) {
            $existing_string_types = $existing_var_type->getLiteralStrings();

            if ($existing_string_types) {
                foreach ($existing_string_types as $string_key => $literal_type) {
                    if ($literal_type->value) {
                        $existing_var_type->removeType($string_key);
                        $did_remove_type = true;
                    }
                }
            } else {
                $did_remove_type = true;
                if ($existing_var_type->hasType('class-string')) {
                    $existing_var_type->removeType('class-string');
                }

                if ($existing_var_type->hasType('callable-string')) {
                    $existing_var_type->removeType('callable-string');
                }

                if ($existing_var_type->hasType('string')) {
                    $existing_var_type->removeType('string');

                    if (!$existing_var_atomic_types['string'] instanceof Type\Atomic\TNonEmptyString) {
                        $existing_var_type->addType(new Type\Atomic\TLiteralString(''));
                        $existing_var_type->addType(new Type\Atomic\TLiteralString('0'));
                    }
                }
            }
        }

        if ($existing_var_type->hasInt()) {
            $existing_int_types = $existing_var_type->getLiteralInts();

            if ($existing_int_types) {
                foreach ($existing_int_types as $int_key => $literal_type) {
                    if ($literal_type->value) {
                        $existing_var_type->removeType($int_key);
                        $did_remove_type = true;
                    }
                }
            } else {
                $did_remove_type = true;
                $existing_var_type->removeType('int');
                $existing_var_type->addType(new Type\Atomic\TLiteralInt(0));
            }
        }

        if ($existing_var_type->hasFloat()) {
            $existing_float_types = $existing_var_type->getLiteralFloats();

            if ($existing_float_types) {
                foreach ($existing_float_types as $float_key => $literal_type) {
                    if ($literal_type->value) {
                        $existing_var_type->removeType($float_key);
                        $did_remove_type = true;
                    }
                }
            } else {
                $did_remove_type = true;
                $existing_var_type->removeType('float');
                $existing_var_type->addType(new Type\Atomic\TLiteralFloat(0));
            }
        }

        if ($existing_var_type->hasNumeric()) {
            $existing_int_types = $existing_var_type->getLiteralInts();

            if ($existing_int_types) {
                foreach ($existing_int_types as $int_key => $literal_type) {
                    if ($literal_type->value) {
                        $existing_var_type->removeType($int_key);
                    }
                }
            }

            $existing_string_types = $existing_var_type->getLiteralStrings();

            if ($existing_string_types) {
                foreach ($existing_string_types as $string_key => $literal_type) {
                    if ($literal_type->value) {
                        $existing_var_type->removeType($string_key);
                    }
                }
            }

            $existing_float_types = $existing_var_type->getLiteralFloats();

            if ($existing_float_types) {
                foreach ($existing_float_types as $float_key => $literal_type) {
                    if ($literal_type->value) {
                        $existing_var_type->removeType($float_key);
                    }
                }
            }

            $did_remove_type = true;
            $existing_var_type->removeType('numeric');
            $existing_var_type->addType(new Type\Atomic\TEmptyNumeric);
        }

        if (isset($existing_var_atomic_types['array'])) {
            $array_atomic_type = $existing_var_atomic_types['array'];

            if ($array_atomic_type instanceof Type\Atomic\TNonEmptyArray
                || $array_atomic_type instanceof Type\Atomic\TNonEmptyList
                || ($array_atomic_type instanceof Type\Atomic\TKeyedArray
                    && array_filter(
                        $array_atomic_type->properties,
                        function (Type\Union $t): bool {
                            return !$t->possibly_undefined;
                        }
                    ))
            ) {
                $did_remove_type = true;

                $existing_var_type->removeType('array');
            } elseif ($array_atomic_type->getId() !== 'array<empty, empty>') {
                $did_remove_type = true;

                $existing_var_type->addType(new TArray(
                    [
                        new Type\Union([new TEmpty]),
                        new Type\Union([new TEmpty]),
                    ]
                ));
            }
        }

        if (isset($existing_var_atomic_types['scalar'])
            && $existing_var_atomic_types['scalar']->getId() !== 'empty-scalar'
        ) {
            $did_remove_type = true;
            $existing_var_type->addType(new Type\Atomic\TEmptyScalar);
        }

        foreach ($existing_var_atomic_types as $type_key => $type) {
            if ($type instanceof TNamedObject
                || $type instanceof TObject
                || $type instanceof TResource
                || $type instanceof TCallable
                || $type instanceof TClassString
            ) {
                $did_remove_type = true;

                $existing_var_type->removeType($type_key);
            }

            if ($type instanceof TTemplateParam) {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || empty($existing_var_type->getAtomicTypes()))
            && ($assertion !== 'empty' || !$existing_var_type->possibly_undefined)
        ) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($existing_var_type->getAtomicTypes()) {
            return $existing_var_type;
        }

        $failed_reconciliation = 2;

        return $assertion === 'empty' && $existing_var_type->possibly_undefined
            ? Type::getEmpty()
            : Type::getMixed();
    }
}
