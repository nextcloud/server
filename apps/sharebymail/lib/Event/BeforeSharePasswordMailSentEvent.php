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
 * Fired by ShareByMailProvider::sendPassword() and sendPasswordToOwner()
 * immediately before the native mailer->send() call for password emails.
 *
 * For sendPassword(), initiatorEmail may be null when the initiator has no
 * email address configured. For sendPasswordToOwner() it is always a non-null
 * string (the call site throws earlier if the owner has no email address).
 *
 * @psalm-type TemplateData = array{
 *   filename: string,
 *   password: string,
 *   initiator: string,
 *   initiatorEmail: string|null,
 *   shareWith: string,
 * }
 *
 * @psalm-api
 */
class BeforeSharePasswordMailSentEvent extends AbstractBeforeShareMailSentEvent {
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

	public function getPassword(): string {
		return $this->templateData['password'];
	}

	public function getInitiatorDisplayName(): string {
		return $this->templateData['initiator'];
	}

	public function getInitiatorEmail(): ?string {
		return $this->templateData['initiatorEmail'];
	}

	public function getShareWith(): string {
		return $this->templateData['shareWith'];
	}
}
