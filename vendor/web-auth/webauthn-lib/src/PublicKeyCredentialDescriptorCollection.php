<?php

declare(strict_types=1);

namespace Webauthn;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use function array_key_exists;
use function count;
use const COUNT_NORMAL;
use const JSON_THROW_ON_ERROR;

/**
 * @implements IteratorAggregate<PublicKeyCredentialDescriptor>
 * @deprecated since 4.8.0 and will be removed in 5.0.0.
 * @infection-ignore-all
 */
class PublicKeyCredentialDescriptorCollection implements JsonSerializable, Countable, IteratorAggregate
{
    /**
     * @var array<string, PublicKeyCredentialDescriptor>
     * @readonly
     */
    public array $publicKeyCredentialDescriptors;

    /**
     * @private
     * @param PublicKeyCredentialDescriptor[] $pkCredentialDescriptors
     */
    public function __construct(
        array $pkCredentialDescriptors = []
    ) {
        $this->publicKeyCredentialDescriptors = [];
        foreach ($pkCredentialDescriptors as $pkCredentialDescriptor) {
            $pkCredentialDescriptor instanceof PublicKeyCredentialDescriptor || throw new InvalidArgumentException(
                'Expected only instances of ' . PublicKeyCredentialDescriptor::class
            );
            $this->publicKeyCredentialDescriptors[$pkCredentialDescriptor->id] = $pkCredentialDescriptor;
        }
    }

    /**
     * @param PublicKeyCredentialDescriptor[] $publicKeyCredentialDescriptors
     */
    public static function create(array $publicKeyCredentialDescriptors): self
    {
        return new self($publicKeyCredentialDescriptors);
    }

    /**
     * @infection-ignore-all
     */
    public function add(PublicKeyCredentialDescriptor ...$publicKeyCredentialDescriptors): void
    {
        foreach ($publicKeyCredentialDescriptors as $publicKeyCredentialDescriptor) {
            $this->publicKeyCredentialDescriptors[$publicKeyCredentialDescriptor->id] = $publicKeyCredentialDescriptor;
        }
    }

    /**
     * @infection-ignore-all
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->publicKeyCredentialDescriptors);
    }

    /**
     * @infection-ignore-all
     */
    public function remove(string $id): void
    {
        if (! array_key_exists($id, $this->publicKeyCredentialDescriptors)) {
            return;
        }

        unset($this->publicKeyCredentialDescriptors[$id]);
    }

    /**
     * @return Iterator<string, PublicKeyCredentialDescriptor>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->publicKeyCredentialDescriptors);
    }

    public function count(int $mode = COUNT_NORMAL): int
    {
        return count($this->publicKeyCredentialDescriptors, $mode);
    }

    /**
     * @return array<string, mixed>[]
     */
    public function jsonSerialize(): array
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
        return $this->publicKeyCredentialDescriptors;
    }

    /**
     * @infection-ignore-all
     */
    public static function createFromString(string $data): self
    {
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        return self::createFromArray($data);
    }

    /**
     * @param mixed[] $json
     * @infection-ignore-all
     */
    public static function createFromArray(array $json): self
    {
        return self::create(
            array_map(
                static fn (array $item): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::createFromArray(
                    $item
                ),
                $json
            )
        );
    }
}
