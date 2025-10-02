<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1\AttributeValue;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\Attribute;
use SpomkyLabs\Pki\X501\ASN1\AttributeType;
use SpomkyLabs\Pki\X501\ASN1\AttributeTypeAndValue;
use SpomkyLabs\Pki\X501\MatchingRule\MatchingRule;
use Stringable;
use function array_key_exists;

/**
 * Base class for attribute values.
 *
 * @see https://www.itu.int/ITU-T/formal-language/itu-t/x/x501/2012/InformationFramework.html#InformationFramework.AttributeValue
 */
abstract class AttributeValue implements Stringable
{
    /**
     * Mapping from attribute type OID to attribute value class name.
     *
     * @internal
     *
     * @var array<string, string>
     */
    private const MAP_OID_TO_CLASS = [
        AttributeType::OID_COMMON_NAME => CommonNameValue::class,
        AttributeType::OID_SURNAME => SurnameValue::class,
        AttributeType::OID_SERIAL_NUMBER => SerialNumberValue::class,
        AttributeType::OID_COUNTRY_NAME => CountryNameValue::class,
        AttributeType::OID_LOCALITY_NAME => LocalityNameValue::class,
        AttributeType::OID_STATE_OR_PROVINCE_NAME => StateOrProvinceNameValue::class,
        AttributeType::OID_ORGANIZATION_NAME => OrganizationNameValue::class,
        AttributeType::OID_ORGANIZATIONAL_UNIT_NAME => OrganizationalUnitNameValue::class,
        AttributeType::OID_TITLE => TitleValue::class,
        AttributeType::OID_DESCRIPTION => DescriptionValue::class,
        AttributeType::OID_NAME => NameValue::class,
        AttributeType::OID_GIVEN_NAME => GivenNameValue::class,
        AttributeType::OID_PSEUDONYM => PseudonymValue::class,
    ];

    /**
     * @param string $oid OID of the attribute type.
     */
    protected function __construct(
        protected string $oid
    ) {
    }

    /**
     * Get attribute value as an UTF-8 encoded string.
     */
    public function __toString(): string
    {
        return $this->_transcodedString();
    }

    /**
     * Generate ASN.1 element.
     */
    abstract public function toASN1(): Element;

    /**
     * Get attribute value as a string.
     */
    abstract public function stringValue(): string;

    /**
     * Get matching rule for equality comparison.
     */
    abstract public function equalityMatchingRule(): MatchingRule;

    /**
     * Get attribute value as a string conforming to RFC 2253.
     *
     * @see https://tools.ietf.org/html/rfc2253#section-2.4
     */
    abstract public function rfc2253String(): string;

    /**
     * Initialize from ASN.1.
     */
    abstract public static function fromASN1(UnspecifiedType $el): self;

    /**
     * Initialize from ASN.1 with given OID hint.
     *
     * @param string $oid Attribute's OID
     */
    public static function fromASN1ByOID(string $oid, UnspecifiedType $el): self
    {
        if (! array_key_exists($oid, self::MAP_OID_TO_CLASS)) {
            return new UnknownAttributeValue($oid, $el->asElement());
        }
        $cls = self::MAP_OID_TO_CLASS[$oid];
        return $cls::fromASN1($el);
    }

    /**
     * Initialize from another AttributeValue.
     *
     * This method is generally used to cast UnknownAttributeValue to specific object when class is declared outside
     * this package.
     *
     * @param self $obj Instance of AttributeValue
     */
    public static function fromSelf(self $obj): self
    {
        return static::fromASN1($obj->toASN1()->asUnspecified());
    }

    /**
     * Get attribute type's OID.
     */
    public function oid(): string
    {
        return $this->oid;
    }

    /**
     * Get Attribute object with this as a single value.
     */
    public function toAttribute(): Attribute
    {
        return Attribute::fromAttributeValues($this);
    }

    /**
     * Get AttributeTypeAndValue object with this as a value.
     */
    public function toAttributeTypeAndValue(): AttributeTypeAndValue
    {
        return AttributeTypeAndValue::fromAttributeValue($this);
    }

    /**
     * Get attribute value as an UTF-8 string conforming to RFC 4518.
     *
     * @see https://tools.ietf.org/html/rfc4518#section-2.1
     */
    abstract protected function _transcodedString(): string;
}
