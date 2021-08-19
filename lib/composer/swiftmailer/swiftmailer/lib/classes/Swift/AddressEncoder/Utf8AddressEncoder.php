<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2018 Christian Schmidt
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A UTF-8 email address encoder.
 *
 * Returns the email address verbatimly in UTF-8 as permitted by RFC 6531 and
 * RFC 6532. It supports addresses containing non-ASCII characters in both
 * local-part and domain (i.e. on both sides of @).
 *
 * This encoder must be used together with Swift_Transport_Esmtp_SmtpUtf8Handler
 * and requires that the outbound SMTP server supports the SMTPUTF8 extension.
 *
 * If your outbound SMTP server does not support SMTPUTF8, use
 * Swift_AddressEncoder_IdnAddressEncoder instead. This allows sending to email
 * addresses with non-ASCII characters in the domain, but not in local-part.
 *
 * @author Christian Schmidt
 */
class Swift_AddressEncoder_Utf8AddressEncoder implements Swift_AddressEncoder
{
    /**
     * Returns the address verbatimly.
     */
    public function encodeString(string $address): string
    {
        return $address;
    }
}
