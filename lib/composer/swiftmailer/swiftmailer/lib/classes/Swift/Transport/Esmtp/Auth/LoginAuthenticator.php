<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Handles LOGIN authentication.
 *
 * @author Chris Corbyn
 */
class Swift_Transport_Esmtp_Auth_LoginAuthenticator implements Swift_Transport_Esmtp_Authenticator
{
    /**
     * Get the name of the AUTH mechanism this Authenticator handles.
     *
     * @return string
     */
    public function getAuthKeyword()
    {
        return 'LOGIN';
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Swift_Transport_SmtpAgent $agent, $username, $password)
    {
        try {
            $agent->executeCommand("AUTH LOGIN\r\n", [334]);
            $agent->executeCommand(sprintf("%s\r\n", base64_encode($username)), [334]);
            $agent->executeCommand(sprintf("%s\r\n", base64_encode($password)), [235]);

            return true;
        } catch (Swift_TransportException $e) {
            $agent->executeCommand("RSET\r\n", [250]);

            throw $e;
        }
    }
}
