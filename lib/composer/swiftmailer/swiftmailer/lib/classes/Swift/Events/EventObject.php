<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A base Event which all Event classes inherit from.
 *
 * @author Chris Corbyn
 */
class Swift_Events_EventObject implements Swift_Events_Event
{
    /** The source of this Event */
    private $source;

    /** The state of this Event (should it bubble up the stack?) */
    private $bubbleCancelled = false;

    /**
     * Create a new EventObject originating at $source.
     *
     * @param object $source
     */
    public function __construct($source)
    {
        $this->source = $source;
    }

    /**
     * Get the source object of this event.
     *
     * @return object
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Prevent this Event from bubbling any further up the stack.
     */
    public function cancelBubble($cancel = true)
    {
        $this->bubbleCancelled = $cancel;
    }

    /**
     * Returns true if this Event will not bubble any further up the stack.
     *
     * @return bool
     */
    public function bubbleCancelled()
    {
        return $this->bubbleCancelled;
    }
}
