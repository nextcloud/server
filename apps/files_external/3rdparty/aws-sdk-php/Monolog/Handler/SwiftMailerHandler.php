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
 * SwiftMailerHandler uses Swift_Mailer to send the emails
 *
 * @author Gyula Sallai
 */
class SwiftMailerHandler extends MailHandler
{
    protected $mailer;
    protected $message;

    /**
     * @param \Swift_Mailer           $mailer  The mailer to use
     * @param callable|\Swift_Message $message An example message for real messages, only the body will be replaced
     * @param integer                 $level   The minimum logging level at which this handler will be triggered
     * @param Boolean                 $bubble  Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(\Swift_Mailer $mailer, $message, $level = Logger::ERROR, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->mailer  = $mailer;
        if (!$message instanceof \Swift_Message && is_callable($message)) {
            $message = call_user_func($message);
        }
        if (!$message instanceof \Swift_Message) {
            throw new \InvalidArgumentException('You must provide either a Swift_Message instance or a callable returning it');
        }
        $this->message = $message;
    }

    /**
     * {@inheritdoc}
     */
    protected function send($content, array $records)
    {
        $message = clone $this->message;
        $message->setBody($content);

        $this->mailer->send($message);
    }
}
