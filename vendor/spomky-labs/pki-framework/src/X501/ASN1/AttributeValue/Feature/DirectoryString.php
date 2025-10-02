<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1\AttributeValue\Feature;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BMPString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\PrintableString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\T61String;
use SpomkyLabs\Pki\ASN1\Type\Primitive\UniversalString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\UTF8String;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use SpomkyLabs\Pki\X501\DN\DNParser;
use SpomkyLabs\Pki\X501\MatchingRule\CaseIgnoreMatch;
use SpomkyLabs\Pki\X501\MatchingRule\MatchingRule;
use SpomkyLabs\Pki\X501\StringPrep\TranscodeStep;
use UnexpectedValueException;
use function array_key_exists;

/**
 * Base class for attribute values having *(Unbounded)DirectoryString* as a syntax.
 *
 * @see https://www.itu.int/ITU-T/formal-language/itu-t/x/x520/2012/SelectedAttributeTypes.html#SelectedAttributeTypes.UnboundedDirectoryString
 */
abstract class DirectoryString extends AttributeValue
{
    /**
     * Teletex string syntax.
     *
     * @var int
     */
    final public const TELETEX = Element::TYPE_T61_STRING;

    /**
     * Printable string syntax.
     *
     * @var int
     */
    final public const PRINTABLE = Element::TYPE_PRINTABLE_STRING;

    /**
     * BMP string syntax.
     *
     * @var int
     */
    final public const BMP = Element::TYPE_BMP_STRING;

    /**
     * Universal string syntax.
     *
     * @var int
     */
    final public const UNIVERSAL = Element::TYPE_UNIVERSAL_STRING;

    /**
     * UTF-8 string syntax.
     *
     * @var int
     */
    final public const UTF8 = Element::TYPE_UTF8_STRING;

    /**
     * Mapping from syntax enumeration to ASN.1 class name.
     *
     * @internal
     *
     * @var array<int, string>
     */
    private const MAP_TAG_TO_CLASS = [
        self::TELETEX => T61String::class,
        self::PRINTABLE => PrintableString::class,
        self::UNIVERSAL => UniversalString::class,
        self::UTF8 => UTF8String::class,
        self::BMP => BMPString::class,
    ];

    /**
     * @param string $_string String value
     * @param int $_stringTag Syntax choice
     */
    final protected function __construct(
        string $oid,
        protected string $_string,
        protected int $_stringTag
    ) {
        parent::__construct($oid);
    }

    abstract public static function create(string $value, int $string_tag = self::UTF8): static;

    /**
     * @return self
     */
    public static function fromASN1(UnspecifiedType $el): AttributeValue
    {
        $tag = $el->tag();
        // validate tag
        self::_tagToASN1Class($tag);
        return static::create($el->asString()->string(), $tag);
    }

    public function toASN1(): Element
    {
        $cls = self::_tagToASN1Class($this->_stringTag);
        return $cls::create($this->_string);
    }

    public function stringValue(): string
    {
        return $this->_string;
    }

    public function equalityMatchingRule(): MatchingRule
    {
        return CaseIgnoreMatch::create($this->_stringTag);
    }

    public function rfc2253String(): string
    {
        // TeletexString is encoded as binary
        if ($this->_stringTag === self::TELETEX) {
            return $this->_transcodedString();
        }
        return DNParser::escapeString($this->_transcodedString());
    }

    protected function _transcodedString(): string
    {
        return TranscodeStep::create($this->_stringTag)
            ->apply($this->_string)
        ;
    }

    /**
     * Get ASN.1 class name for given DirectoryString type tag.
     */
    private static function _tagToASN1Class(int $tag): string
    {
        if (! array_key_exists($tag, self::MAP_TAG_TO_CLASS)) {
            throw new UnexpectedValueException(
                sprintf('Type %s is not valid DirectoryString.', Element::tagToName($tag))
            );
        }
        return self::MAP_TAG_TO_CLASS[$tag];
    }
}
