<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use Monolog\Logger;

/**
 * Sends notifications through the pushover api to mobile phones
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 * @see    https://www.pushover.net/api
 */
class PushoverHandler extends SocketHandler
{
    private $token;
    private $user;
    private $title;

    /**
     * @param string  $token  Pushover api token
     * @param string  $user   Pushover user id the message will be sent to
     * @param string  $title  Title sent to Pushover API
     * @param integer $level  The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     * @param Boolean $useSSL Whether to connect via SSL. Required when pushing messages to users that are not
     *                        the pushover.net app owner. OpenSSL is required for this option.
     */
    public function __construct($token, $user, $title = null, $level = Logger::CRITICAL, $bubble = true, $useSSL = true)
    {
        $connectionString = $useSSL ? 'ssl://api.pushover.net:443' : 'api.pushover.net:80';
        parent::__construct($connectionString, $level, $bubble);

        $this->token = $token;
        $this->user = $user;
        $this->title = $title ?: gethostname();
    }

    protected function generateDataStream($record)
    {
        $content = $this->buildContent($record);

        return $this->buildHeader($content) . $content;
    }

    private function buildContent($record)
    {
        // Pushover has a limit of 512 characters on title and message combined.
        $maxMessageLength = 512 - strlen($this->title);
        $message = substr($record['message'], 0, $maxMessageLength);
        $timestamp = $record['datetime']->getTimestamp();

        $dataArray = array(
            'token' => $this->token,
            'user' => $this->user,
            'message' => $message,
            'title' => $this->title,
            'timestamp' => $timestamp
        );

        return http_build_query($dataArray);
    }

    private function buildHeader($content)
    {
        $header = "POST /1/messages.json HTTP/1.1\r\n";
        $header .= "Host: api.pushover.net\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($content) . "\r\n";
        $header .= "\r\n";

        return $header;
    }

    public function write(array $record)
    {
        parent::write($record);
        $this->closeSocket();
    }
}
