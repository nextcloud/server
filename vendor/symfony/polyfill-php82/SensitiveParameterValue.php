<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php82;

/**
 * @author Tim Düsterhus <duesterhus@woltlab.com>
 *
 * @internal
 */
class SensitiveParameterValue
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __debugInfo(): array
    {
        return [];
    }

    public function __sleep(): array
    {
        throw new \Exception("Serialization of 'SensitiveParameterValue' is not allowed");
    }

    public function __wakeup(): void
    {
        throw new \Exception("Unserialization of 'SensitiveParameterValue' is not allowed");
    }
}
