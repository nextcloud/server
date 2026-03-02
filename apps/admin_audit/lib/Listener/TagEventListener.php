<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\SystemTag\Events\TagCreatedEvent;

/**
 * @template-implements IEventListener<TagCreatedEvent>
 */
class TagEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof TagCreatedEvent) {
			return;
		}

		$tag = $event->getTag();

		$this->log('System tag "%s" (%s, %s) created',
			[
				'name' => $tag->getName(),
				'visibility' => $tag->isUserVisible() ? 'visible' : 'invisible',
				'assignable' => $tag->isUserAssignable() ? 'user assignable' : 'system only',
			],
			['name', 'visibility', 'assignable']
		);
	}
}
