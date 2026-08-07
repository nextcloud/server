<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareByMail\Event;

use OCP\Mail\IMessage;
use OCP\Share\IShare;

/**
 * Fired by ShareByMailProvider::sendNote() immediately before the native
 * mailer->send() call for note-update notifications to recipients.
 *
 * @psalm-type TemplateData = array{
 *   filename: string,
 *   note: string,
 * }
 *
 * @psalm-api
 */
class BeforeShareNoteMailSentEvent extends AbstractBeforeShareMailSentEvent {
	/**
	 * @param string[] $resolvedEmails
	 * @param TemplateData $templateData
	 */
	public function __construct(
		IShare $share,
		array $resolvedEmails,
		IMessage $message,
		private readonly array $templateData,
	) {
		parent::__construct($share, $resolvedEmails, $message);
	}

	public function getFileName(): string {
		return $this->templateData['filename'];
	}

	public function getNote(): string {
		return $this->templateData['note'];
	}
}
