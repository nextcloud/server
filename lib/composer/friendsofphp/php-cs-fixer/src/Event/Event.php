<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// Since PHP-CS-FIXER is PHP 5.6 compliant we can't always use Symfony Contracts (currently needs PHP ^7.1.3)
// This conditional inheritance will be useless when PHP-CS-FIXER no longer supports PHP versions
// inferior to Symfony/Contracts PHP minimal version
if (is_subclass_of(EventDispatcher::class, EventDispatcherInterface::class)) {
    class Event extends \Symfony\Contracts\EventDispatcher\Event
    {
    }
} else {
    class Event extends \Symfony\Component\EventDispatcher\Event
    {
    }
}
