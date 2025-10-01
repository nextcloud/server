<?php

declare(strict_types=1);

namespace CBOR;

/**
 * @see \CBOR\Test\IndefiniteLengthTextStringObjectTest
 */
final class IndefiniteLengthTextStringObject extends AbstractCBORObject implements Normalizable
{
    private const MAJOR_TYPE = self::MAJOR_TYPE_TEXT_STRING;

    private const ADDITIONAL_INFORMATION = self::LENGTH_INDEFINITE;

    /**
     * @var TextStringObject[]
     */
    private array $data = [];

    public function __construct()
    {
        parent::__construct(self::MAJOR_TYPE, self::ADDITIONAL_INFORMATION);
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        foreach ($this->data as $object) {
            $result .= (string) $object;
        }

        return $result . "\xFF";
    }

    public static function create(): self
    {
        return new self();
    }

    public function add(TextStringObject $chunk): self
    {
        $this->data[] = $chunk;

        return $this;
    }

    public function append(string $chunk): self
    {
        $this->add(TextStringObject::create($chunk));

        return $this;
    }

    public function getValue(): string
    {
        $result = '';
        foreach ($this->data as $object) {
            $result .= $object->getValue();
        }

        return $result;
    }

    public function getLength(): int
    {
        $length = 0;
        foreach ($this->data as $object) {
            $length += $object->getLength();
        }

        return $length;
    }

    public function normalize(): string
    {
        $result = '';
        foreach ($this->data as $object) {
            $result .= $object->normalize();
        }

        return $result;
    }
}
