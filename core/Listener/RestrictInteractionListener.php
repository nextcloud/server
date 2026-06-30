<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IGroupManager;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\InteractionRestrictedException;
use OCP\Interaction\Receivers\EmailReceiver;
use OCP\Interaction\Receivers\GroupReceiver;
use OCP\Interaction\Receivers\LinkReceiver;
use OCP\Interaction\Receivers\RemoteGroupReceiver;
use OCP\Interaction\Receivers\RemoteUserReceiver;
use OCP\Interaction\Receivers\UserReceiver;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\Share\IManager;

/**
 * @template-implements IEventListener<RestrictInteractionEvent>
 */
final readonly class RestrictInteractionListener implements IEventListener {
	public function __construct(
		private IManager $manager,
		private IGroupManager $groupManager,
	) {
	}

	/**
	 * @param RestrictInteractionEvent $event
	 */
	#[\Override]
	public function handle(Event $event): void {
		if ($event->action instanceof ShareAction) {
			if (!$this->manager->shareApiEnabled()) {
				throw new InteractionRestrictedException('Sharing is disabled.');
			}

			if ($this->manager->sharingDisabledForUser($event->userId)) {
				throw new InteractionRestrictedException('Sharing is disabled for the user.');
			}

			if ($this->manager->shareWithGroupMembersOnly()) {
				if ($event->receiver instanceof UserReceiver) {
					$groups = array_intersect(
						$this->groupManager->getUserGroupIds($event->getUser()),
						$this->groupManager->getUserGroupIds($event->receiver->getUser()),
					);

					$groups = array_diff($groups, $this->manager->shareWithGroupMembersOnlyExcludeGroupsList());

					if ($groups === []) {
						throw new InteractionRestrictedException('Sharing to user is not allowed.');
					}
				}

				if ($event->receiver instanceof GroupReceiver && (!$event->receiver->getGroup()->inGroup($event->getUser()) || in_array($event->receiver->getGroup()->getGID(), $this->manager->shareWithGroupMembersOnlyExcludeGroupsList(), true))) {
					throw new InteractionRestrictedException('Sharing to group is not allowed.');
				}
			}

			if ($event->receiver instanceof GroupReceiver && !$this->manager->allowGroupSharing()) {
				throw new InteractionRestrictedException('Sharing to groups is not allowed.');
			}

			if (($event->receiver instanceof LinkReceiver || $event->receiver instanceof EmailReceiver) && !$this->manager->shareApiAllowLinks($event->getUser())) {
				throw new InteractionRestrictedException('Public sharing is not allowed.');
			}

			if ($event->receiver instanceof RemoteUserReceiver && !$this->manager->outgoingServer2ServerSharesAllowed()) {
				throw new InteractionRestrictedException('Sharing to remote users is not allowed.');
			}

			if ($event->receiver instanceof RemoteGroupReceiver && !$this->manager->outgoingServer2ServerGroupSharesAllowed()) {
				throw new InteractionRestrictedException('Sharing to remote groups is not allowed.');
			}
		}
	}
}
