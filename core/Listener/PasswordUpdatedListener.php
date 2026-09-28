<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\VerificationToken\IVerificationToken;
use OCP\User\Events\PasswordUpdatedEvent;

/**
 * @template-implements IEventListener<PasswordUpdatedEvent>
 */
class PasswordUpdatedListener implements IEventListener {
	public function __construct(
		readonly private IVerificationToken $verificationToken,
	) {

	}

	public function handle(Event $event): void {
		if ($event instanceof PasswordUpdatedEvent) {
			$this->verificationToken->delete('', $event->getUser(), 'lostpassword');
		}
	}
}
