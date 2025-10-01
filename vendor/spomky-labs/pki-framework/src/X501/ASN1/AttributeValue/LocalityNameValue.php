<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1\AttributeValue;

use SpomkyLabs\Pki\X501\ASN1\AttributeType;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\Feature\DirectoryString;

/**
 * 'localityName' attribute value.
 *
 * @see https://www.itu.int/ITU-T/formal-language/itu-t/x/x520/2012/SelectedAttributeTypes.html#SelectedAttributeTypes.localityName
 */
final class LocalityNameValue extends DirectoryString
{
    public static function create(string $value, int $string_tag = DirectoryString::UTF8): static
    {
        return new static(AttributeType::OID_LOCALITY_NAME, $value, $string_tag);
    }
}
