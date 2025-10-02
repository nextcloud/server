<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use Brick\Math\BigInteger;
use RuntimeException;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\PrimitiveType;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;
use Throwable;
use UnexpectedValueException;
use function chr;
use function count;
use function is_int;
use function mb_strlen;
use function ord;

/**
 * Implements *OBJECT IDENTIFIER* type.
 */
final class ObjectIdentifier extends Element
{
    use UniversalClass;
    use PrimitiveType;

    /**
     * Object identifier split to sub ID's.
     *
     * @var BigInteger[]
     */
    private readonly array $subids;

    /**
     * @param string $oid OID in dotted format
     */
    private function __construct(
        private readonly string $oid,
        ?int $typeTag
    ) {
        $this->subids = self::explodeDottedOID($oid);
        // if OID is non-empty
        if (count($this->subids) > 0) {
            // check that at least two nodes are set
            if (count($this->subids) < 2) {
                throw new UnexpectedValueException('OID must have at least two nodes.');
            }
            // check that root arc is in 0..2 range
            if ($this->subids[0]->isGreaterThan(2)) {
                throw new UnexpectedValueException('Root arc must be in range of 0..2.');
            }
            // if root arc is 0 or 1, second node must be in 0..39 range
            if ($this->subids[0]->isLessThan(2) && $this->subids[1]->isGreaterThanOrEqualTo(40)) {
                throw new UnexpectedValueException('Second node must be in 0..39 range for root arcs 0 and 1.');
            }
        }
        parent::__construct($typeTag ?? self::TYPE_OBJECT_IDENTIFIER);
    }

    public static function create(string $oid, ?int $typeTag = null): self
    {
        return new self($oid, $typeTag);
    }

    /**
     * Get OID in dotted format.
     */
    public function oid(): string
    {
        return $this->oid;
    }

    protected function encodedAsDER(): string
    {
        $subids = $this->subids;
        // encode first two subids to one according to spec section 8.19.4
        if (count($subids) >= 2) {
            $num = $subids[0]->multipliedBy(40)->plus($subids[1]);
            array_splice($subids, 0, 2, [$num]);
        }
        return self::encodeSubIDs(...$subids);
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        $idx = $offset;
        $len = Length::expectFromDER($data, $idx)->intLength();
        $subids = self::decodeSubIDs(mb_substr($data, $idx, $len, '8bit'));
        $idx += $len;
        // decode first subidentifier according to spec section 8.19.4
        if (isset($subids[0])) {
            if ($subids[0]->isLessThan(80)) {
                [$x, $y] = $subids[0]->quotientAndRemainder(40);
            } else {
                $x = BigInteger::of(2);
                $y = $subids[0]->minus(80);
            }
            array_splice($subids, 0, 1, [$x, $y]);
        }
        $offset = $idx;
        return self::create(self::implodeSubIDs(...$subids));
    }

    /**
     * Explode dotted OID to an array of sub ID's.
     *
     * @param string $oid OID in dotted format
     *
     * @return BigInteger[] Array of BigInteger numbers
     */
    protected static function explodeDottedOID(string $oid): array
    {
        $subids = [];
        if ($oid !== '') {
            foreach (explode('.', $oid) as $subid) {
                try {
                    $n = BigInteger::of($subid);
                    $subids[] = $n;
                } catch (Throwable $e) {
                    throw new UnexpectedValueException(sprintf('"%s" is not a number.', $subid), 0, $e);
                }
            }
        }
        return $subids;
    }

    /**
     * Implode an array of sub IDs to dotted OID format.
     */
    protected static function implodeSubIDs(BigInteger ...$subids): string
    {
        return implode('.', array_map(static fn ($num) => $num->toBase(10), $subids));
    }

    /**
     * Encode sub ID's to DER.
     */
    protected static function encodeSubIDs(BigInteger ...$subids): string
    {
        $data = '';
        foreach ($subids as $subid) {
            // if number fits to one base 128 byte
            if ($subid->isLessThan(128)) {
                $data .= chr($subid->toInt());
            } else { // encode to multiple bytes
                $bytes = [];
                do {
                    array_unshift($bytes, 0x7f & $subid->toInt());
                    $subid = $subid->shiftedRight(7);
                } while ($subid->isGreaterThan(0));
                // all bytes except last must have bit 8 set to one
                foreach (array_splice($bytes, 0, -1) as $byte) {
                    $data .= chr(0x80 | $byte);
                }
                $byte = reset($bytes);
                if (! is_int($byte)) {
                    throw new RuntimeException('Encoding failed');
                }
                $data .= chr($byte);
            }
        }
        return $data;
    }

    /**
     * Decode sub ID's from DER data.
     *
     * @return BigInteger[] Array of BigInteger numbers
     */
    protected static function decodeSubIDs(string $data): array
    {
        $subids = [];
        $idx = 0;
        $end = mb_strlen($data, '8bit');
        while ($idx < $end) {
            $num = BigInteger::of(0);
            while (true) {
                if ($idx >= $end) {
                    throw new DecodeException('Unexpected end of data.');
                }
                $byte = ord($data[$idx++]);
                $num = $num->or($byte & 0x7f);
                // bit 8 of the last octet is zero
                if (0 === ($byte & 0x80)) {
                    break;
                }
                $num = $num->shiftedLeft(7);
            }
            $subids[] = $num;
        }
        return $subids;
    }
}
