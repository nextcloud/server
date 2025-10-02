<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1;

use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\Constructed;
use SpomkyLabs\Pki\ASN1\Type\Constructed\ConstructedString;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Set;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BMPString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Boolean;
use SpomkyLabs\Pki\ASN1\Type\Primitive\CharacterString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Enumerated;
use SpomkyLabs\Pki\ASN1\Type\Primitive\EOC;
use SpomkyLabs\Pki\ASN1\Type\Primitive\GeneralizedTime;
use SpomkyLabs\Pki\ASN1\Type\Primitive\GeneralString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\GraphicString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\IA5String;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\Primitive\NullType;
use SpomkyLabs\Pki\ASN1\Type\Primitive\NumericString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectDescriptor;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\PrintableString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Real;
use SpomkyLabs\Pki\ASN1\Type\Primitive\RelativeOID;
use SpomkyLabs\Pki\ASN1\Type\Primitive\T61String;
use SpomkyLabs\Pki\ASN1\Type\Primitive\UniversalString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\UTCTime;
use SpomkyLabs\Pki\ASN1\Type\Primitive\UTF8String;
use SpomkyLabs\Pki\ASN1\Type\Primitive\VideotexString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\VisibleString;
use SpomkyLabs\Pki\ASN1\Type\StringType;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ApplicationType;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ContextSpecificType;
use SpomkyLabs\Pki\ASN1\Type\Tagged\PrivateType;
use SpomkyLabs\Pki\ASN1\Type\TaggedType;
use SpomkyLabs\Pki\ASN1\Type\TimeType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use UnexpectedValueException;
use function array_key_exists;
use function mb_strlen;

/**
 * Base class for all ASN.1 type elements.
 * @see \SpomkyLabs\Pki\Test\ASN1\ElementTest
 */
abstract class Element implements ElementBase
{
    // Universal type tags
    public const TYPE_EOC = 0x00;

    public const TYPE_BOOLEAN = 0x01;

    public const TYPE_INTEGER = 0x02;

    public const TYPE_BIT_STRING = 0x03;

    public const TYPE_OCTET_STRING = 0x04;

    public const TYPE_NULL = 0x05;

    public const TYPE_OBJECT_IDENTIFIER = 0x06;

    public const TYPE_OBJECT_DESCRIPTOR = 0x07;

    public const TYPE_EXTERNAL = 0x08;

    public const TYPE_REAL = 0x09;

    public const TYPE_ENUMERATED = 0x0a;

    public const TYPE_EMBEDDED_PDV = 0x0b;

    public const TYPE_UTF8_STRING = 0x0c;

    public const TYPE_RELATIVE_OID = 0x0d;

    public const TYPE_SEQUENCE = 0x10;

    public const TYPE_SET = 0x11;

    public const TYPE_NUMERIC_STRING = 0x12;

    public const TYPE_PRINTABLE_STRING = 0x13;

    public const TYPE_T61_STRING = 0x14;

    public const TYPE_VIDEOTEX_STRING = 0x15;

    public const TYPE_IA5_STRING = 0x16;

    public const TYPE_UTC_TIME = 0x17;

    public const TYPE_GENERALIZED_TIME = 0x18;

    public const TYPE_GRAPHIC_STRING = 0x19;

    public const TYPE_VISIBLE_STRING = 0x1a;

    public const TYPE_GENERAL_STRING = 0x1b;

    public const TYPE_UNIVERSAL_STRING = 0x1c;

    public const TYPE_CHARACTER_STRING = 0x1d;

    public const TYPE_BMP_STRING = 0x1e;

    /**
     * Pseudotype for all string types.
     *
     * May be used as an expectation parameter.
     *
     * @var int
     */
    public const TYPE_STRING = -1;

    /**
     * Pseudotype for all time types.
     *
     * May be used as an expectation parameter.
     *
     * @var int
     */
    public const TYPE_TIME = -2;

    /**
     * Pseudotype for constructed strings.
     *
     * May be used as an expectation parameter.
     *
     * @var int
     */
    public const TYPE_CONSTRUCTED_STRING = -3;

    /**
     * Mapping from universal type tag to implementation class name.
     *
     * @internal
     *
     * @var array<int, string>
     */
    private const MAP_TAG_TO_CLASS = [
        self::TYPE_EOC => EOC::class,
        self::TYPE_BOOLEAN => Boolean::class,
        self::TYPE_INTEGER => Integer::class,
        self::TYPE_BIT_STRING => BitString::class,
        self::TYPE_OCTET_STRING => OctetString::class,
        self::TYPE_NULL => NullType::class,
        self::TYPE_OBJECT_IDENTIFIER => ObjectIdentifier::class,
        self::TYPE_OBJECT_DESCRIPTOR => ObjectDescriptor::class,
        self::TYPE_REAL => Real::class,
        self::TYPE_ENUMERATED => Enumerated::class,
        self::TYPE_UTF8_STRING => UTF8String::class,
        self::TYPE_RELATIVE_OID => RelativeOID::class,
        self::TYPE_SEQUENCE => Sequence::class,
        self::TYPE_SET => Set::class,
        self::TYPE_NUMERIC_STRING => NumericString::class,
        self::TYPE_PRINTABLE_STRING => PrintableString::class,
        self::TYPE_T61_STRING => T61String::class,
        self::TYPE_VIDEOTEX_STRING => VideotexString::class,
        self::TYPE_IA5_STRING => IA5String::class,
        self::TYPE_UTC_TIME => UTCTime::class,
        self::TYPE_GENERALIZED_TIME => GeneralizedTime::class,
        self::TYPE_GRAPHIC_STRING => GraphicString::class,
        self::TYPE_VISIBLE_STRING => VisibleString::class,
        self::TYPE_GENERAL_STRING => GeneralString::class,
        self::TYPE_UNIVERSAL_STRING => UniversalString::class,
        self::TYPE_CHARACTER_STRING => CharacterString::class,
        self::TYPE_BMP_STRING => BMPString::class,
    ];

