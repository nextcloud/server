<?php
namespace Psalm\Type;

use function array_filter;
use function array_merge;
use function array_shift;
use function array_values;
use function count;
use function get_class;
use function implode;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeCombination;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use function reset;
use function sort;
use function strpos;
use function strval;
use function substr;

class Union implements TypeNode
{
    /**
     * @var non-empty-array<string, Atomic>
     */
    private $types;

    /**
     * Whether the type originated in a docblock
     *
     * @var bool
     */
    public $from_docblock = false;

    /**
     * Whether the type originated from integer calculation
     *
     * @var bool
     */
    public $from_calculation = false;

    /**
     * Whether the property that this type has been derived from has been initialized in a constructor
     *
     * @var bool
     */
    public $initialized = true;

    /**
     * Which class the type was initialised in
     *
     * @var ?string
     */
    public $initialized_class = null;

    /**
     * Whether or not the type has been checked yet
     *
     * @var bool
     */
    public $checked = false;

    /**
     * @var bool
     */
    public $failed_reconciliation = false;

    /**
     * Whether or not to ignore issues with possibly-null values
     *
     * @var bool
     */
    public $ignore_nullable_issues = false;

    /**
     * Whether or not to ignore issues with possibly-false values
     *
     * @var bool
     */
    public $ignore_falsable_issues = false;

    /**
     * Whether or not this variable is possibly undefined
     *
     * @var bool
     */
    public $possibly_undefined = false;

    /**
     * Whether or not this variable is possibly undefined
     *
     * @var bool
     */
    public $possibly_undefined_from_try = false;

    /**
     * Whether or not this union had a template, since replaced
     *
     * @var bool
     */
    public $had_template = false;

    /**
     * Whether or not this union comes from a template "as" default
     *
     * @var bool
     */
    public $from_template_default = false;

    /**
     * @var array<string, TLiteralString>
     */
    private $literal_string_types = [];

    /**
     * @var array<string, Type\Atomic\TClassString>
     */
    private $typed_class_strings = [];

    /**
     * @var array<string, TLiteralInt>
     */
    private $literal_int_types = [];

    /**
     * @var array<string, TLiteralFloat>
     */
    private $literal_float_types = [];

    /**
     * Whether or not the type was passed by reference
     *
     * @var bool
     */
    public $by_ref = false;

    /**
     * @var bool
     */
    public $reference_free = false;

    /**
     * @var bool
     */
    public $allow_mutations = true;

    /** @var null|string */
    private $id;

    /**
     * @var array<string, \Psalm\Internal\DataFlow\DataFlowNode>
     */
    public $parent_nodes = [];

    /**
     * @var bool
     */
    public $different = false;

    /**
     * Constructs an Union instance
     *
     * @param non-empty-array<int, Atomic>     $types
     */
    public function __construct(array $types)
    {
        $from_docblock = false;

        $keyed_types = [];

        foreach ($types as $type) {
            $key = $type->getKey();
            $keyed_types[$key] = $type;

            if ($type instanceof TLiteralInt) {
                $this->literal_int_types[$key] = $type;
            } elseif ($type instanceof TLiteralString) {
                $this->literal_string_types[$key] = $type;
            } elseif ($type instanceof TLiteralFloat) {
                $this->literal_float_types[$key] = $type;
            } elseif ($type instanceof Type\Atomic\TClassString
                && ($type->as_type || $type instanceof Type\Atomic\TTemplateParamClass)
            ) {
                $this->typed_class_strings[$key] = $type;
            }

            $from_docblock = $from_docblock || $type->from_docblock;
        }

        $this->types = $keyed_types;

        $this->from_docblock = $from_docblock;
    }

    /**
     * @return array<string, Atomic>
     * @deprecated in favour of getAtomicTypes()
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return non-empty-array<string, Atomic>
     */
    public function getAtomicTypes(): array
    {
        return $this->types;
    }

    public function addType(Atomic $type): void
    {
        $this->types[$type->getKey()] = $type;

        if ($type instanceof TLiteralString) {
            $this->literal_string_types[$type->getKey()] = $type;
        } elseif ($type instanceof TLiteralInt) {
            $this->literal_int_types[$type->getKey()] = $type;
        } elseif ($type instanceof TLiteralFloat) {
            $this->literal_float_types[$type->getKey()] = $type;
        } elseif ($type instanceof TString && $this->literal_string_types) {
            foreach ($this->literal_string_types as $key => $_) {
                unset($this->literal_string_types[$key], $this->types[$key]);
            }
            if (!$type instanceof Type\Atomic\TClassString
                || (!$type->as_type && !$type instanceof Type\Atomic\TTemplateParamClass)
            ) {
                foreach ($this->typed_class_strings as $key => $_) {
                    unset($this->typed_class_strings[$key], $this->types[$key]);
                }
            }
        } elseif ($type instanceof TInt && $this->literal_int_types) {
            foreach ($this->literal_int_types as $key => $_) {
                unset($this->literal_int_types[$key], $this->types[$key]);
            }
        } elseif ($type instanceof TFloat && $this->literal_float_types) {
            foreach ($this->literal_float_types as $key => $_) {
                unset($this->literal_float_types[$key], $this->types[$key]);
            }
        }

        $this->id = null;
    }

