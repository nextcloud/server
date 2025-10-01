<?php

declare(strict_types=1);

namespace CBOR;

interface CBORObject
{
    public const MAJOR_TYPE_UNSIGNED_INTEGER = 0b000;

    public const MAJOR_TYPE_NEGATIVE_INTEGER = 0b001;

    public const MAJOR_TYPE_BYTE_STRING = 0b010;

    public const MAJOR_TYPE_TEXT_STRING = 0b011;

    public const MAJOR_TYPE_LIST = 0b100;

    public const MAJOR_TYPE_MAP = 0b101;

    public const MAJOR_TYPE_TAG = 0b110;

    public const MAJOR_TYPE_OTHER_TYPE = 0b111;

    public const LENGTH_1_BYTE = 0b00011000;

    public const LENGTH_2_BYTES = 0b00011001;

    public const LENGTH_4_BYTES = 0b00011010;

    public const LENGTH_8_BYTES = 0b00011011;

    public const LENGTH_INDEFINITE = 0b00011111;

    public const FUTURE_USE_1 = 0b00011100;

    public const FUTURE_USE_2 = 0b00011101;

    public const FUTURE_USE_3 = 0b00011110;

    public const OBJECT_FALSE = 20;

    public const OBJECT_TRUE = 21;

    public const OBJECT_NULL = 22;

    public const OBJECT_UNDEFINED = 23;

    public const OBJECT_SIMPLE_VALUE = 24;

    public const OBJECT_HALF_PRECISION_FLOAT = 25;

    public const OBJECT_SINGLE_PRECISION_FLOAT = 26;

    public const OBJECT_DOUBLE_PRECISION_FLOAT = 27;

    public const OBJECT_BREAK = 0b00011111;

    public const TAG_STANDARD_DATETIME = 0;

    public const TAG_EPOCH_DATETIME = 1;

    public const TAG_UNSIGNED_BIG_NUM = 2;

    public const TAG_NEGATIVE_BIG_NUM = 3;

    public const TAG_DECIMAL_FRACTION = 4;

    public const TAG_BIG_FLOAT = 5;

    public const TAG_ENCODED_BASE64_URL = 21;

    public const TAG_ENCODED_BASE64 = 22;

    public const TAG_ENCODED_BASE16 = 23;

    public const TAG_ENCODED_CBOR = 24;

    public const TAG_URI = 32;

    public const TAG_BASE64_URL = 33;

    public const TAG_BASE64 = 34;

    public const TAG_MIME = 36;

    public const TAG_CBOR = 55799;

    public function __toString(): string;

    public function getMajorType(): int;

    public function getAdditionalInformation(): int;
}
