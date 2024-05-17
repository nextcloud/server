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
use OCP\App\IAppManager;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class BeforePreferenceListener implements IEventListener {

	/**
	 * @var string[]
	 */
	private const ALLOWED_KEYS = ['force_enable_blur_filter', 'shortcuts_disabled'];

	public function __construct(
		private IAppManager $appManager,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof BeforePreferenceSetEvent
			&& !$event instanceof BeforePreferenceDeletedEvent) {
			// Invalid event type
			return;
		}

		switch ($event->getAppId()) {
			case Application::APP_ID: $this->handleThemingValues($event);
				break;
			case 'core': $this->handleCoreValues($event);
				break;
		}
	}

	private function handleThemingValues(BeforePreferenceSetEvent|BeforePreferenceDeletedEvent $event): void {
		if (!in_array($event->getConfigKey(), self::ALLOWED_KEYS)) {
			// Not allowed config key
			return;
		}

		if ($event instanceof BeforePreferenceSetEvent) {
			switch ($event->getConfigKey()) {
				case 'force_enable_blur_filter':
					$event->setValid($event->getConfigValue() === 'yes' || $event->getConfigValue() === 'no');
					break;
				case 'shortcuts_disabled':
					$event->setValid($event->getConfigValue() === 'yes');
					break;
				default:
					$event->setValid(false);
			}
			return;
		}

		$event->setValid(true);
	}

	private function handleCoreValues(BeforePreferenceSetEvent|BeforePreferenceDeletedEvent $event): void {
		if ($event->getConfigKey() !== 'apporder') {
			// Not allowed config key
			return;
		}

		if ($event instanceof BeforePreferenceDeletedEvent) {
			$event->setValid(true);
			return;
		}

		$value = json_decode($event->getConfigValue(), true, flags:JSON_THROW_ON_ERROR);
		if (!is_array(($value))) {
			// Must be an array
			return;
		}

		foreach ($value as $id => $info) {
			// required format: [ navigation_id: string => [ order: int, app?: string ] ]
			if (!is_string($id) || !is_array($info) || empty($info) || !isset($info['order']) || !is_numeric($info['order']) || (isset($info['app']) && !$this->appManager->isEnabledForUser($info['app']))) {
				// Invalid config value, refuse the change
				return;
			}
		}
		$event->setValid(true);
	}
}
