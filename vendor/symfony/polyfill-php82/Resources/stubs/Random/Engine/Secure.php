<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Random\Engine;

use Symfony\Polyfill\Php82 as p;

if (\PHP_VERSION_ID < 80200) {
    final class Secure extends p\Random\Engine\Secure implements \Random\CryptoSafeEngine
    {
    }
}
