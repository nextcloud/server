<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Listener;

use OCA\WorkflowEngine\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\LoadSettingsScriptsEvent;

/** @template-implements IEventListener<LoadSettingsScriptsEvent> */
class LoadAdditionalSettingsScriptsListener implements IEventListener {
	public function handle(Event $event): void {
		Util::addScript('core', 'files_fileinfo');
		Util::addScript('core', 'files_client');
		Util::addScript('core', 'systemtags');
		Util::addScript(Application::APP_ID, 'workflowengine');
	}
}
