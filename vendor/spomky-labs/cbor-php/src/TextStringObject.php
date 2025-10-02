<?php

declare(strict_types=1);

namespace CBOR;

/**
 * @see \CBOR\Test\TextStringObjectTest
 */
final class TextStringObject extends AbstractCBORObject implements Normalizable
{
    private const MAJOR_TYPE = self::MAJOR_TYPE_TEXT_STRING;

    private ?string $length = null;

    private string $data;

    public function __construct(string $data)
    {
        [$additionalInformation, $length] = LengthCalculator::getLengthOfString($data);

        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
        $this->data = $data;
        $this->length = $length;
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        if ($this->length !== null) {
            $result .= $this->length;
        }

        return $result . $this->data;
    }

    public static function create(string $data): self
    {
        return new self($data);
    }

    public function getValue(): string
    {
        return $this->data;
    }

    public function getLength(): int
    {
        return mb_strlen($this->data, 'utf8');
    }

    public function normalize(): string
    {
        return $this->data;
    }
}
