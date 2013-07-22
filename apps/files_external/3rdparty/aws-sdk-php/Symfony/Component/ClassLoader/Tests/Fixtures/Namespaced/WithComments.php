<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Namespaced;

class WithComments
{
    /** @Boolean */
    public static $loaded = true;
}

$string = 'string shoult not be   modified {$string}';

$heredoc = (<<<HD


Heredoc should not be   modified {$string}


HD
);

$nowdoc = <<<'ND'


Nowdoc should not be   modified {$string}


ND;