    /**
     * Mapping from universal type tag to human-readable name.
     *
     * @internal
     *
     * @var array<int, string>
     */
    private const MAP_TYPE_TO_NAME = [
        self::TYPE_EOC => 'EOC',
        self::TYPE_BOOLEAN => 'BOOLEAN',
        self::TYPE_INTEGER => 'INTEGER',
        self::TYPE_BIT_STRING => 'BIT STRING',
        self::TYPE_OCTET_STRING => 'OCTET STRING',
        self::TYPE_NULL => 'NULL',
        self::TYPE_OBJECT_IDENTIFIER => 'OBJECT IDENTIFIER',
        self::TYPE_OBJECT_DESCRIPTOR => 'ObjectDescriptor',
        self::TYPE_EXTERNAL => 'EXTERNAL',
        self::TYPE_REAL => 'REAL',
        self::TYPE_ENUMERATED => 'ENUMERATED',
        self::TYPE_EMBEDDED_PDV => 'EMBEDDED PDV',
        self::TYPE_UTF8_STRING => 'UTF8String',
        self::TYPE_RELATIVE_OID => 'RELATIVE-OID',
        self::TYPE_SEQUENCE => 'SEQUENCE',
        self::TYPE_SET => 'SET',
        self::TYPE_NUMERIC_STRING => 'NumericString',
        self::TYPE_PRINTABLE_STRING => 'PrintableString',
        self::TYPE_T61_STRING => 'T61String',
        self::TYPE_VIDEOTEX_STRING => 'VideotexString',
        self::TYPE_IA5_STRING => 'IA5String',
        self::TYPE_UTC_TIME => 'UTCTime',
        self::TYPE_GENERALIZED_TIME => 'GeneralizedTime',
        self::TYPE_GRAPHIC_STRING => 'GraphicString',
        self::TYPE_VISIBLE_STRING => 'VisibleString',
        self::TYPE_GENERAL_STRING => 'GeneralString',
        self::TYPE_UNIVERSAL_STRING => 'UniversalString',
        self::TYPE_CHARACTER_STRING => 'CHARACTER STRING',
        self::TYPE_BMP_STRING => 'BMPString',
        self::TYPE_STRING => 'Any String',
        self::TYPE_TIME => 'Any Time',
        self::TYPE_CONSTRUCTED_STRING => 'Constructed String',
    ];

    /**
     * @param bool $indefiniteLength Whether type shall be encoded with indefinite length.
     */
    protected function __construct(
        protected readonly int $typeTag,
        protected bool $indefiniteLength = false
    ) {
    }

    abstract public function typeClass(): int;

    abstract public function isConstructed(): bool;

    /**
     * Decode element from DER data.
     *
     * @param string $data DER encoded data
     * @param null|int $offset Reference to the variable that contains offset
     * into the data where to start parsing.
     * Variable is updated to the offset next to the
     * parsed element. If null, start from offset 0.
     */
    public static function fromDER(string $data, int &$offset = null): static
    {
        $idx = $offset ?? 0;
        // decode identifier
        $identifier = Identifier::fromDER($data, $idx);
        // determine class that implements type specific decoding
        $cls = self::determineImplClass($identifier);
        // decode remaining element
        $element = $cls::decodeFromDER($identifier, $data, $idx);
        // if called in the context of a concrete class, check
        // that decoded type matches the type of calling class
        $called_class = static::class;
        if ($called_class !== self::class) {
            if (! $element instanceof $called_class) {
                throw new UnexpectedValueException(sprintf('%s expected, got %s.', $called_class, $element::class));
            }
        }
        // update offset for the caller
        if (isset($offset)) {
            $offset = $idx;
        }
        return $element;
    }

    public function toDER(): string
    {
        $identifier = Identifier::create(
            $this->typeClass(),
            $this->isConstructed() ? Identifier::CONSTRUCTED : Identifier::PRIMITIVE,
            $this->typeTag
        );
        $content = $this->encodedAsDER();
        if ($this->indefiniteLength) {
            $length = Length::create(0, true);
            $eoc = EOC::create();
            return $identifier->toDER() . $length->toDER() . $content . $eoc->toDER();
        }
        $length = Length::create(mb_strlen($content, '8bit'));
        return $identifier->toDER() . $length->toDER() . $content;
    }

