<?php

declare(strict_types=1);

namespace CBOR;

use CBOR\Tag\TagInterface;
use InvalidArgumentException;

abstract class Tag extends AbstractCBORObject implements TagInterface
{
    private const MAJOR_TYPE = self::MAJOR_TYPE_TAG;

    public function __construct(
        int $additionalInformation,
        protected ?string $data,
        protected CBORObject $object
    ) {
        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        if ($this->data !== null) {
            $result .= $this->data;
        }

        return $result . $this->object;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getValue(): CBORObject
    {
        return $this->object;
    }

    /**
     * @return array{int, null|string}
     */
    protected static function determineComponents(int $tag): array
    {
        switch (true) {
            case $tag < 0:
                throw new InvalidArgumentException('The value must be a positive integer.');
            case $tag < 24:
                return [$tag, null];
            case $tag < 0xFF:
                return [24, self::hex2bin(dechex($tag))];
            case $tag < 0xFFFF:
                return [25, self::hex2bin(dechex($tag))];
            case $tag < 0xFFFFFFFF:
                return [26, self::hex2bin(dechex($tag))];
            default:
                throw new InvalidArgumentException(
                    'Out of range. Please use PositiveBigIntegerTag tag with ByteStringObject object instead.'
                );
        }
    }

    private static function hex2bin(string $data): string
    {
        $result = hex2bin($data);
        if ($result === false) {
            throw new InvalidArgumentException('Unable to convert the data');
        }

        return $result;
    }
}