    public function __clone()
    {
        $this->literal_string_types = [];
        $this->literal_int_types = [];
        $this->literal_float_types = [];
        $this->typed_class_strings = [];

        foreach ($this->types as $key => &$type) {
            $type = clone $type;

            if ($type instanceof TLiteralInt) {
                $this->literal_int_types[$key] = $type;
            } elseif ($type instanceof TLiteralString) {
                $this->literal_string_types[$key] = $type;
            } elseif ($type instanceof TLiteralFloat) {
                $this->literal_float_types[$key] = $type;
            } elseif ($type instanceof Type\Atomic\TClassString
                && ($type->as_type || $type instanceof Type\Atomic\TTemplateParamClass)
            ) {
                $this->typed_class_strings[$key] = $type;
            }
        }
    }

    public function __toString(): string
    {
        $types = [];

        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        foreach ($this->types as $type) {
            if ($type instanceof TLiteralFloat) {
                if ($printed_float) {
                    continue;
                }

                $printed_float = true;
            } elseif ($type instanceof TLiteralString) {
                if ($printed_string) {
                    continue;
                }

                $printed_string = true;
            } elseif ($type instanceof TLiteralInt) {
                if ($printed_int) {
                    continue;
                }

                $printed_int = true;
            }

            $types[] = strval($type);
        }

        sort($types);
        return implode('|', $types);
    }

    public function getKey() : string
    {
        $types = [];

        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        foreach ($this->types as $type) {
            if ($type instanceof TLiteralFloat) {
                if ($printed_float) {
                    continue;
                }

                $types[] = 'float';
                $printed_float = true;
            } elseif ($type instanceof TLiteralString) {
                if ($printed_string) {
                    continue;
                }

                $types[] = 'string';
                $printed_string = true;
            } elseif ($type instanceof TLiteralInt) {
                if ($printed_int) {
                    continue;
                }

                $types[] = 'int';
                $printed_int = true;
            } else {
                $types[] = strval($type->getKey());
            }
        }

        sort($types);
        return implode('|', $types);
    }

    public function getId(): string
    {
        if ($this->id) {
            return $this->id;
        }

        $types = [];
        foreach ($this->types as $type) {
            $types[] = strval($type->getId());
        }
        sort($types);

        if (\count($types) > 1) {
            foreach ($types as $i => $type) {
                if (strpos($type, ' as ') && strpos($type, '(') === false) {
                    $types[$i] = '(' . $type . ')';
                }
            }
        }

        $id = implode('|', $types);

        $this->id = $id;

        return $id;
    }

    public function getAssertionString(): string
    {
        foreach ($this->types as $type) {
            return $type->getAssertionString();
        }

        throw new \UnexpectedValueException('Should only be one type per assertion');
    }

