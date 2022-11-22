<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\Theming\Listener;

use OCA\Theming\AppInfo\Application;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class BeforePreferenceListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof BeforePreferenceSetEvent
			&& !$event instanceof BeforePreferenceDeletedEvent) {
			return;
		}

		if ($event->getAppId() !== Application::APP_ID) {
			return;
		}

		if ($event->getConfigKey() !== 'shortcuts_disabled') {
			return;
		}

		if ($event instanceof BeforePreferenceSetEvent) {
			$event->setValid($event->getConfigValue() === 'yes');
			return;
		}

		$event->setValid(true);
	}
}
