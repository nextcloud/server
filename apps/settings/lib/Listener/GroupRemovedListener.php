<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Listener;

use OCA\Settings\Service\AuthorizedGroupService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;

/** @template-implements IEventListener<GroupDeletedEvent> */
class GroupRemovedListener implements IEventListener {

	/** @var AuthorizedGroupService $authorizedGroupService */
	private $authorizedGroupService;

	public function __construct(AuthorizedGroupService $authorizedGroupService) {
		$this->authorizedGroupService = $authorizedGroupService;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if (!($event instanceof GroupDeletedEvent)) {
			return;
		}

		/** @var GroupDeletedEvent $event */
		$this->authorizedGroupService->removeAuthorizationAssociatedTo($event->getGroup());
	}
}
