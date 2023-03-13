<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\App\Events\AppUpdateEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements UserManagementEventListener<AppEnableEvent|AppDisableEvent|AppUpdateEvent>
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
