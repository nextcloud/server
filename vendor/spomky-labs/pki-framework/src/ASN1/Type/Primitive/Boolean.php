<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\PrimitiveType;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;
use function chr;
use function ord;

/**
 * Implements *BOOLEAN* type.
 */
final class Boolean extends Element
{
    use UniversalClass;
    use PrimitiveType;

    private function __construct(
        private readonly bool $_bool
    ) {
        parent::__construct(self::TYPE_BOOLEAN);
    }

    public static function create(bool $_bool): self
    {
        return new self($_bool);
    }

    /**
     * Get the value.
     */
    public function value(): bool
    {
        return $this->_bool;
    }

    protected function encodedAsDER(): string
    {
        return $this->_bool ? chr(0xff) : chr(0);
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        $idx = $offset;
        Length::expectFromDER($data, $idx, 1);
        $byte = ord($data[$idx++]);
        if ($byte !== 0) {
            if ($byte !== 0xff) {
                throw new DecodeException('DER encoded boolean true must have all bits set to 1.');
            }
        }
        $offset = $idx;
        return self::create($byte !== 0);
    }
}
