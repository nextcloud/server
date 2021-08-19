<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The default email message class.
 *
 * @author Chris Corbyn
 */
class Swift_Mime_SimpleMessage extends Swift_Mime_MimePart
{
    const PRIORITY_HIGHEST = 1;
    const PRIORITY_HIGH = 2;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_LOW = 4;
    const PRIORITY_LOWEST = 5;

    /**
     * Create a new SimpleMessage with $headers, $encoder and $cache.
     *
     * @param string $charset
     */
    public function __construct(Swift_Mime_SimpleHeaderSet $headers, Swift_Mime_ContentEncoder $encoder, Swift_KeyCache $cache, Swift_IdGenerator $idGenerator, $charset = null)
    {
        parent::__construct($headers, $encoder, $cache, $idGenerator, $charset);
        $this->getHeaders()->defineOrdering([
            'Return-Path',
            'Received',
            'DKIM-Signature',
            'DomainKey-Signature',
            'Sender',
            'Message-ID',
            'Date',
            'Subject',
            'From',
            'Reply-To',
            'To',
            'Cc',
            'Bcc',
            'MIME-Version',
            'Content-Type',
            'Content-Transfer-Encoding',
            ]);
        $this->getHeaders()->setAlwaysDisplayed(['Date', 'Message-ID', 'From']);
        $this->getHeaders()->addTextHeader('MIME-Version', '1.0');
        $this->setDate(new DateTimeImmutable());
        $this->setId($this->getId());
        $this->getHeaders()->addMailboxHeader('From');
    }

    /**
     * Always returns {@link LEVEL_TOP} for a message instance.
     *
     * @return int
     */
    public function getNestingLevel()
    {
        return self::LEVEL_TOP;
    }

    /**
     * Set the subject of this message.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        if (!$this->setHeaderFieldModel('Subject', $subject)) {
            $this->getHeaders()->addTextHeader('Subject', $subject);
        }

        return $this;
    }

    /**
     * Get the subject of this message.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->getHeaderFieldModel('Subject');
    }

    /**
     * Set the date at which this message was created.
     *
     * @return $this
     */
    public function setDate(DateTimeInterface $dateTime)
    {
        if (!$this->setHeaderFieldModel('Date', $dateTime)) {
            $this->getHeaders()->addDateHeader('Date', $dateTime);
        }

        return $this;
    }

    /**
     * Get the date at which this message was created.
     *
     * @return DateTimeInterface
     */
    public function getDate()
    {
        return $this->getHeaderFieldModel('Date');
    }

    /**
     * Set the return-path (the bounce address) of this message.
     *
     * @param string $address
     *
     * @return $this
     */
    public function setReturnPath($address)
    {
        if (!$this->setHeaderFieldModel('Return-Path', $address)) {
            $this->getHeaders()->addPathHeader('Return-Path', $address);
        }

        return $this;
    }

    /**
     * Get the return-path (bounce address) of this message.
     *
     * @return string
     */
    public function getReturnPath()
    {
        return $this->getHeaderFieldModel('Return-Path');
    }

    /**
     * Set the sender of this message.
     *
     * This does not override the From field, but it has a higher significance.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function setSender($address, $name = null)
    {
        if (!\is_array($address) && isset($name)) {
            $address = [$address => $name];
        }

        if (!$this->setHeaderFieldModel('Sender', (array) $address)) {
            $this->getHeaders()->addMailboxHeader('Sender', (array) $address);
        }

        return $this;
    }

    /**
     * Get the sender of this message.
     *
     * @return string
     */
    public function getSender()
    {
        return $this->getHeaderFieldModel('Sender');
    }

    /**
     * Add a From: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function addFrom($address, $name = null)
    {
        $current = $this->getFrom();
        $current[$address] = $name;

        return $this->setFrom($current);
    }

    /**
     * Set the from address of this message.
     *
     * You may pass an array of addresses if this message is from multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string       $name      optional
     *
     * @return $this
     */
    public function setFrom($addresses, $name = null)
    {
        if (!\is_array($addresses) && isset($name)) {
            $addresses = [$addresses => $name];
        }

        if (!$this->setHeaderFieldModel('From', (array) $addresses)) {
            $this->getHeaders()->addMailboxHeader('From', (array) $addresses);
        }

        return $this;
    }

