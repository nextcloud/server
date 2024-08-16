<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail;

/**
 * Interface IAttachment
 *
 * @since 13.0.0
 */
interface IAttachment {
	/**
	 * @param string $filename
	 * @return IAttachment
	 * @since 13.0.0
	 */
	public function setFilename(string $filename): IAttachment;

	/**
	 * @param string $contentType
	 * @return IAttachment
	 * @since 13.0.0
	 */
	public function setContentType(string $contentType): IAttachment;

	/**
	 * @param string $body
	 * @return IAttachment
	 * @since 13.0.0
	 */
	public function setBody(string $body): IAttachment;
}
