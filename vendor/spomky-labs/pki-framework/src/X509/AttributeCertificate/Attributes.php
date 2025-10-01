<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate;

use SpomkyLabs\Pki\X501\ASN1\Attribute;
use SpomkyLabs\Pki\X501\ASN1\AttributeType;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use SpomkyLabs\Pki\X501\ASN1\Collection\SequenceOfAttributes;
use SpomkyLabs\Pki\X509\AttributeCertificate\Attribute\AccessIdentityAttributeValue;
use SpomkyLabs\Pki\X509\AttributeCertificate\Attribute\AuthenticationInfoAttributeValue;
use SpomkyLabs\Pki\X509\AttributeCertificate\Attribute\ChargingIdentityAttributeValue;
use SpomkyLabs\Pki\X509\AttributeCertificate\Attribute\GroupAttributeValue;
use SpomkyLabs\Pki\X509\AttributeCertificate\Attribute\RoleAttributeValue;

/**
 * Implements *Attributes* ASN.1 type of *AttributeCertificateInfo*.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.1
 * @see https://tools.ietf.org/html/rfc5755#section-4.2.7
 */
final class Attributes extends SequenceOfAttributes
{
    /**
     * Mapping from OID to attribute value class name.
     *
     * @internal
     *
     * @var array<string, string>
     */
    private const MAP_OID_TO_CLASS = [
        AccessIdentityAttributeValue::OID => AccessIdentityAttributeValue::class,
        AuthenticationInfoAttributeValue::OID => AuthenticationInfoAttributeValue::class,
        ChargingIdentityAttributeValue::OID => ChargingIdentityAttributeValue::class,
        GroupAttributeValue::OID => GroupAttributeValue::class,
        AttributeType::OID_ROLE => RoleAttributeValue::class,
    ];

    /**
     * Initialize from attribute values.
     *
     * @param AttributeValue ...$values List of attribute values
     */
    public static function fromAttributeValues(AttributeValue ...$values): static
    {
        return static::create(...array_map(static fn (AttributeValue $value) => $value->toAttribute(), $values));
    }

    /**
     * Check whether 'Access Identity' attribute is present.
     */
    public function hasAccessIdentity(): bool
    {
        return $this->has(AccessIdentityAttributeValue::OID);
    }

    /**
     * Get the first 'Access Identity' attribute value.
     */
    public function accessIdentity(): AccessIdentityAttributeValue
    {
        return $this->firstOf(AccessIdentityAttributeValue::OID)->first();
    }

    /**
     * Check whether 'Service Authentication Information' attribute is present.
     */
    public function hasAuthenticationInformation(): bool
    {
        return $this->has(AuthenticationInfoAttributeValue::OID);
    }

    /**
     * Get the first 'Service Authentication Information' attribute value.
     */
    public function authenticationInformation(): AuthenticationInfoAttributeValue
    {
        return $this->firstOf(AuthenticationInfoAttributeValue::OID)->first();
    }

    /**
     * Check whether 'Charging Identity' attribute is present.
     */
    public function hasChargingIdentity(): bool
    {
        return $this->has(ChargingIdentityAttributeValue::OID);
    }

    /**
     * Get the first 'Charging Identity' attribute value.
     */
    public function chargingIdentity(): ChargingIdentityAttributeValue
    {
        return $this->firstOf(ChargingIdentityAttributeValue::OID)->first();
    }

    /**
     * Check whether 'Group' attribute is present.
     */
    public function hasGroup(): bool
    {
        return $this->has(GroupAttributeValue::OID);
    }

    /**
     * Get the first 'Group' attribute value.
     */
    public function group(): GroupAttributeValue
    {
        return $this->firstOf(GroupAttributeValue::OID)->first();
    }

    /**
     * Check whether 'Role' attribute is present.
     */
    public function hasRole(): bool
    {
        return $this->has(AttributeType::OID_ROLE);
    }

    /**
     * Get the first 'Role' attribute value.
     */
    public function role(): RoleAttributeValue
    {
        return $this->firstOf(AttributeType::OID_ROLE)->first();
    }

    /**
     * Get all 'Role' attribute values.
     *
     * @return RoleAttributeValue[]
     */
    public function roles(): array
    {
        return array_merge(
            [],
            ...array_map(static fn (Attribute $attr) => $attr->values(), $this->allOf(AttributeType::OID_ROLE))
        );
    }

    protected static function _castAttributeValues(Attribute $attribute): Attribute
    {
        $oid = $attribute->oid();
        if (isset(self::MAP_OID_TO_CLASS[$oid])) {
            return $attribute->castValues(self::MAP_OID_TO_CLASS[$oid]);
        }
        return $attribute;
    }
}
