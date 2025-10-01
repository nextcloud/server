<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\Attribute;

use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use SpomkyLabs\Pki\X501\ASN1\Collection\SetOfAttributes;

/**
 * Implements *OneAsymmetricKey*'s *Attribute* ASN.1 type.
 */
final class OneAsymmetricKeyAttributes extends SetOfAttributes
{
    /**
     * Initialize from attribute values.
     *
     * @param AttributeValue ...$values List of attribute values
     */
    public static function fromAttributeValues(AttributeValue ...$values): static
    {
        return static::create(...array_map(static fn (AttributeValue $value) => $value->toAttribute(), $values));
    }

    // Nothing yet. Extended from base class for future extensions.
}
