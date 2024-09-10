<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\App\Events\AppUpdateEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<AppEnableEvent|AppDisableEvent|AppUpdateEvent>
 */
class AppManagementEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof AppEnableEvent) {
			$this->appEnable($event);
		} elseif ($event instanceof AppDisableEvent) {
			$this->appDisable($event);
		} elseif ($event instanceof AppUpdateEvent) {
			$this->appUpdate($event);
		}
	}

	private function appEnable(AppEnableEvent $event): void {
		if (empty($event->getGroupIds())) {
			$this->log('App "%s" enabled',
				['app' => $event->getAppId()],
				['app']
			);
		} else {
			$this->log('App "%1$s" enabled for groups: %2$s',
				['app' => $event->getAppId(), 'groups' => implode(', ', $event->getGroupIds())],
				['app', 'groups']
			);
		}
	}

	private function appDisable(AppDisableEvent $event): void {
		$this->log('App "%s" disabled',
			['app' => $event->getAppId()],
			['app']
		);
	}

	private function appUpdate(AppUpdateEvent $event): void {
		$this->log('App "%s" updated',
			['app' => $event->getAppId()],
			['app']
		);
	}
}
