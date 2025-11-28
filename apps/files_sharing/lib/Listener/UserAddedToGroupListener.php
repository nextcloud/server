<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Circle;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IConfig;
use OCP\Share\Events\UserAddedToShareEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Container\ContainerInterface;

/** @template-implements IEventListener<UserAddedEvent> */
class UserAddedToGroupListener extends GroupListenerBase implements IEventListener {
	public function __construct(
		private readonly IManager $shareManager,
		private readonly IConfig $config,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserAddedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		// Get all group shares this user has access too now to filter later
		$groupShares = $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_GROUP, null, -1);

		foreach ($groupShares as $share) {
			// If this is not the new group we can skip it
			if ($share->getSharedWith() !== $group->getGID()) {
				continue;
			}

			// Accept the share if needed
			if ($this->hasAutoAccept($user->getUID())) {
				$this->shareManager->acceptShare($share, $user->getUID());
			}

			$this->eventDispatcher->dispatchTyped(new UserAddedToShareEvent($share, $user));
		}

		if ($this->appManager->isAppLoaded('circles')) {
			// Get all circle shares this user has access too now to filter later
			$circleShares = $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_CIRCLE, null, -1);
			if (count($circleShares) === 0) {
				return;
			}

			/** @var CircleRequest $circlesRequest */
			$circlesRequest = $this->container->get(CircleRequest::class);
			$circles = $this->getCirclesForGroup($circlesRequest, $group->getGID());
			$circleIds = array_map(fn (Circle $circle) => $circle->getSingleId(), $circles);

			foreach ($circleShares as $share) {
				if (!in_array($share->getSharedWith(), $circleIds)) {
					continue;
				}
				// todo: detect if this share is new for the user because it only has access trough the new group
				// or if the user already had access before

				$this->eventDispatcher->dispatchTyped(new UserAddedToShareEvent($share, $user));
			}
		}
	}


	private function hasAutoAccept(string $userId): bool {
		$defaultAcceptSystemConfig = $this->config->getSystemValueBool('sharing.enable_share_accept', false) ? 'no' : 'yes';
		$acceptDefault = $this->config->getUserValue($userId, Application::APP_ID, 'default_accept', $defaultAcceptSystemConfig) === 'yes';
		return (!$this->config->getSystemValueBool('sharing.force_share_accept', false) && $acceptDefault);
	}
}
