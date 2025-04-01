<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Encryption\Listeners;

use OC\Core\Events\BeforePasswordResetEvent;
use OC\Core\Events\PasswordResetEvent;
use OC\Files\SetupManager;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Services\PassphraseService;
use OCA\Encryption\Session;
use OCA\Encryption\Users\Setup;
use OCA\Encryption\Util;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\User\Events\BeforePasswordUpdatedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;
use OCP\User\Events\UserLoggedOutEvent;

/**
 * @template-implements IEventListener<UserCreatedEvent|UserDeletedEvent|UserLoggedInEvent|UserLoggedInWithCookieEvent|UserLoggedOutEvent|BeforePasswordUpdatedEvent|PasswordUpdatedEvent|BeforePasswordResetEvent|PasswordResetEvent>
 */
class UserEventsListener implements IEventListener {

	public function __construct(
		private Util $util,
		private Setup $userSetup,
		private Session $session,
		private KeyManager $keyManager,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private SetupManager $setupManager,
		private PassphraseService $passphraseService,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof UserCreatedEvent) {
			$this->onUserCreated($event->getUid(), $event->getPassword());
		} elseif ($event instanceof UserDeletedEvent) {
			$this->onUserDeleted($event->getUid());
		} elseif ($event instanceof UserLoggedInEvent || $event instanceof UserLoggedInWithCookieEvent) {
			$this->onUserLogin($event->getUser(), $event->getPassword());
		} elseif ($event instanceof UserLoggedOutEvent) {
			$this->onUserLogout();
		} elseif ($event instanceof BeforePasswordUpdatedEvent) {
			$this->onBeforePasswordUpdated($event->getUser(), $event->getPassword(), $event->getRecoveryPassword());
		} elseif ($event instanceof PasswordUpdatedEvent) {
			$this->onPasswordUpdated($event->getUid(), $event->getPassword(), $event->getRecoveryPassword());
		} elseif ($event instanceof BeforePasswordResetEvent) {
			$this->onBeforePasswordReset($event->getUid());
		} elseif ($event instanceof PasswordResetEvent) {
			$this->onPasswordReset($event->getUid(), $event->getPassword());
		}
	}

	/**
	 * Startup encryption backend upon user login
	 */
	private function onUserLogin(IUser $user, ?string $password): void {
		// ensure filesystem is loaded
		$this->setupManager->setupForUser($user);
		if ($this->util->isMasterKeyEnabled() === false) {
			// Skip if no master key and the password is not provided
			if ($password === null) {
				return;
			}

			$this->userSetup->setupUser($user->getUID(), $password);
		}

		$this->keyManager->init($user->getUID(), $password);
	}

	/**
	 * Remove keys from session during logout
	 */
	private function onUserLogout(): void {
		$this->session->clear();
	}

	/**
	 * Setup encryption backend upon user created
	 *
	 * This method should never be called for users using client side encryption
	 */
	protected function onUserCreated(string $userId, string $password): void {
		$this->userSetup->setupUser($userId, $password);
	}

	/**
	 * Cleanup encryption backend upon user deleted
	 *
	 * This method should never be called for users using client side encryption
	 */
	protected function onUserDeleted(string $userId): void {
		$this->keyManager->deletePublicKey($userId);
	}

	/**
	 * If the password can't be changed within Nextcloud, than update the key password in advance.
	 */
	public function onBeforePasswordUpdated(IUser $user, string $password, ?string $recoveryPassword = null): void {
		if (!$user->canChangePassword()) {
			$this->passphraseService->setPassphraseForUser($user->getUID(), $password, $recoveryPassword);
		}
	}

	/**
	 * Change a user's encryption passphrase
	 */
	public function onPasswordUpdated(string $userId, string $password, ?string $recoveryPassword): void {
		$this->passphraseService->setPassphraseForUser($userId, $password, $recoveryPassword);
	}

	/**
	 * Set user password resetting state to allow ignoring "reset"-requests on password update
	 */
	public function onBeforePasswordReset(string $userId): void {
		$this->passphraseService->setProcessingReset($userId);
	}

	/**
	 * Create new encryption keys on password reset and backup the old one
	 */
	public function onPasswordReset(string $userId, string $password): void {
		$this->keyManager->backupUserKeys('passwordReset', $userId);
		$this->keyManager->deleteUserKeys($userId);
		$this->userSetup->setupUser($userId, $password);
		$this->passphraseService->setProcessingReset($userId, false);
	}
}
