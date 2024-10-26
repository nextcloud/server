<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Message Object
 *
 * This object is used to define a mail message that can be used to transfer data to a provider
 *
 * @since 30.0.0
 *
 */
class Message implements \OCP\Mail\Provider\IMessage {

	/**
	 * initialize the mail message object
	 *
	 * @since 30.0.0
	 *
	 * @param array $data message data array
	 */
	public function __construct(
		protected array $data = [],
	) {
	}

	/**
	 * arbitrary unique text string identifying this message
	 *
	 * @since 30.0.0
	 *
	 * @return string id of this message
	 */
	public function id(): string {
		// return id of message
		return (isset($this->data['id'])) ? $this->data['id'] : '';
	}

	/**
	 * sets the sender of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress $value sender's mail address object
	 *
	 * @return self return this object for command chaining
	 */
	public function setFrom(IAddress $value): self {
		// create or update field in data store with value
		$this->data['from'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the sender of this message
	 *
	 * @since 30.0.0
	 *
	 * @return IAddress|null sender's mail address object
	 */
	public function getFrom(): ?IAddress {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['from'])) ? $this->data['from'] : null;
	}

	/**
	 * sets the sender's reply to address of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress $value senders's reply to mail address object
	 *
	 * @return self return this object for command chaining
	 */
	public function setReplyTo(IAddress $value): self {
		// create or update field in data store with value
		$this->data['replyTo'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the sender's reply to address of this message
	 *
	 * @since 30.0.0
	 *
	 * @return IAddress|null sender's reply to mail address object
	 */
	public function getReplyTo(): ?IAddress {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['replyTo'])) ? $this->data['replyTo'] : null;
	}

	/**
	 * sets the recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value collection of or one or more mail address objects
	 *
	 * @return self return this object for command chaining
	 */
	public function setTo(IAddress ...$value): self {
		// create or update field in data store with value
		$this->data['to'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @return array<int,IAddress> collection of all recipient mail address objects
	 */
	public function getTo(): array {
		// evaluate if data store field exists and return value(s) or empty collection
		return (isset($this->data['to'])) ? $this->data['to'] : [];
	}

	/**
	 * sets the copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value collection of or one or more mail address objects
	 *
	 * @return self return this object for command chaining
	 */
	public function setCc(IAddress ...$value): self {
		// create or update field in data store with value
		$this->data['cc'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @return array<int,IAddress> collection of all copied recipient mail address objects
	 */
	public function getCc(): array {
		// evaluate if data store field exists and return value(s) or empty collection
		return (isset($this->data['cc'])) ? $this->data['cc'] : [];
	}

	/**
	 * sets the blind copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value collection of or one or more mail address objects
	 *
	 * @return self return this object for command chaining
	 */
	public function setBcc(IAddress ...$value): self {
		// create or update field in data store with value
		$this->data['bcc'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the blind copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @return array<int,IAddress> collection of all blind copied recipient mail address objects
	 */
	public function getBcc(): array {
		// evaluate if data store field exists and return value(s) or empty collection
		return (isset($this->data['bcc'])) ? $this->data['bcc'] : [];
	}

	/**
	 * sets the subject of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value subject of mail message
	 *
	 * @return self return this object for command chaining
	 */
	public function setSubject(string $value): self {
		// create or update field in data store with value
		$this->data['subject'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the subject of this message
	 *
	 * @since 30.0.0
	 *
	 * @return string|null subject of message or null if one is not set
	 */
	public function getSubject(): ?string {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['subject'])) ? $this->data['subject'] : null;
	}

	/**
	 * sets the plain text or html body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value text or html body of message
	 * @param bool $html html flag - true for html
	 *
	 * @return self return this object for command chaining
	 */
	public function setBody(string $value, bool $html = false): self {
		// evaluate html flag and create or update appropriate field in data store with value
		if ($html) {
			$this->data['bodyHtml'] = $value;
		} else {
			$this->data['bodyPlain'] = $value;
		}
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets either the html or plain text body of this message
	 *
	 * html body will be returned over plain text if html body exists
	 *
	 * @since 30.0.0
	 *
	 * @return string|null html/plain body of this message or null if one is not set
	 */
	public function getBody(): ?string {
		// evaluate if data store field(s) exists and return value
		if (isset($this->data['bodyHtml'])) {
			return $this->data['bodyHtml'];
		} elseif (isset($this->data['bodyPlain'])) {
			return $this->data['bodyPlain'];
		}
		// return null if data fields did not exist in data store
		return null;
	}

	/**
	 * sets the html body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value html body of message
	 *
	 * @return self return this object for command chaining
	 */
	public function setBodyHtml(string $value): self {
		// create or update field in data store with value
		$this->data['bodyHtml'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the html body of this message
	 *
	 * @since 30.0.0
	 *
	 * @return string|null html body of this message or null if one is not set
	 */
	public function getBodyHtml(): ?string {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['bodyHtml'])) ? $this->data['bodyHtml'] : null;
	}

	/**
	 * sets the plain text body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value plain text body of message
	 *
	 * @return self return this object for command chaining
	 */
	public function setBodyPlain(string $value): self {
		// create or update field in data store with value
		$this->data['bodyPlain'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the plain text body of this message
	 *
	 * @since 30.0.0
	 *
	 * @return string|null plain text body of this message or null if one is not set
	 */
	public function getBodyPlain(): ?string {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['bodyPlain'])) ? $this->data['bodyPlain'] : null;
	}

	/**
	 * sets the attachments of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAttachment ...$value collection of or one or more mail attachment objects
	 *
	 * @return self return this object for command chaining
	 */
	public function setAttachments(IAttachment ...$value): self {
		// create or update field in data store with value
		$this->data['attachments'] = $value;
		// return this object for command chaining
		return $this;
	}

	/**
	 * gets the attachments of this message
	 *
	 * @since 30.0.0
	 *
	 * @return array<int,IAttachment> collection of all mail attachment objects
	 */
	public function getAttachments(): array {
		// evaluate if data store field exists and return value(s) or null otherwise
		return (isset($this->data['attachments'])) ? $this->data['attachments'] : [];
	}

}
