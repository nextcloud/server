<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\ListObject;
use CBOR\SignedIntegerObject;
use CBOR\TagObject as Base;
use CBOR\UnsignedIntegerObject;
use function count;
use function extension_loaded;
use InvalidArgumentException;
use RuntimeException;

final class BigFloatTag extends Base
{
    public function __construct(int $additionalInformation, ?string $data, CBORObject $object)
    {
        if (!extension_loaded('bcmath')) {
            throw new RuntimeException('The extension "bcmath" is required to use this tag');
        }
        parent::__construct($additionalInformation, $data, $object);
    }

    public static function getTagId(): int
    {
        return 5;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Base
    {
        if (!$object instanceof ListObject || 2 !== count($object)) {
            throw new InvalidArgumentException('This tag only accepts a ListObject object that contains an exponent and a mantissa.');
        }
        $e = $object->get(0);
        if (!$e instanceof UnsignedIntegerObject && !$e instanceof SignedIntegerObject) {
            throw new InvalidArgumentException('The exponent must be a Signed Integer or an Unsigned Integer object.');
        }
        $m = $object->get(1);
        if (!$m instanceof UnsignedIntegerObject && !$m instanceof SignedIntegerObject && !$m instanceof NegativeBigIntegerTag && !$m instanceof PositiveBigIntegerTag) {
            throw new InvalidArgumentException('The mantissa must be a Positive or Negative Signed Integer or an Unsigned Integer object.');
        }

        return new self(5, null, $object);
    }

    public static function createFromExponentAndMantissa(CBORObject $e, CBORObject $m): Base
    {
        $object = new ListObject();
        $object->add($e);
        $object->add($m);

        return self::create($object);
    }

    public function getNormalizedData(bool $ignoreTags = false)
    {
        if ($ignoreTags) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        if (!$this->object instanceof ListObject || 2 !== count($this->object)) {
            return $this->object->getNormalizedData($ignoreTags);
        }
        $e = $this->object->get(0);
        $m = $this->object->get(1);

        if (!$e instanceof UnsignedIntegerObject && !$e instanceof SignedIntegerObject) {
            return $this->object->getNormalizedData($ignoreTags);
        }
        if (!$m instanceof UnsignedIntegerObject && !$m instanceof SignedIntegerObject && !$m instanceof NegativeBigIntegerTag && !$m instanceof PositiveBigIntegerTag) {
            return $this->object->getNormalizedData($ignoreTags);
        }

        return rtrim(
            bcmul(
                $m->getNormalizedData($ignoreTags),
                bcpow(
                    '2',
                    $e->getNormalizedData($ignoreTags),
                    100),
                100),
            '0'
        );
    }
}