    public function tag(): int
    {
        return $this->typeTag;
    }

    public function isType(int $tag): bool
    {
        // if element is context specific
        if ($this->typeClass() === Identifier::CLASS_CONTEXT_SPECIFIC) {
            return false;
        }
        // negative tags identify an abstract pseudotype
        if ($tag < 0) {
            return $this->isPseudoType($tag);
        }
        return $this->isConcreteType($tag);
    }

    public function expectType(int $tag): ElementBase
    {
        if (! $this->isType($tag)) {
            throw new UnexpectedValueException(
                sprintf('%s expected, got %s.', self::tagToName($tag), $this->typeDescriptorString())
            );
        }
        return $this;
    }

    public function isTagged(): bool
    {
        return $this instanceof TaggedType;
    }

    public function expectTagged(?int $tag = null): TaggedType
    {
        if (! $this->isTagged()) {
            throw new UnexpectedValueException(
                sprintf('Context specific element expected, got %s.', Identifier::classToName($this->typeClass()))
            );
        }
        if (isset($tag) && $this->tag() !== $tag) {
            throw new UnexpectedValueException(sprintf('Tag %d expected, got %d.', $tag, $this->tag()));
        }
        return $this;
    }

    /**
     * Whether element has indefinite length.
     */
    public function hasIndefiniteLength(): bool
    {
        return $this->indefiniteLength;
    }

    /**
     * Get self with indefinite length encoding set.
     *
     * @param bool $indefinite True for indefinite length, false for definite length
     */
    public function withIndefiniteLength(bool $indefinite = true): self
    {
        $obj = clone $this;
        $obj->indefiniteLength = $indefinite;
        return $obj;
    }

    final public function asElement(): self
    {
        return $this;
    }

    /**
     * Get element decorated with `UnspecifiedType` object.
     */
    public function asUnspecified(): UnspecifiedType
    {
        return UnspecifiedType::create($this);
    }

    /**
     * Get human readable name for an universal tag.
     */
    public static function tagToName(int $tag): string
    {
        if (! array_key_exists($tag, self::MAP_TYPE_TO_NAME)) {
            return "TAG {$tag}";
        }
        return self::MAP_TYPE_TO_NAME[$tag];
    }

    /**
     * Get the content encoded in DER.
     *
     * Returns the DER encoded content without identifier and length header octets.
     */
    abstract protected function encodedAsDER(): string;

    /**
     * Decode type-specific element from DER.
     *
     * @param Identifier $identifier Pre-parsed identifier
     * @param string $data DER data
     * @param int $offset Offset in data to the next byte after identifier
     */
    abstract protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase;

    /**
     * Determine the class that implements the type.
     *
     * @return string Class name
     */
    protected static function determineImplClass(Identifier $identifier): string
    {
        switch ($identifier->typeClass()) {
            case Identifier::CLASS_UNIVERSAL:
                $cls = self::determineUniversalImplClass($identifier->intTag());
                // constructed strings may be present in BER
                if ($identifier->isConstructed()
                    && is_subclass_of($cls, StringType::class)) {
                    $cls = ConstructedString::class;
                }
                return $cls;
            case Identifier::CLASS_CONTEXT_SPECIFIC:
                return ContextSpecificType::class;
            case Identifier::CLASS_APPLICATION:
                return ApplicationType::class;
            case Identifier::CLASS_PRIVATE:
                return PrivateType::class;
        }
        throw new UnexpectedValueException(sprintf(
            '%s %d not implemented.',
            Identifier::classToName($identifier->typeClass()),
            $identifier->tag()
        ));
    }

    /**
     * Determine the class that implements an universal type of the given tag.
     *
     * @return string Class name
     */
    protected static function determineUniversalImplClass(int $tag): string
    {
        if (! array_key_exists($tag, self::MAP_TAG_TO_CLASS)) {
            throw new UnexpectedValueException("Universal tag {$tag} not implemented.");
        }
        return self::MAP_TAG_TO_CLASS[$tag];
    }

    /**
     * Get textual description of the type for debugging purposes.
     */
    protected function typeDescriptorString(): string
    {
        if ($this->typeClass() === Identifier::CLASS_UNIVERSAL) {
            return self::tagToName($this->typeTag);
        }
        return sprintf('%s TAG %d', Identifier::classToName($this->typeClass()), $this->typeTag);
    }

    /**
     * Check whether the element is a concrete type of given tag.
     */
    private function isConcreteType(int $tag): bool
    {
        // if tag doesn't match
        if ($this->tag() !== $tag) {
            return false;
        }
        // if type is universal check that instance is of a correct class
        if ($this->typeClass() === Identifier::CLASS_UNIVERSAL) {
            $cls = self::determineUniversalImplClass($tag);
            if (! $this instanceof $cls) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check whether the element is a pseudotype.
     */
    private function isPseudoType(int $tag): bool
    {
        return match ($tag) {
            self::TYPE_STRING => $this instanceof StringType,
            self::TYPE_TIME => $this instanceof TimeType,
            self::TYPE_CONSTRUCTED_STRING => $this instanceof ConstructedString,
            default => false,
        };
    }
}
