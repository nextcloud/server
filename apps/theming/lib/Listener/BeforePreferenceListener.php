<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Listener;

use OCA\Theming\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<BeforePreferenceDeletedEvent|BeforePreferenceSetEvent> */
class BeforePreferenceListener implements IEventListener {

	/**
	 * @var string[]
	 */
	private const ALLOWED_KEYS = ['force_enable_blur_filter', 'shortcuts_disabled', 'primary_color'];

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
				case 'primary_color':
					$event->setValid(preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $event->getConfigValue()) === 1);
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
