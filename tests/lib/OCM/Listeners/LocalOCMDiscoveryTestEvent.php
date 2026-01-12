<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\OCM\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<\OCP\OCM\Events\LocalOCMDiscoveryEvent> */
class LocalOCMDiscoveryTestEvent implements IEventListener {
	public function __construct(
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof \OCP\OCM\Events\LocalOCMDiscoveryEvent)) {
			return;
		}

		$event->addCapability('ocm-capability-test');
	}
}
