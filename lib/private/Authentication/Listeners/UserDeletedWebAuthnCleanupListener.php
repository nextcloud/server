<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Listeners;

use OC\Authentication\WebAuthn\Db\PublicKeyCredentialMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/** @template-implements IEventListener<UserDeletedEvent> */
class UserDeletedWebAuthnCleanupListener implements IEventListener {
	/** @var PublicKeyCredentialMapper */
	private $credentialMapper;

	public function __construct(PublicKeyCredentialMapper $credentialMapper) {
		$this->credentialMapper = $credentialMapper;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		$this->credentialMapper->deleteByUid($event->getUser()->getUID());
	}
}
