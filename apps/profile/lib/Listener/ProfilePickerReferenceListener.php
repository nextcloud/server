<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Profile\Listener;

use OCA\Profile\AppInfo\Application;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use OCP\Profile\IProfileManager;
use OCP\Util;

/**
 * @template-implements IEventListener<RenderReferenceEvent>
 */
class ProfilePickerReferenceListener implements IEventListener {

	public function __construct(
		private IAppConfig $appConfig,
		private IProfileManager $profileManager,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof RenderReferenceEvent) {
			return;
		}

		$profileEnabledGlobally = $this->profileManager->isProfileEnabled();

		$profilePickerEnabled = filter_var(
			$this->appConfig->getValueString('settings', 'profile_picker_enabled', '1'),
			FILTER_VALIDATE_BOOLEAN,
			FILTER_NULL_ON_FAILURE,
		);

		if ($profileEnabledGlobally && $profilePickerEnabled) {
			Util::addScript(Application::APP_ID, 'reference');
		}
	}
}
