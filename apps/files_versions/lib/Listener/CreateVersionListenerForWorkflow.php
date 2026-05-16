<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Listener;

use OCA\Files_Versions\BlockVersioningOperation;
use OCA\Files_Versions\Events\CreateVersionEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\WorkflowEngine\IManager;

/** @template-implements IEventListener<CreateVersionEvent> */
class CreateVersionListenerForWorkflow implements IEventListener {

	public function __construct(
		private IManager $manager,
		private BlockVersioningOperation $operation,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof CreateVersionEvent)) {
			return;
		}

		$this->operation->onEvent(
			$event::class,
			$event,
			$this->manager->getRuleMatcher(),
		);
	}
}