    /**
     * Get the from address of this message.
     *
     * @return mixed
     */
    public function getFrom()
    {
        return $this->getHeaderFieldModel('From');
    }

    /**
     * Add a Reply-To: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function addReplyTo($address, $name = null)
    {
        $current = $this->getReplyTo();
        $current[$address] = $name;

        return $this->setReplyTo($current);
    }

    /**
     * Set the reply-to address of this message.
     *
     * You may pass an array of addresses if replies will go to multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed  $addresses
     * @param string $name      optional
     *
     * @return $this
     */
    public function setReplyTo($addresses, $name = null)
    {
        if (!\is_array($addresses) && isset($name)) {
            $addresses = [$addresses => $name];
        }

        if (!$this->setHeaderFieldModel('Reply-To', (array) $addresses)) {
            $this->getHeaders()->addMailboxHeader('Reply-To', (array) $addresses);
        }

        return $this;
    }

    /**
     * Get the reply-to address of this message.
     *
     * @return string
     */
    public function getReplyTo()
    {
        return $this->getHeaderFieldModel('Reply-To');
    }

    /**
     * Add a To: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function addTo($address, $name = null)
    {
        $current = $this->getTo();
        $current[$address] = $name;

        return $this->setTo($current);
    }

    /**
     * Set the to addresses of this message.
     *
     * If multiple recipients will receive the message an array should be used.
     * Example: array('receiver@domain.org', 'other@domain.org' => 'A name')
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed  $addresses
     * @param string $name      optional
     *
     * @return $this
     */
    public function setTo($addresses, $name = null)
    {
        if (!\is_array($addresses) && isset($name)) {
            $addresses = [$addresses => $name];
        }

        if (!$this->setHeaderFieldModel('To', (array) $addresses)) {
            $this->getHeaders()->addMailboxHeader('To', (array) $addresses);
        }

        return $this;
    }

    /**
     * Get the To addresses of this message.
     *
     * @return array
     */
    public function getTo()
    {
        return $this->getHeaderFieldModel('To');
    }

    /**
     * Add a Cc: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function addCc($address, $name = null)
    {
        $current = $this->getCc();
        $current[$address] = $name;

        return $this->setCc($current);
    }

    /**
     * Set the Cc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed  $addresses
     * @param string $name      optional
     *
     * @return $this
     */
    public function setCc($addresses, $name = null)
    {
        if (!\is_array($addresses) && isset($name)) {
            $addresses = [$addresses => $name];
        }

        if (!$this->setHeaderFieldModel('Cc', (array) $addresses)) {
            $this->getHeaders()->addMailboxHeader('Cc', (array) $addresses);
        }

        return $this;
    }

    /**
     * Get the Cc address of this message.
     *
     * @return array
     */
    public function getCc()
    {
        return $this->getHeaderFieldModel('Cc');
    }

    /**
     * Add a Bcc: address to this message.
     *
     * If $name is passed this name will be associated with the address.
     *
     * @param string $address
     * @param string $name    optional
     *
     * @return $this
     */
    public function addBcc($address, $name = null)
    {
        $current = $this->getBcc();
        $current[$address] = $name;

        return $this->setBcc($current);
    }

    /**
     * Set the Bcc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param mixed  $addresses
     * @param string $name      optional
     *
     * @return $this
     */
    public function setBcc($addresses, $name = null)
    {
        if (!\is_array($addresses) && isset($name)) {
            $addresses = [$addresses => $name];
        }

        if (!$this->setHeaderFieldModel('Bcc', (array) $addresses)) {
            $this->getHeaders()->addMailboxHeader('Bcc', (array) $addresses);
        }

        return $this;
    }

    /**
     * Get the Bcc addresses of this message.
     *
     * @return array
     */
    public function getBcc()
    {
        return $this->getHeaderFieldModel('Bcc');
    }