    /**
     * @param  array<string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        $types = [];

        $multi_ints = count($this->literal_int_types) > 1
            || $this->hasPositiveInt();
        $multi_strings = count($this->literal_string_types) > 1;
        $multi_floats = count($this->literal_float_types) > 1;

        foreach ($this->types as $type) {
            $type_string = $type->toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format);

            if ($type instanceof TLiteralInt && !$multi_ints) {
                $type_string = 'int';
            } elseif ($type instanceof TLiteralFloat && !$multi_floats) {
                $type_string = 'float';
            } elseif ($type instanceof TLiteralString && !$multi_strings) {
                $type_string = 'string';
            }

            $types[] = $type_string;
        }

        sort($types);
        return implode('|', \array_unique($types));
    }

    /**
     * @param  array<string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        $nullable = false;

        if (!$this->isSingleAndMaybeNullable()
            || $php_major_version < 7
            || (isset($this->types['null']) && $php_major_version === 7 && $php_minor_version < 1)
        ) {
            return null;
        }

        $types = $this->types;

        if (isset($types['null'])) {
            if (count($types) === 1) {
                return null;
            }

            unset($types['null']);

            $nullable = true;
        }

        $atomic_type = array_values($types)[0];

        $atomic_type_string = $atomic_type->toPhpString(
            $namespace,
            $aliased_classes,
            $this_class,
            $php_major_version,
            $php_minor_version
        );

        if ($atomic_type_string) {
            return ($nullable ? '?' : '') . $atomic_type_string;
        }

        return null;
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        if (!$this->isSingleAndMaybeNullable()) {
            return false;
        }

        $types = $this->types;

        if (isset($types['null'])) {
            if (count($types) > 1) {
                unset($types['null']);
            } else {
                return false;
            }
        }

        $atomic_type = array_values($types)[0];

        return $atomic_type->canBeFullyExpressedInPhp();
    }

    public function removeType(string $type_string): bool
    {
        if (isset($this->types[$type_string])) {
            unset($this->types[$type_string]);

            if (strpos($type_string, '(')) {
                unset(
                    $this->literal_string_types[$type_string],
                    $this->literal_int_types[$type_string],
                    $this->literal_float_types[$type_string]
                );
            }

            $this->id = null;

            return true;
        }

        if ($type_string === 'string') {
            if ($this->literal_string_types) {
                foreach ($this->literal_string_types as $literal_key => $_) {
                    unset($this->types[$literal_key]);
                }
                $this->literal_string_types = [];
            }

            if ($this->typed_class_strings) {
                foreach ($this->typed_class_strings as $typed_class_key => $_) {
                    unset($this->types[$typed_class_key]);
                }
                $this->typed_class_strings = [];
            }

            unset($this->types['class-string'], $this->types['trait-string']);
        } elseif ($type_string === 'int' && $this->literal_int_types) {
            foreach ($this->literal_int_types as $literal_key => $_) {
                unset($this->types[$literal_key]);
            }
            $this->literal_int_types = [];
        } elseif ($type_string === 'float' && $this->literal_float_types) {
            foreach ($this->literal_float_types as $literal_key => $_) {
                unset($this->types[$literal_key]);
            }
            $this->literal_float_types = [];
        }

        return false;
    }

    public function bustCache(): void
    {
        $this->id = null;
    }

    public function hasType(string $type_string): bool
    {
        return isset($this->types[$type_string]);
    }

    public function hasArray(): bool
    {
        return isset($this->types['array']);
    }

    public function hasList(): bool
    {
        return isset($this->types['array']) && $this->types['array'] instanceof Atomic\TList;
    }

    public function hasClassStringMap(): bool
    {
        return isset($this->types['array']) && $this->types['array'] instanceof Atomic\TClassStringMap;
    }

    public function isTemplatedClassString() : bool
    {
        return $this->isSingle()
            && count(
                array_filter(
                    $this->types,
                    function ($type): bool {
                        return $type instanceof Atomic\TTemplateParamClass;
                    }
                )
            ) === 1;
    }

    public function hasEmptyArray(): bool
    {
        return isset($this->types['array'])
            && $this->types['array'] instanceof Atomic\TArray
            && $this->types['array']->type_params[1]->isEmpty();
    }

    public function hasArrayAccessInterface(Codebase $codebase) : bool
    {
        return !!array_filter(
            $this->types,
            function ($type) use ($codebase) {
                return $type->hasArrayAccessInterface($codebase);
            }
        );
    }

    public function hasCallableType(): bool
    {
        return $this->getCallableTypes() || $this->getClosureTypes();
    }

    /**
     * @return array<string, Atomic\TCallable>
     */
    private function getCallableTypes(): array
    {
        return array_filter(
            $this->types,
            function ($type): bool {
                return $type instanceof Atomic\TCallable;
            }
        );
    }

    /**
     * @return array<string, Atomic\TClosure>
     */
    public function getClosureTypes(): array
    {
        return array_filter(
            $this->types,
            function ($type): bool {
                return $type instanceof Atomic\TClosure;
            }
        );
    }

    public function hasObject(): bool
    {
        return isset($this->types['object']);
    }

    public function hasObjectType(): bool
    {
        foreach ($this->types as $type) {
            if ($type->isObjectType()) {
                return true;
            }
        }

        return false;
    }

    public function isObjectType(): bool
    {
        foreach ($this->types as $type) {
            if (!$type->isObjectType()) {
                return false;
            }
        }

        return true;
    }

    public function hasNamedObjectType(): bool
    {
        foreach ($this->types as $type) {
            if ($type->isNamedObjectType()) {
                return true;
            }
        }

        return false;
    }

    public function isFormerStaticObject(): bool
    {
        foreach ($this->types as $type) {
            if (!$type instanceof TNamedObject
                || !$type->was_static
            ) {
                return false;
            }
        }

        return true;
    }

    public function hasFormerStaticObject(): bool
    {
        foreach ($this->types as $type) {
            if ($type instanceof TNamedObject
                && $type->was_static
            ) {
                return true;
            }
        }

        return false;
    }

    public function isNullable(): bool
    {
        if (isset($this->types['null'])) {
            return true;
        }

        foreach ($this->types as $type) {
            if ($type instanceof TTemplateParam && $type->as->isNullable()) {
                return true;
            }
        }

        return false;
    }

    public function isFalsable(): bool
    {
        if (isset($this->types['false'])) {
            return true;
        }

        foreach ($this->types as $type) {
            if ($type instanceof TTemplateParam && $type->as->isFalsable()) {
                return true;
            }
        }

        return false;
    }

    public function hasBool(): bool
    {
        return isset($this->types['bool']) || isset($this->types['false']) || isset($this->types['true']);
    }

    public function hasString(): bool
    {
        return isset($this->types['string'])
            || isset($this->types['class-string'])
            || isset($this->types['trait-string'])
            || isset($this->types['numeric-string'])
            || $this->literal_string_types
            || $this->typed_class_strings;
    }

