<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use OC\Core\AppInfo\Application;
use OCP\Interaction\InteractionReceiver;
use OCP\Interaction\Receivers\EmailReceiver;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\IEmailValidator;
use OCP\Server;
use OCP\Share\IShare;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Recipient\AShareRecipientTypeSearchCollaborator;

// TODO: Add logic to send emails when share state is updated to active

final class EmailShareRecipientType extends AShareRecipientTypeSearchCollaborator {
	private ?IEmailValidator $emailValidator = null;

	private function getEmailValidator(): IEmailValidator {
		return $this->emailValidator ??= Server::get(IEmailValidator::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Email');
	}

	#[\Override]
	public function validateRecipient(string $recipient): bool {
		return $this->getEmailValidator()->isValid($recipient);
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		return [];
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): string {
		return $recipient;
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return null;
	}

	#[\Override]
	public function getRecipientInteractionReceiver(string $recipient): InteractionReceiver {
		return new EmailReceiver($recipient);
	}

	#[\Override]
	public function getCollaboratorType(): int {
		return IShare::TYPE_EMAIL;
	}

	#[\Override]
	public function getCollaboratorKey(): string {
		return 'emails';
	}
}
