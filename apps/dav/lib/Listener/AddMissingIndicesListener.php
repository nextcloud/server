<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event|AddMissingIndicesEvent>
 */
class AddMissingIndicesListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof AddMissingIndicesEvent)) {
			return;
		}
		$event->addMissingIndex(
			'dav_shares',
			'dav_shares_resourceid_type',
			['resourceid', 'type']
		);
		$event->addMissingIndex(
			'dav_shares',
			'dav_shares_resourceid_access',
			['resourceid', 'access']
		);
	}

}
