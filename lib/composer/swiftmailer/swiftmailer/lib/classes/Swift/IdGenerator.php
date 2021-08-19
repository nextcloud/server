<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Message ID generator.
 */
interface Swift_IdGenerator
{
    /**
     * Returns a globally unique string to use for Message-ID or Content-ID.
     *
     * @return string
     */
    public function generateId();
}
