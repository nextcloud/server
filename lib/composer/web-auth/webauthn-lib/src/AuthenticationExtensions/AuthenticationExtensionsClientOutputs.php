<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\AuthenticationExtensions;

use function array_key_exists;
use ArrayIterator;
use Assert\Assertion;
use function count;
use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use function Safe\json_decode;
use function Safe\sprintf;

class AuthenticationExtensionsClientOutputs implements JsonSerializable, Countable, IteratorAggregate
{
    /**
     * @var AuthenticationExtension[]
     */
    private $extensions = [];

    public function add(AuthenticationExtension $extension): void
    {
        $this->extensions[$extension->name()] = $extension;
    }

    public static function createFromString(string $data): self
    {
        $data = json_decode($data, true);
        Assertion::isArray($data, 'Invalid data');

        return self::createFromArray($data);
    }

    /**
     * @param mixed[] $json
     */
    public static function createFromArray(array $json): self
    {
        $object = new self();
        foreach ($json as $k => $v) {
            $object->add(new AuthenticationExtension($k, $v));
        }

        return $object;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->extensions);
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        Assertion::true($this->has($key), sprintf('The extension with key "%s" is not available', $key));

        return $this->extensions[$key];
    }

    /**
     * @return AuthenticationExtension[]
     */
    public function jsonSerialize(): array
    {
        return array_map(static function (AuthenticationExtension $object) {
            return $object->jsonSerialize();
        }, $this->extensions);
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
}
