<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2018 Christian Schmidt
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * An ESMTP handler for 8BITMIME support (RFC 6152).
 *
 * 8BITMIME is required when sending 8-bit content to over SMTP, e.g. when using
 * Swift_Mime_ContentEncoder_PlainContentEncoder in "8bit" mode.
 *
 * 8BITMIME mode is enabled unconditionally, even when sending ASCII-only
 * messages, so it should only be used with an outbound SMTP server that will
 * convert the message to 7-bit MIME if the next hop does not support 8BITMIME.
 *
 * @author Christian Schmidt
 */
class Swift_Transport_Esmtp_EightBitMimeHandler implements Swift_Transport_EsmtpHandler
{
    protected $encoding;

    /**
     * @param string $encoding The parameter so send with the MAIL FROM command;
     *                         either "8BITMIME" or "7BIT"
     */
    public function __construct(string $encoding = '8BITMIME')
    {
        $this->encoding = $encoding;
    }

    /**
     * Get the name of the ESMTP extension this handles.
     *
     * @return string
     */
    public function getHandledKeyword()
    {
        return '8BITMIME';
    }

    /**
     * Not used.
     */
    public function setKeywordParams(array $parameters)
    {
    }

    /**
     * Not used.
     */
    public function afterEhlo(Swift_Transport_SmtpAgent $agent)
    {
    }

    /**
     * Get params which are appended to MAIL FROM:<>.
     *
     * @return string[]
     */
    public function getMailParams()
    {
        return ['BODY='.$this->encoding];
    }

    /**
     * Not used.
     */
    public function getRcptParams()
    {
        return [];
    }

    /**
     * Not used.
     */
    public function onCommand(Swift_Transport_SmtpAgent $agent, $command, $codes = [], &$failedRecipients = null, &$stop = false)
    {
    }

    /**
     * Returns +1, -1 or 0 according to the rules for usort().
     *
     * This method is called to ensure extensions can be execute in an appropriate order.
     *
     * @param string $esmtpKeyword to compare with
     *
     * @return int
     */
    public function getPriorityOver($esmtpKeyword)
    {
        return 0;
    }

    /**
     * Not used.
     */
    public function exposeMixinMethods()
    {
        return [];
    }

    /**
     * Not used.
     */
    public function resetState()
    {
    }
}
