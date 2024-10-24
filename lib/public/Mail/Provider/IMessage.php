<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Message Interface
 *
 * This interface is a base requirement of methods and functionality used to construct a mail message object
 *
 * @since 30.0.0
 *
 */
interface IMessage {
	
	/**
	 * arbitrary unique text string identifying this message
	 *
	 * @since 30.0.0
	 *
	 * @return string id of this message
	 */
	public function id(): string;

	/**
	 * sets the sender of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress $value sender's mail address object
	 *
	 * @return self return this object for command chaining
	 */
	public function setFrom(IAddress $value): self;

	/**
	 * gets the sender of this message
	 *
	 * @since 30.0.0
	 *
	 * @return IAddress|null sender's mail address object
	 */
	public function getFrom(): ?IAddress;

	/**
	 * sets the sender's reply to address of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress $value senders's reply to mail address object
	 *
	 * @return self return this object for command chaining
	 */
	public function setReplyTo(IAddress $value): self;

	/**
	 * gets the sender's reply to address of this message
	 *
	 * @since 30.0.0
	 *
	 * @return IAddress|null sender's reply to mail address object
	 */
	public function getReplyTo(): ?IAddress;

	/**
	 * sets the recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value collection of or one or more mail address objects
	 *
	 * @return self return this object for command chaining
	 */
	public function setTo(IAddress ...$value): self;

	/**
	 * gets the recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @return array<int,IAddress> collection of all recipient mail address objects
	 */
	public function getTo(): array;

	/**
	 * sets the copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value collection of or one or more mail address objects
	 *
	 * @return self return this object for command chaining
	 */
	public function setCc(IAddress ...$value): self;

	/**
	 * gets the copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @return array<int,IAddress> collection of all copied recipient mail address objects
	 */
	public function getCc(): array;

	/**
	 * sets the blind copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value collection of or one or more mail address objects
	 *
	 * @return self return this object for command chaining
	 */
	public function setBcc(IAddress ...$value): self;
	
	/**
	 * gets the blind copy to recipient(s) of this message
	 *
	 * @since 30.0.0
	 *
	 * @return array<int,IAddress> collection of all blind copied recipient mail address objects
	 */
	public function getBcc(): array;

	/**
	 * sets the subject of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value subject of mail message
	 *
	 * @return self return this object for command chaining
	 */
	public function setSubject(string $value): self;

	/**
	 * gets the subject of this message
	 *
	 * @since 30.0.0
	 *
	 * @return string|null subject of message or null if one is not set
	 */
	public function getSubject(): ?string;

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
	public function setBody(string $value, bool $html): self;

	/**
	 * gets either the html or plain text body of this message
	 *
	 * html body will be returned over plain text if html body exists
	 *
	 * @since 30.0.0
	 *
	 * @return string|null html/plain body of this message or null if one is not set
	 */
	public function getBody(): ?string;

	/**
	 * sets the html body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value html body of message
	 *
	 * @return self return this object for command chaining
	 */
	public function setBodyHtml(string $value): self;

	/**
	 * gets the html body of this message
	 *
	 * @since 30.0.0
	 *
	 * @return string|null html body of this message or null if one is not set
	 */
	public function getBodyHtml(): ?string;

	/**
	 * sets the plain text body of this message
	 *
	 * @since 30.0.0
	 *
	 * @param string $value plain text body of message
	 *
	 * @return self return this object for command chaining
	 */
	public function setBodyPlain(string $value): self;

	/**
	 * gets the plain text body of this message
	 *
	 * @since 30.0.0
	 *
	 * @return string|null plain text body of this message or null if one is not set
	 */
	public function getBodyPlain(): ?string;

	/**
	 * sets the attachments of this message
	 *
	 * @since 30.0.0
	 *
	 * @param IAttachment ...$value collection of or one or more mail attachment objects
	 *
	 * @return self return this object for command chaining
	 */
	public function setAttachments(IAttachment ...$value): self;

	/**
	 * gets the attachments of this message
	 *
	 * @since 30.0.0
	 *
	 * @return array<int,IAttachment> collection of all mail attachment objects
	 */
	public function getAttachments(): array;
}
