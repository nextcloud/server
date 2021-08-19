<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\EventDispatcher;

/**
 * @deprecated since Symfony 4.3, use "Symfony\Contracts\EventDispatcher\Event" instead
 */
class Event
{
    private $propagationStopped = false;

    /**
     * @return bool Whether propagation was already stopped for this event
     *
     * @deprecated since Symfony 4.3, use "Symfony\Contracts\EventDispatcher\Event" instead
     */
    public function isPropagationStopped()
    {
        return $this->propagationStopped;
    }

    /**
     * @deprecated since Symfony 4.3, use "Symfony\Contracts\EventDispatcher\Event" instead
     */
    public function stopPropagation()
    {
        $this->propagationStopped = true;
    }
}
