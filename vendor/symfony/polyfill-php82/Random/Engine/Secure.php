<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php82\Random\Engine;

use Random\RandomException;
use Symfony\Polyfill\Php82\NoDynamicProperties;

/**
 * @author Tim Düsterhus <tim@bastelstu.be>
 * @author Anton Smirnov <sandfox@sandfox.me>
 *
 * @internal
 */
class Secure
{
    use NoDynamicProperties;

    public function generate(): string
    {
        try {
            return random_bytes(\PHP_INT_SIZE);
        } catch (\Exception $e) {
            throw new RandomException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    public function __sleep(): array
    {
        throw new \Exception("Serialization of 'Random\Engine\Secure' is not allowed");
    }

    public function __wakeup(): void
    {
        throw new \Exception("Unserialization of 'Random\Engine\Secure' is not allowed");
    }

    public function __clone()
    {
        throw new \Error('Trying to clone an uncloneable object of class Random\Engine\Secure');
    }
}
