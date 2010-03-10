<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Chuck Hagenbuch <chuck@horde.org>                            |
// +----------------------------------------------------------------------+
//
// $Id: mail.php,v 1.13 2004/09/09 02:08:55 jon Exp $

/**
 * internal PHP-mail() implementation of the PEAR Mail:: interface.
 * @package Mail
 * @version $Revision: 1.13 $
 */
class Mail_mail extends Mail {

    /**
     * Any arguments to pass to the mail() function.
     * @var string
     */
    var $_params = '';

    /**
     * Constructor.
     *
     * Instantiates a new Mail_mail:: object based on the parameters
     * passed in.
     *
     * @param array $params Extra arguments for the mail() function.
     */
    function Mail_mail($params = null)
    {
        /* The other mail implementations accept parameters as arrays.
         * In the interest of being consistent, explode an array into
         * a string of parameter arguments. */
        if (is_array($params)) {
            $this->_params = join(' ', $params);
        } else {
            $this->_params = $params;
        }

        /* Because the mail() function may pass headers as command
         * line arguments, we can't guarantee the use of the standard
         * "\r\n" separator.  Instead, we use the system's native line
         * separator. */
        $this->sep = (strstr(PHP_OS, 'WIN')) ? "\r\n" : "\n";
    }

	/**
     * Implements Mail_mail::send() function using php's built-in mail()
     * command.
     *
     * @param mixed $recipients Either a comma-seperated list of recipients
     *              (RFC822 compliant), or an array of recipients,
     *              each RFC822 valid. This may contain recipients not
     *              specified in the headers, for Bcc:, resending
     *              messages, etc.
     *
     * @param array $headers The array of headers to send with the mail, in an
     *              associative array, where the array key is the
     *              header name (ie, 'Subject'), and the array value
     *              is the header value (ie, 'test'). The header
     *              produced from those values would be 'Subject:
     *              test'.
     *
     * @param string $body The full text of the message body, including any
     *               Mime parts, etc.
     *
     * @return mixed Returns true on success, or a PEAR_Error
     *               containing a descriptive error message on
     *               failure.
     *
     * @access public
     */
    function send($recipients, $headers, $body)
    {
        // If we're passed an array of recipients, implode it.
        if (is_array($recipients)) {
            $recipients = implode(', ', $recipients);
        }

        // Get the Subject out of the headers array so that we can
        // pass it as a seperate argument to mail().
        $subject = '';
        if (isset($headers['Subject'])) {
            $subject = $headers['Subject'];
            unset($headers['Subject']);
        }

        // Flatten the headers out.
        $headerElements = $this->prepareHeaders($headers);
        if (PEAR::isError($headerElements)) {
            return $headerElements;
        }
        list(, $text_headers) = $headerElements;

        /*
         * We only use mail()'s optional fifth parameter if the additional
         * parameters have been provided and we're not running in safe mode.
         */
        if (empty($this->_params) || ini_get('safe_mode')) {
            $result = mail($recipients, $subject, $body, $text_headers);
        } else {
            $result = mail($recipients, $subject, $body, $text_headers,
                           $this->_params);
        }

        /*
         * If the mail() function returned failure, we need to create a
         * PEAR_Error object and return it instead of the boolean result.
         */
        if ($result === false) {
            $result = PEAR::raiseError('mail() returned failure');
        }

        return $result;
    }

}
