<?php
namespace Psalm\Type;

use function array_filter;
use function array_keys;
use function get_class;
use function is_numeric;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Type;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TAssertionFalsy;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableList;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\THtmlEscapedString;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TScalarClassConstant;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Atomic\TVoid;
use function strpos;
use function strtolower;
use function substr;

abstract class Atomic implements TypeNode
{
    public const KEY = 'atomic';

    /**
     * Whether or not the type has been checked yet
     *
     * @var bool
     */
    public $checked = false;

    /**
     * Whether or not the type comes from a docblock
     *
     * @var bool
     */
    public $from_docblock = false;

    /**
     * @var ?int
     */
    public $offset_start;

    /**
     * @var ?int
     */
    public $offset_end;

    /**
     * @param  array{int,int}|null   $php_version
     * @param  array<string, array<string, array{Union}>> $template_type_map
     * @param  array<string, TypeAlias> $type_aliases
     */
    public static function create(
        string $value,
        ?array $php_version = null,
        array $template_type_map = [],
        array $type_aliases = []
    ): Atomic {
        switch ($value) {
            case 'int':
                return new TInt();

            case 'float':
                return new TFloat();

            case 'string':
                return new TString();

            case 'bool':
                return new TBool();

            case 'void':
                if ($php_version === null
                    || ($php_version[0] > 7)
                    || ($php_version[0] === 7 && $php_version[1] >= 1)
                ) {
                    return new TVoid();
                }

                break;

            case 'array-key':
                return new TArrayKey();

            case 'iterable':
                if ($php_version === null
                    || ($php_version[0] > 7)
                    || ($php_version[0] === 7 && $php_version[1] >= 1)
                ) {
                    return new TIterable();
                }

                break;

            case 'never-return':
            case 'never-returns':
            case 'no-return':
                return new TNever();

            case 'object':
                if ($php_version === null
                    || ($php_version[0] > 7)
                    || ($php_version[0] === 7 && $php_version[1] >= 2)
                ) {
                    return new TObject();
                }

                break;

            case 'callable':
                return new TCallable();
            case 'pure-callable':
                $type = new TCallable();
                $type->is_pure = true;

                return $type;

            case 'array':
            case 'associative-array':
                return new TArray([new Union([new TArrayKey]), new Union([new TMixed])]);

            case 'non-empty-array':
                return new TNonEmptyArray([new Union([new TArrayKey]), new Union([new TMixed])]);

            case 'callable-array':
                return new Type\Atomic\TCallableArray([new Union([new TArrayKey]), new Union([new TMixed])]);

            case 'list':
                return new TList(Type::getMixed());

            case 'non-empty-list':
                return new TNonEmptyList(Type::getMixed());

            case 'non-empty-string':
                return new Type\Atomic\TNonEmptyString();

            case 'lowercase-string':
                return new Type\Atomic\TLowercaseString();

            case 'non-empty-lowercase-string':
                return new Type\Atomic\TNonEmptyLowercaseString();

            case 'resource':
                return $php_version !== null ? new TNamedObject($value) : new TResource();

            case 'resource (closed)':
            case 'closed-resource':
                return new Type\Atomic\TClosedResource();

            case 'positive-int':
                return new TPositiveInt();

            case 'numeric':
                return $php_version !== null ? new TNamedObject($value) : new TNumeric();

            case 'true':
                return $php_version !== null ? new TNamedObject($value) : new TTrue();

            case 'false':
                if ($php_version === null || $php_version[0] >= 8) {
                    return new TFalse();
                }

                return new TNamedObject($value);

            case 'empty':
                return $php_version !== null ? new TNamedObject($value) : new TEmpty();

            case 'scalar':
                return $php_version !== null ? new TNamedObject($value) : new TScalar();

            case 'null':
                if ($php_version === null || $php_version[0] >= 8) {
                    return new TNull();
                }

                return new TNamedObject($value);

            case 'mixed':
                if ($php_version === null || $php_version[0] >= 8) {
                    return new TMixed();
                }

                return new TNamedObject($value);

            case 'callable-object':
                return new TCallableObject();

            case 'class-string':
            case 'interface-string':
                return new TClassString();

            case 'trait-string':
                return new TTraitString();

            case 'callable-string':
                return new TCallableString();

            case 'numeric-string':
                return new TNumericString();

            case 'html-escaped-string':
                return new THtmlEscapedString();

            case 'false-y':
                return new TAssertionFalsy();

            case '$this':
                return new TNamedObject('static');
        }

        if (strpos($value, '-') && substr($value, 0, 4) !== 'OCI-') {
            throw new \Psalm\Exception\TypeParseTreeException('Unrecognized type ' . $value);
        }

        if (is_numeric($value[0])) {
            throw new \Psalm\Exception\TypeParseTreeException('First character of type cannot be numeric');
        }

        if (isset($template_type_map[$value])) {
            $first_class = array_keys($template_type_map[$value])[0];

            return new TTemplateParam(
                $value,
                $template_type_map[$value][$first_class][0],
                $first_class
            );
        }

        if (isset($type_aliases[$value])) {
            $type_alias = $type_aliases[$value];

            if ($type_alias instanceof TypeAlias\LinkableTypeAlias) {
                return new TTypeAlias($type_alias->declaring_fq_classlike_name, $type_alias->alias_name);
            }

            throw new \UnexpectedValueException('This should never happen');
        }

        return new TNamedObject($value);
    }

