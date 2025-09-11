<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Encryption;

use OC\Files\SetupManager;
use OC\Files\View;
use OCA\Files_Trashbin\Events\NodeRestoredEvent;
use OCP\Encryption\IFile;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<NodeRenamedEvent|ShareCreatedEvent|ShareDeletedEvent|NodeRestoredEvent> */
class EncryptionEventListener implements IEventListener {
	private ?Update $updater = null;

	public function __construct(
		private IUserSession $userSession,
		private SetupManager $setupManager,
		private Manager $encryptionManager,
		private IUserManager $userManager,
	) {
	}

	public static function register(IEventDispatcher $dispatcher): void {
		$dispatcher->addServiceListener(NodeRenamedEvent::class, static::class);
		$dispatcher->addServiceListener(ShareCreatedEvent::class, static::class);
		$dispatcher->addServiceListener(ShareDeletedEvent::class, static::class);
		$dispatcher->addServiceListener(NodeRestoredEvent::class, static::class);
	}

	public function handle(Event $event): void {
		if (!$this->encryptionManager->isEnabled()) {
			return;
		}
		if ($event instanceof NodeRenamedEvent) {
			$this->getUpdate()->postRename($event->getSource(), $event->getTarget());
		} elseif ($event instanceof ShareCreatedEvent) {
			$this->getUpdate()->postShared($event->getShare()->getNode());
		} elseif ($event instanceof ShareDeletedEvent) {
			try {
				// In case the unsharing happens in a background job, we don't have
				// a session and we load instead the user from the UserManager
				$owner = $this->userManager->get($event->getShare()->getShareOwner());
				$this->getUpdate($owner)->postUnshared($event->getShare()->getNode());
			} catch (NotFoundException $e) {
				/* The node was deleted already, nothing to update */
			}
		} elseif ($event instanceof NodeRestoredEvent) {
			$this->getUpdate()->postRestore($event->getTarget());
		}
	}

	private function getUpdate(?IUser $owner = null): Update {
		$user = $this->userSession->getUser();
		if (!$user && ($owner !== null)) {
			$user = $owner;
		}
		if ($user) {
			if (!$this->setupManager->isSetupComplete($user)) {
				$this->setupManager->setupForUser($user);
			}
		}
		if (is_null($this->updater)) {
			$this->updater = new Update(
				new Util(
					new View(),
					$this->userManager,
					\OC::$server->getGroupManager(),
					\OC::$server->getConfig()),
				\OC::$server->getEncryptionManager(),
				\OC::$server->get(IFile::class),
				\OC::$server->get(LoggerInterface::class),
			);
		}

		return $this->updater;
	}
}
