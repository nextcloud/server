<?php

/**
 * iMIP handler.
 *
 * This class is responsible for sending out iMIP messages. iMIP is the
 * email-based transport for iTIP. iTIP deals with scheduling operations for
 * iCalendar objects.
 *
 * If you want to customize the email that gets sent out, you can do so by
 * extending this class and overriding the sendMessage method.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Schedule_IMip {

    /**
     * Email address used in From: header.
     *
     * @var string
     */
    protected $senderEmail;

    /**
     * Creates the email handler.
     *
     * @param string $senderEmail. The 'senderEmail' is the email that shows up
     *                             in the 'From:' address. This should
     *                             generally be some kind of no-reply email
     *                             address you own.
     */
    public function __construct($senderEmail) {

        $this->senderEmail = $senderEmail;

    }

    /**
     * Sends one or more iTip messages through email.
     *
     * @param string $originator
     * @param array $recipients
     * @param Sabre_VObject_Component $vObject
     * @return void
     */
    public function sendMessage($originator, array $recipients, Sabre_VObject_Component $vObject) {

        foreach($recipients as $recipient) {

            $to = $recipient;
            $replyTo = $originator;
            $subject = 'SabreDAV iTIP message';

            switch(strtoupper($vObject->METHOD)) {
                case 'REPLY' :
                    $subject = 'Response for: ' . $vObject->VEVENT->SUMMARY;
                    break;
                case 'REQUEST' :
                    $subject = 'Invitation for: ' .$vObject->VEVENT->SUMMARY;
                    break;
                case 'CANCEL' :
                    $subject = 'Cancelled event: ' . $vObject->VEVENT->SUMMARY;
                    break;
            }

            $headers = array();
            $headers[] = 'Reply-To: ' . $replyTo;
            $headers[] = 'From: ' . $this->senderEmail;
            $headers[] = 'Content-Type: text/calendar; method=' . (string)$vObject->method . '; charset=utf-8';
            if (Sabre_DAV_Server::$exposeVersion) {
                $headers[] = 'X-Sabre-Version: ' . Sabre_DAV_Version::VERSION . '-' . Sabre_DAV_Version::STABILITY;
            }

            $vcalBody = $vObject->serialize();

            $this->mail($to, $subject, $vcalBody, $headers);

        }

    }

    /**
     * This function is reponsible for sending the actual email.
     *
     * @param string $to Recipient email address
     * @param string $subject Subject of the email
     * @param string $body iCalendar body
     * @param array $headers List of headers
     * @return void
     */
    protected function mail($to, $subject, $body, array $headers) {

        mail($to, $subject, $body, implode("\r\n", $headers));

    }


}

?>
