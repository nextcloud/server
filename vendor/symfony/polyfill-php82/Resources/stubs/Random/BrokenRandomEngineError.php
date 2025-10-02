<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Random;

if (\PHP_VERSION_ID < 80200) {
    class BrokenRandomEngineError extends RandomError
    {
    }
}
