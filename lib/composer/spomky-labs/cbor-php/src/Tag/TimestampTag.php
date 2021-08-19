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
use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use CBOR\TagObject as Base;
use CBOR\UnsignedIntegerObject;
use DateTimeImmutable;
use InvalidArgumentException;
use function strval;

final class TimestampTag extends Base
{
    public static function getTagId(): int
    {
        return 1;
    }

    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    public static function create(CBORObject $object): Base
    {
        if (!$object instanceof UnsignedIntegerObject && !$object instanceof HalfPrecisionFloatObject && !$object instanceof SinglePrecisionFloatObject && !$object instanceof DoublePrecisionFloatObject) {
            throw new InvalidArgumentException('This tag only accepts a Byte String object.');
        }

        return new self(1, null, $object);
    }

    public function getNormalizedData(bool $ignoreTags = false)
    {
        if ($ignoreTags) {
            return $this->object->getNormalizedData($ignoreTags);
        }
        switch (true) {
            case $this->object instanceof UnsignedIntegerObject:
                return DateTimeImmutable::createFromFormat('U', strval($this->object->getNormalizedData($ignoreTags)));
            case $this->object instanceof HalfPrecisionFloatObject:
            case $this->object instanceof SinglePrecisionFloatObject:
            case $this->object instanceof DoublePrecisionFloatObject:
                return DateTimeImmutable::createFromFormat('U.u', strval($this->object->getNormalizedData($ignoreTags)));
            default:
                return $this->object->getNormalizedData($ignoreTags);
        }
    }
}