    abstract public function getKey(bool $include_extra = true) : string;

    public function isNumericType(): bool
    {
        return $this instanceof TInt
            || $this instanceof TFloat
            || $this instanceof TNumericString
            || $this instanceof TNumeric
            || ($this instanceof TLiteralString && \is_numeric($this->value));
    }

    public function isObjectType(): bool
    {
        return $this instanceof TObject
            || $this instanceof TNamedObject
            || ($this instanceof TTemplateParam
                && $this->as->hasObjectType());
    }

    public function isNamedObjectType(): bool
    {
        return $this instanceof TNamedObject
            || ($this instanceof TTemplateParam
                && ($this->as->hasNamedObjectType()
                    || array_filter(
                        $this->extra_types ?: [],
                        function ($extra_type) {
                            return $extra_type->isNamedObjectType();
                        }
                    )
                )
            );
    }

    public function isCallableType(): bool
    {
        return $this instanceof TCallable
            || $this instanceof TCallableObject
            || $this instanceof TCallableString
            || $this instanceof TCallableArray
            || $this instanceof TCallableList
            || $this instanceof TCallableKeyedArray;
    }

    public function isIterable(Codebase $codebase): bool
    {
        return $this instanceof TIterable
            || $this->hasTraversableInterface($codebase)
            || $this instanceof TArray
            || $this instanceof TKeyedArray
            || $this instanceof TList;
    }

    public function isCountable(Codebase $codebase): bool
    {
        return $this->hasCountableInterface($codebase)
            || $this instanceof TArray
            || $this instanceof TKeyedArray
            || $this instanceof TList;
    }

