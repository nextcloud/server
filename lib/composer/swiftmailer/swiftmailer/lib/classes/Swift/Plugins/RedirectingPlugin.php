<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Redirects all email to a single recipient.
 *
 * @author     Fabien Potencier
 */
class Swift_Plugins_RedirectingPlugin implements Swift_Events_SendListener
{
    /**
     * The recipient who will receive all messages.
     *
     * @var mixed
     */
    private $recipient;

    /**
     * List of regular expression for recipient whitelisting.
     *
     * @var array
     */
    private $whitelist = [];

    /**
     * Create a new RedirectingPlugin.
     *
     * @param mixed $recipient
     */
    public function __construct($recipient, array $whitelist = [])
    {
        $this->recipient = $recipient;
        $this->whitelist = $whitelist;
    }

    /**
     * Set the recipient of all messages.
     *
     * @param mixed $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * Get the recipient of all messages.
     *
     * @return mixed
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Set a list of regular expressions to whitelist certain recipients.
     */
    public function setWhitelist(array $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * Get the whitelist.
     *
     * @return array
     */
    public function getWhitelist()
    {
        return $this->whitelist;
    }

    /**
     * Invoked immediately before the Message is sent.
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();
        $headers = $message->getHeaders();

        // conditionally save current recipients

        if ($headers->has('to')) {
            $headers->addMailboxHeader('X-Swift-To', $message->getTo());
        }

        if ($headers->has('cc')) {
            $headers->addMailboxHeader('X-Swift-Cc', $message->getCc());
        }

        if ($headers->has('bcc')) {
            $headers->addMailboxHeader('X-Swift-Bcc', $message->getBcc());
        }

        // Filter remaining headers against whitelist
        $this->filterHeaderSet($headers, 'To');
        $this->filterHeaderSet($headers, 'Cc');
        $this->filterHeaderSet($headers, 'Bcc');

        // Add each hard coded recipient
        $to = $message->getTo();
        if (null === $to) {
            $to = [];
        }

        foreach ((array) $this->recipient as $recipient) {
            if (!\array_key_exists($recipient, $to)) {
                $message->addTo($recipient);
            }
        }
    }

    /**
     * Filter header set against a whitelist of regular expressions.
     *
     * @param string $type
     */
    private function filterHeaderSet(Swift_Mime_SimpleHeaderSet $headerSet, $type)
    {
        foreach ($headerSet->getAll($type) as $headers) {
            $headers->setNameAddresses($this->filterNameAddresses($headers->getNameAddresses()));
        }
    }

    /**
     * Filtered list of addresses => name pairs.
     *
     * @return array
     */
    private function filterNameAddresses(array $recipients)
    {
        $filtered = [];

        foreach ($recipients as $address => $name) {
            if ($this->isWhitelisted($address)) {
                $filtered[$address] = $name;
            }
        }

        return $filtered;
    }

    /**
     * Matches address against whitelist of regular expressions.
     *
     * @return bool
     */
    protected function isWhitelisted($recipient)
    {
        if (\in_array($recipient, (array) $this->recipient)) {
            return true;
        }

        foreach ($this->whitelist as $pattern) {
            if (preg_match($pattern, $recipient)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Invoked immediately after the Message is sent.
     */
    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
        $this->restoreMessage($evt->getMessage());
    }

    private function restoreMessage(Swift_Mime_SimpleMessage $message)
    {
        // restore original headers
        $headers = $message->getHeaders();

        if ($headers->has('X-Swift-To')) {
            $message->setTo($headers->get('X-Swift-To')->getNameAddresses());
            $headers->removeAll('X-Swift-To');
        } else {
            $message->setTo(null);
        }

        if ($headers->has('X-Swift-Cc')) {
            $message->setCc($headers->get('X-Swift-Cc')->getNameAddresses());
            $headers->removeAll('X-Swift-Cc');
        }

        if ($headers->has('X-Swift-Bcc')) {
            $message->setBcc($headers->get('X-Swift-Bcc')->getNameAddresses());
            $headers->removeAll('X-Swift-Bcc');
        }
    }
}
