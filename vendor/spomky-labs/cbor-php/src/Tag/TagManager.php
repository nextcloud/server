<?php

declare(strict_types=1);

namespace CBOR\Tag;

use CBOR\CBORObject;
use CBOR\Tag;
use CBOR\Utils;
use InvalidArgumentException;
use function array_key_exists;

final class TagManager implements TagManagerInterface
{
    /**
     * @var string[]
     */
    private array $classes = [];

    public static function create(): self
    {
        return new self();
    }

    public function add(string $class): self
    {
        if ($class::getTagId() < 0) {
            throw new InvalidArgumentException('Invalid tag ID.');
        }
        $this->classes[$class::getTagId()] = $class;

        return $this;
    }

    public function getClassForValue(int $value): string
    {
        return array_key_exists($value, $this->classes) ? $this->classes[$value] : GenericTag::class;
    }

    public function createObjectForValue(int $additionalInformation, ?string $data, CBORObject $object): TagInterface
    {
        $value = $additionalInformation;
        if ($additionalInformation >= 24) {
            Utils::assertString($data, 'Invalid data');
            $value = Utils::binToInt($data);
        }
        /** @var Tag $class */
        $class = $this->getClassForValue($value);

        return $class::createFromLoadedData($additionalInformation, $data, $object);
    }
}
