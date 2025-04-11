<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Util;

/** @template-implements IEventListener<LoadAdditionalScriptsEvent> */
class LoadAdditionalListener implements IEventListener {
	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}

		// After files for the breadcrumb share indicator
		Util::addScript(Application::APP_ID, 'additionalScripts', 'files');
		Util::addStyle(Application::APP_ID, 'icons');

		$shareManager = Server::get(IManager::class);
		if ($shareManager->shareApiEnabled() && class_exists('\OCA\Files\App')) {
			Util::addInitScript(Application::APP_ID, 'init');
		}
	}
}
