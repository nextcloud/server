<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Session\Flash;

/**
 * FlashBag flash message container.
 *
 * @author Drak <drak@zikula.org>
 */
class FlashBag implements FlashBagInterface
{
    private string $name = 'flashes';
    private array $flashes = [];
    private string $storageKey;

    /**
     * @param string $storageKey The key used to store flashes in the session
     */
    public function __construct(string $storageKey = '_symfony_flashes')
    {
        $this->storageKey = $storageKey;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return void
     */
    public function initialize(array &$flashes)
    {
        $this->flashes = &$flashes;
    }

    /**
     * @return void
     */
    public function add(string $type, mixed $message)
    {
        $this->flashes[$type][] = $message;
    }

    public function peek(string $type, array $default = []): array
    {
        return $this->has($type) ? $this->flashes[$type] : $default;
    }

    public function peekAll(): array
    {
        return $this->flashes;
    }

    public function get(string $type, array $default = []): array
    {
        if (!$this->has($type)) {
            return $default;
        }

        $return = $this->flashes[$type];

        unset($this->flashes[$type]);

        return $return;
    }

    public function all(): array
    {
        $return = $this->peekAll();
        $this->flashes = [];

        return $return;
    }

    /**
     * @return void
     */
    public function set(string $type, string|array $messages)
    {
        $this->flashes[$type] = (array) $messages;
    }

    /**
     * @return void
     */
    public function setAll(array $messages)
    {
        $this->flashes = $messages;
    }

    public function has(string $type): bool
    {
        return \array_key_exists($type, $this->flashes) && $this->flashes[$type];
    }

    public function keys(): array
    {
        return array_keys($this->flashes);
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    public function clear(): mixed
    {
        return $this->all();
    }
}
