<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Listener;

use OC\Core\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\InteractionRestrictedException;
use OCP\Interaction\Receivers\EmailReceiver;
use OCP\Interaction\Receivers\GroupReceiver;
use OCP\Interaction\Receivers\LinkReceiver;
use OCP\Interaction\Receivers\RemoteGroupReceiver;
use OCP\Interaction\Receivers\RemoteUserReceiver;
use OCP\Interaction\Receivers\UserReceiver;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\L10N\IFactory;
use OCP\Share\IManager;

/**
 * @template-implements IEventListener<RestrictInteractionEvent>
 */
final class RestrictInteractionListener implements IEventListener {
	private readonly IL10N $l10n;

	public function __construct(
		private IManager $manager,
		private IGroupManager $groupManager,
		IFactory $l10nFactory,
	) {
		$this->l10n = $l10nFactory->get(Application::APP_ID);
	}

	/**
	 * @param RestrictInteractionEvent $event
	 */
	#[\Override]
	public function handle(Event $event): void {
		if ($event->action instanceof ShareAction) {
			if (!$this->manager->shareApiEnabled()) {
				throw new InteractionRestrictedException('Sharing is not allowed.', $this->l10n->t('Sharing is not allowed.'));
			}

			if ($this->manager->sharingDisabledForUser($event->userId)) {
				throw new InteractionRestrictedException('Sharing is not allowed for the user.', $this->l10n->t('Sharing is not allowed for you.'));
			}

			foreach ($event->receivers as $receiver) {
				if ($this->manager->shareWithGroupMembersOnly()) {
					if ($receiver instanceof UserReceiver) {
						$groups = array_intersect(
							$this->groupManager->getUserGroupIds($event->getUser()),
							$this->groupManager->getUserGroupIds($receiver->getUser()),
						);

						$groups = array_diff($groups, $this->manager->shareWithGroupMembersOnlyExcludeGroupsList());

						if ($groups === []) {
							throw new InteractionRestrictedException('Sharing is only allowed with group members.', $this->l10n->t('Sharing is only allowed with group members.'));
						}
					}

					if ($receiver instanceof GroupReceiver && (!$receiver->getGroup()->inGroup($event->getUser()) || in_array($receiver->getGroup()->getGID(), $this->manager->shareWithGroupMembersOnlyExcludeGroupsList(), true))) {
						throw new InteractionRestrictedException('Sharing is only allowed to the groups the user is a member of.', $this->l10n->t('Sharing is only allowed within your own groups.'));
					}
				}

				if ($receiver instanceof GroupReceiver && !$this->manager->allowGroupSharing()) {
					throw new InteractionRestrictedException('Group sharing is not allowed.', $this->l10n->t('Group sharing is not allowed.'));
				}

				if (($receiver instanceof LinkReceiver || $receiver instanceof EmailReceiver) && !$this->manager->shareApiAllowLinks($event->getUser())) {
					throw new InteractionRestrictedException('Public link sharing is not allowed.', $this->l10n->t('Public link sharing is not allowed.'));
				}

				if ($receiver instanceof RemoteUserReceiver && !$this->manager->outgoingServer2ServerSharesAllowed()) {
					throw new InteractionRestrictedException('Sharing to remote users is not allowed.', $this->l10n->t('Sharing to remote users is not allowed.'));
				}

				if ($receiver instanceof RemoteGroupReceiver && !$this->manager->outgoingServer2ServerGroupSharesAllowed()) {
					throw new InteractionRestrictedException('Sharing to remote groups is not allowed.', $this->l10n->t('Sharing to remote groups is not allowed.'));
				}
			}
		}
	}
}
