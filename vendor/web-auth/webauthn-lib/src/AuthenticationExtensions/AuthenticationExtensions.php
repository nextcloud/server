<?php

declare(strict_types=1);

namespace Webauthn\AuthenticationExtensions;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use Webauthn\Exception\AuthenticationExtensionException;
use function array_key_exists;
use function count;
use function is_string;
use const COUNT_NORMAL;

/**
 * @implements IteratorAggregate<AuthenticationExtension>
 * @final
 */
class AuthenticationExtensions implements JsonSerializable, Countable, IteratorAggregate, ArrayAccess
{
    /**
     * @var array<string, AuthenticationExtension>
     * @readonly
     */
    public array $extensions;

    /**
     * @param array<array-key, mixed|AuthenticationExtension> $extensions
     */
    public function __construct(array $extensions = [])
    {
        $list = [];
        foreach ($extensions as $key => $extension) {
            if ($extension instanceof AuthenticationExtension) {
                $list[$extension->name] = $extension;

                continue;
            }
            if (is_string($key)) {
                $list[$key] = AuthenticationExtension::create($key, $extension);
                continue;
            }
            throw new AuthenticationExtensionException('Invalid extension');
        }
        $this->extensions = $list;
    }

    /**
     * @param array<array-key, AuthenticationExtension> $extensions
     */
    public static function create(array $extensions = []): static
    {
        return new static($extensions);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function add(AuthenticationExtension ...$extensions): static
    {
        foreach ($extensions as $extension) {
            $this->extensions[$extension->name] = $extension;
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $json
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $json): static
    {
        return static::create(
            array_map(
                static fn (string $key, mixed $value): AuthenticationExtension => AuthenticationExtension::create(
                    $key,
                    $value
                ),
                array_keys($json),
                $json
            )
        );
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->extensions);
    }

    public function get(string $key): AuthenticationExtension
    {
        $this->has($key) || throw AuthenticationExtensionException::create(sprintf(
            'The extension with key "%s" is not available',
            $key
        ));

        return $this->extensions[$key];
    }

    /**
     * @return array<string, AuthenticationExtension>
     */
    public function jsonSerialize(): array
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
        return $this->extensions;
    }

    /**
     * @return Iterator<string, AuthenticationExtension>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->extensions);
    }

    public function count(int $mode = COUNT_NORMAL): int
    {
        return count($this->extensions, $mode);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->extensions);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->extensions[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value === null) {
            return;
        }
        if ($value instanceof AuthenticationExtension) {
            $this->extensions[$value->name] = $value;
            return;
        }
        if (is_string($offset)) {
            $this->extensions[$offset] = AuthenticationExtension::create($offset, $value);
            return;
        }
        throw new AuthenticationExtensionException('Invalid extension');
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->extensions[$offset]);
    }
}