    public function hasTraversableInterface(Codebase $codebase): bool
    {
        return $this instanceof TNamedObject
            && (
                strtolower($this->value) === 'traversable'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'Traversable'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'Traversable'
                    )))
                || (
                    $this->extra_types
                    && array_filter(
                        $this->extra_types,
                        function (Atomic $a) use ($codebase) : bool {
                            return $a->hasTraversableInterface($codebase);
                        }
                    )
                )
            );
    }

    public function hasCountableInterface(Codebase $codebase): bool
    {
        return $this instanceof TNamedObject
            && (
                strtolower($this->value) === 'countable'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'Countable'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'Countable'
                    )))
                || (
                    $this->extra_types
                    && array_filter(
                        $this->extra_types,
                        function (Atomic $a) use ($codebase) : bool {
                            return $a->hasCountableInterface($codebase);
                        }
                    )
                )
            );
    }

    public function isArrayAccessibleWithStringKey(Codebase $codebase): bool
    {
        return $this instanceof TArray
            || $this instanceof TKeyedArray
            || $this instanceof TList
            || $this instanceof Atomic\TClassStringMap
            || $this->hasArrayAccessInterface($codebase)
            || ($this instanceof TNamedObject && $this->value === 'SimpleXMLElement');
    }

    public function isArrayAccessibleWithIntOrStringKey(Codebase $codebase): bool
    {
        return $this instanceof TString
            || $this->isArrayAccessibleWithStringKey($codebase);
    }

    public function hasArrayAccessInterface(Codebase $codebase): bool
    {
        return $this instanceof TNamedObject
            && (
                strtolower($this->value) === 'arrayaccess'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'ArrayAccess'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'ArrayAccess'
                    )))
                || (
                    $this->extra_types
                    && array_filter(
                        $this->extra_types,
                        function (Atomic $a) use ($codebase) : bool {
                            return $a->hasArrayAccessInterface($codebase);
                        }
                    )
                )
            );
    }

    public function getChildNodes() : array
    {
        return [];
    }

    public function replaceClassLike(string $old, string $new) : void
    {
        if ($this instanceof TNamedObject) {
            if (strtolower($this->value) === $old) {
                $this->value = $new;
            }
        }

        if ($this instanceof TNamedObject
            || $this instanceof TIterable
            || $this instanceof TTemplateParam
        ) {
            if ($this->extra_types) {
                foreach ($this->extra_types as $extra_type) {
                    $extra_type->replaceClassLike($old, $new);
                }
            }
        }

        if ($this instanceof TScalarClassConstant) {
            if (strtolower($this->fq_classlike_name) === $old) {
                $this->fq_classlike_name = $new;
            }
        }

        if ($this instanceof TClassString && $this->as !== 'object') {
            if (strtolower($this->as) === $old) {
                $this->as = $new;
            }
        }

        if ($this instanceof TTemplateParam) {
            $this->as->replaceClassLike($old, $new);
        }

        if ($this instanceof TLiteralClassString) {
            if (strtolower($this->value) === $old) {
                $this->value = $new;
            }
        }

        if ($this instanceof Type\Atomic\TArray
            || $this instanceof Type\Atomic\TGenericObject
            || $this instanceof Type\Atomic\TIterable
        ) {
            foreach ($this->type_params as $type_param) {
                $type_param->replaceClassLike($old, $new);
            }
        }

        if ($this instanceof Type\Atomic\TKeyedArray) {
            foreach ($this->properties as $property_type) {
                $property_type->replaceClassLike($old, $new);
            }
        }

        if ($this instanceof Type\Atomic\TClosure
            || $this instanceof Type\Atomic\TCallable
        ) {
            if ($this->params) {
                foreach ($this->params as $param) {
                    if ($param->type) {
                        $param->type->replaceClassLike($old, $new);
                    }
                }
            }

            if ($this->return_type) {
                $this->return_type->replaceClassLike($old, $new);
            }
        }
    }

    public function __toString(): string
    {
        return '';
    }

    public function __clone()
    {
        if ($this instanceof TNamedObject
            || $this instanceof TTemplateParam
            || $this instanceof TIterable
            || $this instanceof Type\Atomic\TObjectWithProperties
        ) {
            if ($this->extra_types) {
                foreach ($this->extra_types as &$type) {
                    $type = clone $type;
                }
            }
        }

        if ($this instanceof TTemplateParam) {
            $this->as = clone $this->as;
        }
    }

    public function getId(bool $nested = false): string
    {
        return $this->__toString();
    }

    public function getAssertionString(): string
    {
        return $this->getId();
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
        return $this->getKey();
    }

    /**
     * @param  array<string, string> $aliased_classes
     */
    abstract public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string;

    abstract public function canBeFullyExpressedInPhp(): bool;

    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        ?Codebase $codebase = null,
        ?StatementsAnalyzer $statements_analyzer = null,
        Type\Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) : self {
        return $this;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        // do nothing
    }

    public function equals(Atomic $other_type): bool
    {
        return get_class($other_type) === get_class($this);
    }
}
