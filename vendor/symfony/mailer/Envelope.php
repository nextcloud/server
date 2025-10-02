<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer;

use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\Exception\LogicException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Envelope
{
    private Address $sender;
    private array $recipients = [];

    /**
     * @param Address[] $recipients
     */
    public function __construct(Address $sender, array $recipients)
    {
        $this->setSender($sender);
        $this->setRecipients($recipients);
    }

    public static function create(RawMessage $message): self
    {
        if (RawMessage::class === $message::class) {
            throw new LogicException('Cannot send a RawMessage instance without an explicit Envelope.');
        }

        return new DelayedEnvelope($message);
    }

    public function setSender(Address $sender): void
    {
        // to ensure deliverability of bounce emails independent of UTF-8 capabilities of SMTP servers
        if (!preg_match('/^[^@\x80-\xFF]++@/', $sender->getAddress())) {
            throw new InvalidArgumentException(sprintf('Invalid sender "%s": non-ASCII characters not supported in local-part of email.', $sender->getAddress()));
        }
        $this->sender = $sender;
    }

    /**
     * @return Address Returns a "mailbox" as specified by RFC 2822
     *                 Must be converted to an "addr-spec" when used as a "MAIL FROM" value in SMTP (use getAddress())
     */
    public function getSender(): Address
    {
        return $this->sender;
    }

    /**
     * @param Address[] $recipients
     */
    public function setRecipients(array $recipients): void
    {
        if (!$recipients) {
            throw new InvalidArgumentException('An envelope must have at least one recipient.');
        }

        $this->recipients = [];
        foreach ($recipients as $recipient) {
            if (!$recipient instanceof Address) {
                throw new InvalidArgumentException(sprintf('A recipient must be an instance of "%s" (got "%s").', Address::class, get_debug_type($recipient)));
            }
            $this->recipients[] = new Address($recipient->getAddress());
        }
    }

    /**
     * @return Address[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }
}
