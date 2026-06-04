<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Attachment Interface
 *
 * This interface is used for defining individual attachments that are attached to a message
 *
 * @since 30.0.0
 *
 */
interface IAttachment {

	/**
	 * sets the attachment file name
	 *
	 * @since 30.0.0
	 *
	 * @param string $value file name (e.g example.txt)
	 *
	 * @return self return this object for command chaining
	 */
	public function setName(string $value): self;

	/**
	 * gets the attachment file name
	 *
	 * @since 30.0.0
	 *
	 * @return string | null returns the attachment file name or null if one is not set
	 */
	public function getName(): ?string;

	/**
	 * sets the attachment mime type
	 *
	 * @since 30.0.0
	 *
	 * @param string $value mime type (e.g. text/plain)
	 *
	 * @return self return this object for command chaining
	 */
	public function setType(string $value): self;

	/**
	 * gets the attachment mime type
	 *
	 * @since 30.0.0
	 *
	 * @return string | null returns the attachment mime type or null if not set
	 */
	public function getType(): ?string;

	/**
	 * sets the attachment contents (actual data)
	 *
	 * @since 30.0.0
	 *
	 * @param string $value binary contents of file
	 *
	 * @return self return this object for command chaining
	 */
	public function setContents(string $value): self;

	/**
	 * gets the attachment contents (actual data)
	 *
	 * @since 30.0.0
	 *
	 * @return string | null returns the attachment contents or null if not set
	 */
	public function getContents(): ?string;

	/**
	 * sets the embedded status of the attachment
	 *
	 * @since 30.0.0
	 *
	 * @param bool $value true - embedded / false - not embedded
	 *
	 * @return self return this object for command chaining
	 */
	public function setEmbedded(bool $value): self;

	/**
	 * gets the embedded status of the attachment
	 *
	 * @since 30.0.0
	 *
	 * @return bool embedded status of the attachment
	 */
	public function getEmbedded(): bool;

}