    /**
     * Set the priority of this message.
     *
     * The value is an integer where 1 is the highest priority and 5 is the lowest.
     *
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        $priorityMap = [
            self::PRIORITY_HIGHEST => 'Highest',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_LOWEST => 'Lowest',
            ];
        $pMapKeys = array_keys($priorityMap);
        if ($priority > max($pMapKeys)) {
            $priority = max($pMapKeys);
        } elseif ($priority < min($pMapKeys)) {
            $priority = min($pMapKeys);
        }
        if (!$this->setHeaderFieldModel('X-Priority',
            sprintf('%d (%s)', $priority, $priorityMap[$priority]))) {
            $this->getHeaders()->addTextHeader('X-Priority',
                sprintf('%d (%s)', $priority, $priorityMap[$priority]));
        }

        return $this;
    }

    /**
     * Get the priority of this message.
     *
     * The returned value is an integer where 1 is the highest priority and 5
     * is the lowest.
     *
     * @return int
     */
    public function getPriority()
    {
        list($priority) = sscanf($this->getHeaderFieldModel('X-Priority'),
            '%[1-5]'
            );

        return $priority ?? 3;
    }

    /**
     * Ask for a delivery receipt from the recipient to be sent to $addresses.
     *
     * @param array $addresses
     *
     * @return $this
     */
    public function setReadReceiptTo($addresses)
    {
        if (!$this->setHeaderFieldModel('Disposition-Notification-To', $addresses)) {
            $this->getHeaders()
                ->addMailboxHeader('Disposition-Notification-To', $addresses);
        }

        return $this;
    }

    /**
     * Get the addresses to which a read-receipt will be sent.
     *
     * @return string
     */
    public function getReadReceiptTo()
    {
        return $this->getHeaderFieldModel('Disposition-Notification-To');
    }

    /**
     * Attach a {@link Swift_Mime_SimpleMimeEntity} such as an Attachment or MimePart.
     *
     * @return $this
     */
    public function attach(Swift_Mime_SimpleMimeEntity $entity)
    {
        $this->setChildren(array_merge($this->getChildren(), [$entity]));

        return $this;
    }

    /**
     * Remove an already attached entity.
     *
     * @return $this
     */
    public function detach(Swift_Mime_SimpleMimeEntity $entity)
    {
        $newChildren = [];
        foreach ($this->getChildren() as $child) {
            if ($entity !== $child) {
                $newChildren[] = $child;
            }
        }
        $this->setChildren($newChildren);

        return $this;
    }

    /**
     * Attach a {@link Swift_Mime_SimpleMimeEntity} and return it's CID source.
     *
     * This method should be used when embedding images or other data in a message.
     *
     * @return string
     */
    public function embed(Swift_Mime_SimpleMimeEntity $entity)
    {
        $this->attach($entity);

        return 'cid:'.$entity->getId();
    }

    /**
     * Get this message as a complete string.
     *
     * @return string
     */
    public function toString()
    {
        if (\count($children = $this->getChildren()) > 0 && '' != $this->getBody()) {
            $this->setChildren(array_merge([$this->becomeMimePart()], $children));
            $string = parent::toString();
            $this->setChildren($children);
        } else {
            $string = parent::toString();
        }

        return $string;
    }

    /**
     * Returns a string representation of this object.
     *
     * @see toString()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Write this message to a {@link Swift_InputByteStream}.
     */
    public function toByteStream(Swift_InputByteStream $is)
    {
        if (\count($children = $this->getChildren()) > 0 && '' != $this->getBody()) {
            $this->setChildren(array_merge([$this->becomeMimePart()], $children));
            parent::toByteStream($is);
            $this->setChildren($children);
        } else {
            parent::toByteStream($is);
        }
    }

    /** @see Swift_Mime_SimpleMimeEntity::getIdField() */
    protected function getIdField()
    {
        return 'Message-ID';
    }

    /** Turn the body of this message into a child of itself if needed */
    protected function becomeMimePart()
    {
        $part = new parent($this->getHeaders()->newInstance(), $this->getEncoder(),
            $this->getCache(), $this->getIdGenerator(), $this->userCharset
            );
        $part->setContentType($this->userContentType);
        $part->setBody($this->getBody());
        $part->setFormat($this->userFormat);
        $part->setDelSp($this->userDelSp);
        $part->setNestingLevel($this->getTopNestingLevel());

        return $part;
    }

    /** Get the highest nesting level nested inside this message */
    private function getTopNestingLevel()
    {
        $highestLevel = $this->getNestingLevel();
        foreach ($this->getChildren() as $child) {
            $childLevel = $child->getNestingLevel();
            if ($highestLevel < $childLevel) {
                $highestLevel = $childLevel;
            }
        }

        return $highestLevel;
    }
}
