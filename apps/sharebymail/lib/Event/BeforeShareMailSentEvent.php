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
 * Fired by ShareByMailProvider::sendEmail() immediately before the native
 * mailer->send() call for share-link notifications to recipients.
 *
 * @psalm-type TemplateData = array{
 *   senderUserId: string,
 *   filename: string,
 *   link: string,
 *   initiator: string,
 *   expiration: \DateTime|null,
 *   shareWith: string,
 *   note: string,
 * }
 *
 * @psalm-api
 */
class BeforeShareMailSentEvent extends AbstractBeforeShareMailSentEvent {
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

	public function getSenderUserId(): string {
		return $this->templateData['senderUserId'];
	}

	public function getFileName(): string {
		return $this->templateData['filename'];
	}

	public function getResourceUrl(): string {
		return $this->templateData['link'];
	}

	public function getNote(): string {
		return $this->templateData['note'];
	}

	public function getShareWith(): string {
		return $this->templateData['shareWith'];
	}

	public function getInitiatorDisplayName(): string {
		return $this->templateData['initiator'];
	}

	public function getExpiration(): ?\DateTime {
		return $this->templateData['expiration'];
	}
}
