<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\CertificationRequest;

use LogicException;
use SpomkyLabs\Pki\X501\ASN1\Attribute;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use SpomkyLabs\Pki\X501\ASN1\Collection\SetOfAttributes;
use SpomkyLabs\Pki\X509\CertificationRequest\Attribute\ExtensionRequestValue;

/**
 * Implements *Attributes* ASN.1 type of *CertificationRequestInfo*.
 *
 * @see https://tools.ietf.org/html/rfc2986#section-4
 */
final class Attributes extends SetOfAttributes
{
    /**
     * Mapping from OID to attribute value class name.
     *
     * @internal
     *
     * @var array<string, string>
     */
    private const MAP_OID_TO_CLASS = [
        ExtensionRequestValue::OID => ExtensionRequestValue::class,
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
     * Check whether extension request attribute is present.
     */
    public function hasExtensionRequest(): bool
    {
        return $this->has(ExtensionRequestValue::OID);
    }

    /**
     * Get extension request attribute value.
     */
    public function extensionRequest(): ExtensionRequestValue
    {
        if (! $this->hasExtensionRequest()) {
            throw new LogicException('No extension request attribute.');
        }
        return $this->firstOf(ExtensionRequestValue::OID)->first();
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
