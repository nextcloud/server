<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use SpomkyLabs\Pki\ASN1\Type\PrimitiveString;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;

/**
 * Implements *ObjectDescriptor* type.
 */
final class ObjectDescriptor extends PrimitiveString
{
    use UniversalClass;

    private function __construct(string $descriptor)
    {
        parent::__construct(self::TYPE_OBJECT_DESCRIPTOR, $descriptor);
    }

    public static function create(string $descriptor): self
    {
        return new self($descriptor);
    }

    /**
     * Get the object descriptor.
     */
    public function descriptor(): string
    {
        return $this->string();
    }
}
