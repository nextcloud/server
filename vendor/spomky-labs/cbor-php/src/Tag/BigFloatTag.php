<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\ListObject;
use CBOR\NegativeIntegerObject;
use CBOR\Normalizable;
use CBOR\Tag;
use CBOR\UnsignedIntegerObject;
use InvalidArgumentException;
use RuntimeException;
use function count;
use function extension_loaded;

final class BigFloatTag extends Tag implements Normalizable
{
    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        if (! extension_loaded('bcmath')) {
            throw new RuntimeException('The extension "bcmath" is required to use this tag');
        }

        if (! $object instanceof ListObject || count($object) !== 2) {
            throw new InvalidArgumentException(
                'This tag only accepts a ListObject object that contains an exponent and a mantissa.'
            );
        }
        $e = $object->get(0);
        if (! $e instanceof UnsignedIntegerObject && ! $e instanceof NegativeIntegerObject) {
            throw new InvalidArgumentException('The exponent must be a Signed Integer or an Unsigned Integer object.');
        }
        $m = $object->get(1);
        if (! $m instanceof UnsignedIntegerObject && ! $m instanceof NegativeIntegerObject && ! $m instanceof NegativeBigIntegerTag && ! $m instanceof UnsignedBigIntegerTag) {
            throw new InvalidArgumentException(
                'The mantissa must be a Positive or Negative Signed Integer or an Unsigned Integer object.'
            );
        }

        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return self::TAG_BIG_FLOAT;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Tag
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Tag
    {
        [$ai, $data] = self::determineComponents(self::TAG_BIG_FLOAT);

        return new self($ai, $data, $object);
    }

    public static function createFromExponentAndMantissa(CBORObject $e, CBORObject $m): Tag
    {
        $object = ListObject::create()
            ->add($e)
            ->add($m)
        ;

        return self::create($object);
    }

    public function normalize()
    {
        /** @var ListObject $object */
        $object = $this->object;
        /** @var UnsignedIntegerObject|NegativeIntegerObject $e */
        $e = $object->get(0);
        /** @var UnsignedIntegerObject|NegativeIntegerObject|NegativeBigIntegerTag|UnsignedBigIntegerTag $m */
        $m = $object->get(1);

        return rtrim(bcmul($m->normalize(), bcpow('2', $e->normalize(), 100), 100), '0');
    }
}