    public function hasLowercaseString(): bool
    {
        return isset($this->types['string'])
            && ($this->types['string'] instanceof Atomic\TLowercaseString
                || $this->types['string'] instanceof Atomic\TNonEmptyLowercaseString);
    }

    public function hasLiteralClassString(): bool
    {
        return count($this->typed_class_strings) > 0;
    }

    public function hasInt(): bool
    {
        return isset($this->types['int']) || isset($this->types['array-key']) || $this->literal_int_types;
    }

    public function hasPositiveInt(): bool
    {
        return isset($this->types['int']) && $this->types['int'] instanceof Type\Atomic\TPositiveInt;
    }

    public function hasArrayKey(): bool
    {
        return isset($this->types['array-key']);
    }

    public function hasFloat(): bool
    {
        return isset($this->types['float']) || $this->literal_float_types;
    }

    public function hasDefinitelyNumericType(bool $include_literal_int = true): bool
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['numeric-string'])
            || ($include_literal_int && $this->literal_int_types)
            || $this->literal_float_types;
    }

    public function hasPossiblyNumericType(): bool
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['string'])
            || isset($this->types['numeric-string'])
            || $this->literal_int_types
            || $this->literal_float_types
            || $this->literal_string_types;
    }

    public function hasScalar(): bool
    {
        return isset($this->types['scalar']);
    }

    public function hasNumeric(): bool
    {
        return isset($this->types['numeric']);
    }

    public function hasScalarType(): bool
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['string'])
            || isset($this->types['class-string'])
            || isset($this->types['trait-string'])
            || isset($this->types['bool'])
            || isset($this->types['false'])
            || isset($this->types['true'])
            || isset($this->types['numeric'])
            || isset($this->types['numeric-string'])
            || $this->literal_int_types
            || $this->literal_float_types
            || $this->literal_string_types
            || $this->typed_class_strings;
    }

    public function hasTemplate(): bool
    {
        return (bool) array_filter(
            $this->types,
            function (Atomic $type) : bool {
                return $type instanceof Type\Atomic\TTemplateParam
                    || ($type instanceof Type\Atomic\TNamedObject
                        && $type->extra_types
                        && array_filter(
                            $type->extra_types,
                            function ($t): bool {
                                return $t instanceof Type\Atomic\TTemplateParam;
                            }
                        )
                    );
            }
        );
    }

    public function hasConditional(): bool
    {
        return (bool) array_filter(
            $this->types,
            function (Atomic $type) : bool {
                return $type instanceof Type\Atomic\TConditional;
            }
        );
    }

    public function hasTemplateOrStatic(): bool
    {
        return (bool) array_filter(
            $this->types,
            function (Atomic $type) : bool {
                return $type instanceof Type\Atomic\TTemplateParam
                    || ($type instanceof Type\Atomic\TNamedObject
                        && ($type->was_static
                            || ($type->extra_types
                                && array_filter(
                                    $type->extra_types,
                                    function ($t): bool {
                                        return $t instanceof Type\Atomic\TTemplateParam;
                                    }
                                )
                            )
                        )
                    );
            }
        );
    }

    public function hasMixed(): bool
    {
        return isset($this->types['mixed']);
    }

    public function isMixed(): bool
    {
        return isset($this->types['mixed']) && count($this->types) === 1;
    }

    public function isEmptyMixed(): bool
    {
        return isset($this->types['mixed'])
            && $this->types['mixed'] instanceof Type\Atomic\TEmptyMixed;
    }

    public function isVanillaMixed(): bool
    {
        /**
         * @psalm-suppress UndefinedPropertyFetch
         */
        return isset($this->types['mixed'])
            && !$this->types['mixed']->from_loop_isset
            && get_class($this->types['mixed']) === Type\Atomic\TMixed::class
            && !$this->types['mixed']->from_loop_isset
            && count($this->types) === 1;
    }

    public function isArrayKey(): bool
    {
        return isset($this->types['array-key']) && count($this->types) === 1;
    }

    public function isNull(): bool
    {
        return count($this->types) === 1 && isset($this->types['null']);
    }

    public function isFalse(): bool
    {
        return count($this->types) === 1 && isset($this->types['false']);
    }

    public function isTrue(): bool
    {
        return count($this->types) === 1 && isset($this->types['true']);
    }

    public function isVoid(): bool
    {
        return isset($this->types['void']);
    }

    public function isNever(): bool
    {
        return isset($this->types['never-return']);
    }

    public function isGenerator(): bool
    {
        return count($this->types) === 1
            && (($single_type = reset($this->types)) instanceof TNamedObject)
            && ($single_type->value === 'Generator');
    }

    public function isEmpty(): bool
    {
        return isset($this->types['empty']);
    }

    public function substitute(Union $old_type, ?Union $new_type = null): void
    {
        if ($this->hasMixed() && !$this->isEmptyMixed()) {
            return;
        }

        if ($new_type && $new_type->ignore_nullable_issues) {
            $this->ignore_nullable_issues = true;
        }

        if ($new_type && $new_type->ignore_falsable_issues) {
            $this->ignore_falsable_issues = true;
        }

        foreach ($old_type->types as $old_type_part) {
            if (!$this->removeType($old_type_part->getKey())) {
                if ($old_type_part instanceof Type\Atomic\TFalse
                    && isset($this->types['bool'])
                    && !isset($this->types['true'])
                ) {
                    $this->removeType('bool');
                    $this->types['true'] = new Type\Atomic\TTrue;
                } elseif ($old_type_part instanceof Type\Atomic\TTrue
                    && isset($this->types['bool'])
                    && !isset($this->types['false'])
                ) {
                    $this->removeType('bool');
                    $this->types['false'] = new Type\Atomic\TFalse;
                } elseif (isset($this->types['iterable'])) {
                    if ($old_type_part instanceof Type\Atomic\TNamedObject
                        && $old_type_part->value === 'Traversable'
                        && !isset($this->types['array'])
                    ) {
                        $this->removeType('iterable');
                        $this->types['array'] = new Type\Atomic\TArray([Type::getArrayKey(), Type::getMixed()]);
                    }

                    if ($old_type_part instanceof Type\Atomic\TArray
                        && !isset($this->types['traversable'])
                    ) {
                        $this->removeType('iterable');
                        $this->types['traversable'] = new Type\Atomic\TNamedObject('Traversable');
                    }
                } elseif (isset($this->types['array-key'])) {
                    if ($old_type_part instanceof Type\Atomic\TString
                        && !isset($this->types['int'])
                    ) {
                        $this->removeType('array-key');
                        $this->types['int'] = new Type\Atomic\TInt();
                    }

                    if ($old_type_part instanceof Type\Atomic\TInt
                        && !isset($this->types['string'])
                    ) {
                        $this->removeType('array-key');
                        $this->types['string'] = new Type\Atomic\TString();
                    }
                }
            }
        }

        if ($new_type) {
            foreach ($new_type->types as $key => $new_type_part) {
                if (!isset($this->types[$key])
                    || ($new_type_part instanceof Type\Atomic\Scalar
                        && get_class($new_type_part) === get_class($this->types[$key]))
                ) {
                    $this->types[$key] = $new_type_part;
                } else {
                    $combined = TypeCombination::combineTypes([$new_type_part, $this->types[$key]]);
                    $this->types[$key] = array_values($combined->types)[0];
                }
            }
        } elseif (count($this->types) === 0) {
            $this->types['mixed'] = new Atomic\TMixed();
        }

        $this->id = null;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        $keys_to_unset = [];

        $new_types = [];

        $is_mixed = false;

        $found_generic_params = $template_result->upper_bounds ?: [];

        foreach ($this->types as $key => $atomic_type) {
            $atomic_type->replaceTemplateTypesWithArgTypes($template_result, $codebase);

            if ($atomic_type instanceof Type\Atomic\TTemplateParam) {
                $template_type = null;

                $traversed_type = \Psalm\Internal\Type\UnionTemplateHandler::getRootTemplateType(
                    $found_generic_params,
                    $atomic_type->param_name,
                    $atomic_type->defining_class
                );

                if ($traversed_type) {
                    $template_type = $traversed_type[0];

                    if (!$atomic_type->as->isMixed() && $template_type->isMixed()) {
                        $template_type = clone $atomic_type->as;
                    } else {
                        $template_type = clone $template_type;
                    }

                    if ($atomic_type->extra_types) {
                        foreach ($template_type->getAtomicTypes() as $template_type_key => $atomic_template_type) {
                            if ($atomic_template_type instanceof TNamedObject
                                || $atomic_template_type instanceof TTemplateParam
                                || $atomic_template_type instanceof TIterable
                                || $atomic_template_type instanceof Type\Atomic\TObjectWithProperties
                            ) {
                                $atomic_template_type->extra_types = array_merge(
                                    $atomic_type->extra_types,
                                    $atomic_template_type->extra_types ?: []
                                );
                            } elseif ($atomic_template_type instanceof Type\Atomic\TObject) {
                                $first_atomic_type = array_shift($atomic_type->extra_types);

                                if ($atomic_type->extra_types) {
                                    $first_atomic_type->extra_types = $atomic_type->extra_types;
                                }

                                $template_type->removeType($template_type_key);
                                $template_type->addType($first_atomic_type);
                            }
                        }
                    }
                } elseif ($codebase) {
                    foreach ($found_generic_params as $template_type_map) {
                        foreach ($template_type_map as $template_class => $_) {
                            if (substr($template_class, 0, 3) === 'fn-') {
                                continue;
                            }

                            try {
                                $classlike_storage = $codebase->classlike_storage_provider->get($template_class);

                                if ($classlike_storage->template_type_extends) {
                                    $defining_class = $atomic_type->defining_class;

                                    if (isset($classlike_storage->template_type_extends[$defining_class])) {
                                        $param_map = $classlike_storage->template_type_extends[$defining_class];

                                        if (isset($param_map[$key])
                                            && isset($found_generic_params[(string) $param_map[$key]][$template_class])
                                        ) {
                                            $template_type
                                                = clone $found_generic_params
                                                    [(string) $param_map[$key]][$template_class][0];
                                        }
                                    }
                                }
                            } catch (\InvalidArgumentException $e) {
                            }
                        }
                    }
                }

                if ($template_type) {
                    $keys_to_unset[] = $key;

                    foreach ($template_type->types as $template_type_part) {
                        if ($template_type_part instanceof Type\Atomic\TMixed) {
                            $is_mixed = true;
                        }

                        $new_types[$template_type_part->getKey()] = $template_type_part;
                    }
                }
            } elseif ($atomic_type instanceof Type\Atomic\TTemplateParamClass) {
                $template_type = isset($found_generic_params[$atomic_type->param_name][$atomic_type->defining_class])
                    ? clone $found_generic_params[$atomic_type->param_name][$atomic_type->defining_class][0]
                    : null;

                $class_template_type = null;

                if ($template_type) {
                    foreach ($template_type->types as $template_type_part) {
                        if ($template_type_part instanceof Type\Atomic\TMixed
                            || $template_type_part instanceof Type\Atomic\TObject
                        ) {
                            $class_template_type = new Type\Atomic\TClassString();
                        } elseif ($template_type_part instanceof Type\Atomic\TNamedObject) {
                            $class_template_type = new Type\Atomic\TClassString(
                                $template_type_part->value,
                                $template_type_part
                            );
                        } elseif ($template_type_part instanceof Type\Atomic\TTemplateParam) {
                            $first_atomic_type = array_values($template_type_part->as->types)[0];

                            $class_template_type = new Type\Atomic\TTemplateParamClass(
                                $template_type_part->param_name,
                                $template_type_part->as->getId(),
                                $first_atomic_type instanceof TNamedObject ? $first_atomic_type : null,
                                $template_type_part->defining_class
                            );
                        }
                    }
                }

                if ($class_template_type) {
                    $keys_to_unset[] = $key;
                    $new_types[$class_template_type->getKey()] = $class_template_type;
                }
            } elseif ($atomic_type instanceof Type\Atomic\TTemplateIndexedAccess) {
                $keys_to_unset[] = $key;

                $template_type = null;

                if (isset($found_generic_params[$atomic_type->array_param_name][$atomic_type->defining_class])
                    && !empty($found_generic_params[$atomic_type->offset_param_name])
                ) {
                    $array_template_type
                        = $found_generic_params[$atomic_type->array_param_name][$atomic_type->defining_class][0];
                    $offset_template_type
                        = array_values(
                            $found_generic_params[$atomic_type->offset_param_name]
                        )[0][0];

                    if ($array_template_type->isSingle()
                        && $offset_template_type->isSingle()
                        && !$array_template_type->isMixed()
                        && !$offset_template_type->isMixed()
                    ) {
                        $array_template_type = array_values($array_template_type->types)[0];
                        $offset_template_type = array_values($offset_template_type->types)[0];

                        if ($array_template_type instanceof Type\Atomic\TKeyedArray
                            && ($offset_template_type instanceof Type\Atomic\TLiteralString
                                || $offset_template_type instanceof Type\Atomic\TLiteralInt)
                            && isset($array_template_type->properties[$offset_template_type->value])
                        ) {
                            $template_type = clone $array_template_type->properties[$offset_template_type->value];
                        }
                    }
                }

                if ($template_type) {
                    foreach ($template_type->types as $template_type_part) {
                        if ($template_type_part instanceof Type\Atomic\TMixed) {
                            $is_mixed = true;
                        }

                        $new_types[$template_type_part->getKey()] = $template_type_part;
                    }
                } else {
                    $new_types[$key] = new Type\Atomic\TMixed();
                }
            } elseif ($atomic_type instanceof Type\Atomic\TConditional
                && $codebase
            ) {
                $template_type = isset($found_generic_params[$atomic_type->param_name][$atomic_type->defining_class])
                    ? clone $found_generic_params[$atomic_type->param_name][$atomic_type->defining_class][0]
                    : null;

                $class_template_type = null;

                $atomic_type = clone $atomic_type;

                if ($template_type) {
                    $atomic_type->as_type->replaceTemplateTypesWithArgTypes(
                        $template_result,
                        $codebase
                    );

                    if ($atomic_type->as_type->isNullable() && $template_type->isVoid()) {
                        $template_type = Type::getNull();
                    }

                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $template_type,
                        $atomic_type->conditional_type
                    )) {
                        $class_template_type = clone $atomic_type->if_type;
                        $class_template_type->replaceTemplateTypesWithArgTypes(
                            $template_result,
                            $codebase
                        );
                    } elseif (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $template_type,
                        $atomic_type->as_type
                    )
                        && !UnionTypeComparator::isContainedBy(
                            $codebase,
                            $atomic_type->as_type,
                            $template_type
                        )
                    ) {
                        $class_template_type = clone $atomic_type->else_type;
                        $class_template_type->replaceTemplateTypesWithArgTypes(
                            $template_result,
                            $codebase
                        );
                    }
                }

                if (!$class_template_type) {
                    $atomic_type->if_type->replaceTemplateTypesWithArgTypes(
                        $template_result,
                        $codebase
                    );

                    $atomic_type->else_type->replaceTemplateTypesWithArgTypes(
                        $template_result,
                        $codebase
                    );

                    $class_template_type = Type::combineUnionTypes(
                        $atomic_type->if_type,
                        $atomic_type->else_type,
                        $codebase
                    );
                }

                $keys_to_unset[] = $key;

                foreach ($class_template_type->getAtomicTypes() as $class_template_atomic_type) {
                    $new_types[$class_template_atomic_type->getKey()] = $class_template_atomic_type;
                }
            }
        }

        $this->id = null;

        if ($is_mixed) {
            if (!$new_types) {
                throw new \UnexpectedValueException('This array should be full');
            }

            $this->types = $new_types;

            return;
        }

        foreach ($keys_to_unset as $key) {
            unset($this->types[$key]);
        }

        foreach ($new_types as $type) {
            if ($type instanceof TLiteralString) {
                $this->literal_string_types[$type->getKey()] = $type;
            } elseif ($type instanceof TLiteralInt) {
                $this->literal_int_types[$type->getKey()] = $type;
            } elseif ($type instanceof TLiteralFloat) {
                $this->literal_float_types[$type->getKey()] = $type;
            }
        }

        $atomic_types = array_values(array_merge($this->types, $new_types));

        if ($atomic_types) {
            $this->types = TypeCombination::combineTypes(
                $atomic_types,
                $codebase
            )->getAtomicTypes();

            $this->id = null;
        }
    }

    public function isSingle(): bool
    {
        $type_count = count($this->types);

        $int_literal_count = count($this->literal_int_types);
        $string_literal_count = count($this->literal_string_types);
        $float_literal_count = count($this->literal_float_types);

        if (($int_literal_count && $string_literal_count)
            || ($int_literal_count && $float_literal_count)
            || ($string_literal_count && $float_literal_count)
        ) {
            return false;
        }

        if ($int_literal_count || $string_literal_count || $float_literal_count) {
            $type_count -= $int_literal_count + $string_literal_count + $float_literal_count - 1;
        }

        return $type_count === 1;
    }

    public function isSingleAndMaybeNullable(): bool
    {
        $is_nullable = isset($this->types['null']);

        $type_count = count($this->types);

        if ($type_count === 1 && $is_nullable) {
            return false;
        }

        $int_literal_count = count($this->literal_int_types);
        $string_literal_count = count($this->literal_string_types);
        $float_literal_count = count($this->literal_float_types);

        if (($int_literal_count && $string_literal_count)
            || ($int_literal_count && $float_literal_count)
            || ($string_literal_count && $float_literal_count)
        ) {
            return false;
        }

        if ($int_literal_count || $string_literal_count || $float_literal_count) {
            $type_count -= $int_literal_count + $string_literal_count + $float_literal_count - 1;
        }

        return ($type_count - (int) $is_nullable) === 1;
    }

    /**
     * @return bool true if this is an int
     */
    public function isInt(bool $check_templates = false): bool
    {
        return count(
            array_filter(
                $this->types,
                function ($type) use ($check_templates): bool {
                    return $type instanceof TInt
                        || ($check_templates
                            && $type instanceof TTemplateParam
                            && $type->as->isInt()
                        );
                }
            )
        ) === count($this->types);
    }

    /**
     * @return bool true if this is a float
     */
    public function isFloat(): bool
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['float']) || $this->literal_float_types;
    }

    /**
     * @return bool true if this is a string
     */
    public function isString(bool $check_templates = false): bool
    {
        return count(
            array_filter(
                $this->types,
                function ($type) use ($check_templates): bool {
                    return $type instanceof TString
                        || ($check_templates
                            && $type instanceof TTemplateParam
                            && $type->as->isString()
                        );
                }
            )
        ) === count($this->types);
    }

    /**
     * @return bool true if this is a string literal with only one possible value
     */
    public function isSingleStringLiteral(): bool
    {
        return count($this->types) === 1 && count($this->literal_string_types) === 1;
    }

    /**
     * @throws \InvalidArgumentException if isSingleStringLiteral is false
     *
     * @return TLiteralString the only string literal represented by this union type
     */
    public function getSingleStringLiteral(): TLiteralString
    {
        if (count($this->types) !== 1 || count($this->literal_string_types) !== 1) {
            throw new \InvalidArgumentException('Not a string literal');
        }

        return reset($this->literal_string_types);
    }

    public function allStringLiterals() : bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralString) {
                return false;
            }
        }

        return true;
    }

    public function hasLiteralValue() : bool
    {
        return $this->literal_int_types
            || $this->literal_string_types
            || $this->literal_float_types
            || isset($this->types['false'])
            || isset($this->types['true']);
    }

    public function hasLiteralString(): bool
    {
        return count($this->literal_string_types) > 0;
    }

    public function hasLiteralInt(): bool
    {
        return count($this->literal_int_types) > 0;
    }

    /**
     * @return bool true if this is a int literal with only one possible value
     */
    public function isSingleIntLiteral(): bool
    {
        return count($this->types) === 1 && count($this->literal_int_types) === 1;
    }

    /**
     * @throws \InvalidArgumentException if isSingleIntLiteral is false
     *
     * @return TLiteralInt the only int literal represented by this union type
     */
    public function getSingleIntLiteral(): TLiteralInt
    {
        if (count($this->types) !== 1 || count($this->literal_int_types) !== 1) {
            throw new \InvalidArgumentException('Not an int literal');
        }

        return reset($this->literal_int_types);
    }

    /**
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     *
     */
    public function check(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $inherited = false,
        bool $prevent_template_covariance = false,
        ?string $calling_method_id = null
    ) : bool {
        if ($this->checked) {
            return true;
        }

        $checker = new \Psalm\Internal\TypeVisitor\TypeChecker(
            $source,
            $code_location,
            $suppressed_issues,
            $phantom_classes,
            $inferred,
            $inherited,
            $prevent_template_covariance,
            $calling_method_id
        );

        $checker->traverseArray($this->types);

        $this->checked = true;

        return !$checker->hasErrors();
    }

    /**
     * @param  array<string, mixed> $phantom_classes
     *
     */
    public function queueClassLikesForScanning(
        Codebase $codebase,
        ?FileStorage $file_storage = null,
        array $phantom_classes = []
    ): void {
        $scanner_visitor = new \Psalm\Internal\TypeVisitor\TypeScanner(
            $codebase->scanner,
            $file_storage,
            $phantom_classes
        );

        $scanner_visitor->traverseArray($this->types);
    }

    /**
     * @param  lowercase-string $fq_class_like_name
     */
    public function containsClassLike(string $fq_class_like_name) : bool
    {
        $classlike_visitor = new \Psalm\Internal\TypeVisitor\ContainsClassLikeVisitor($fq_class_like_name);

        $classlike_visitor->traverseArray($this->types);

        return $classlike_visitor->matches();
    }

    /**
     * @return list<TTemplateParam>
     */
    public function getTemplateTypes(): array
    {
        $template_type_collector = new \Psalm\Internal\TypeVisitor\TemplateTypeCollector();

        $template_type_collector->traverseArray($this->types);

        return $template_type_collector->getTemplateTypes();
    }

    public function setFromDocblock(): void
    {
        $this->from_docblock = true;

        (new \Psalm\Internal\TypeVisitor\FromDocblockSetter())->traverseArray($this->types);
    }

    public function replaceClassLike(string $old, string $new) : void
    {
        foreach ($this->types as $key => $atomic_type) {
            $atomic_type->replaceClassLike($old, $new);

            $this->removeType($key);
            $this->addType($atomic_type);
        }
    }

    public function equals(Union $other_type): bool
    {
        if ($other_type === $this) {
            return true;
        }

        if ($other_type->id && $this->id && $other_type->id !== $this->id) {
            return false;
        }

        if ($this->possibly_undefined !== $other_type->possibly_undefined) {
            return false;
        }

        if ($this->had_template !== $other_type->had_template) {
            return false;
        }

        if ($this->possibly_undefined_from_try !== $other_type->possibly_undefined_from_try) {
            return false;
        }

        if ($this->from_calculation !== $other_type->from_calculation) {
            return false;
        }

        if ($this->initialized !== $other_type->initialized) {
            return false;
        }

        if ($this->from_docblock !== $other_type->from_docblock) {
            return false;
        }

        if (count($this->types) !== count($other_type->types)) {
            return false;
        }

        if ($this->parent_nodes !== $other_type->parent_nodes) {
            return false;
        }

        if ($this->different || $other_type->different) {
            return false;
        }

        $other_atomic_types = $other_type->types;

        foreach ($this->types as $key => $atomic_type) {
            if (!isset($other_atomic_types[$key])) {
                return false;
            }

            if (!$atomic_type->equals($other_atomic_types[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, TLiteralString>
     */
    public function getLiteralStrings(): array
    {
        return $this->literal_string_types;
    }

    /**
     * @return array<string, TLiteralInt>
     */
    public function getLiteralInts(): array
    {
        return $this->literal_int_types;
    }

    /**
     * @return array<string, TLiteralFloat>
     */
    public function getLiteralFloats(): array
    {
        return $this->literal_float_types;
    }

    /**
     * @return array<string, Atomic>
     */
    public function getChildNodes() : array
    {
        return $this->types;
    }
}
