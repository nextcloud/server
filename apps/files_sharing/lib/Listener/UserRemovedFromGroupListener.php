<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OC\Share20\DefaultShareProvider;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Model\Circle;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserRemovedEvent;
use OCP\Share\Events\UserRemovedFromShareEvent;
use Psr\Container\ContainerInterface;

/** @template-implements IEventListener<UserRemovedEvent> */
class UserRemovedFromGroupListener extends GroupListenerBase implements IEventListener {

	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly DefaultShareProvider $shareProvider,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserRemovedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		$shares = $this->shareProvider->getSharedWithGroup($group->getGID());

		foreach ($shares as $share) {
			$this->eventDispatcher->dispatchTyped(new UserRemovedFromShareEvent($share, $user));
		}

		if ($this->appManager->isAppLoaded('circles')) {
			/** @var CircleRequest $circlesRequest */
			$circlesRequest = $this->container->get(CircleRequest::class);
			$circles = $this->getCirclesForGroup($circlesRequest, $group->getGID());
			$circleIds = array_map(fn (Circle $circle) => $circle->getSingleId(), $circles);

			// todo: get shares by circles, see which of those the user still has access to, emit event for those that the user doesn't
		}
	}
}
