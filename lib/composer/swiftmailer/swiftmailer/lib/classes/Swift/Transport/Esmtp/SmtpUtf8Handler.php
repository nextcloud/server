<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2018 Christian Schmidt
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * An ESMTP handler for SMTPUTF8 support (RFC 6531).
 *
 * SMTPUTF8 is required when sending to email addresses containing non-ASCII
 * characters in local-part (the substring before @). This handler should be
 * used together with Swift_AddressEncoder_Utf8AddressEncoder.
 *
 * SMTPUTF8 mode is enabled unconditionally, even when sending to ASCII-only
 * addresses, so it should only be used with an outbound SMTP server that will
 * deliver ASCII-only messages even if the next hop does not support SMTPUTF8.
 *
 * @author Christian Schmidt
 */
class Swift_Transport_Esmtp_SmtpUtf8Handler implements Swift_Transport_EsmtpHandler
{
    public function __construct()
    {
    }

    /**
     * Get the name of the ESMTP extension this handles.
     *
     * @return string
     */
    public function getHandledKeyword()
    {
        return 'SMTPUTF8';
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
        return ['SMTPUTF8'];
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
