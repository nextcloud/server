<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Listener;

use OCA\Files_Versions\BlockVersioningOperation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\LoadSettingsScriptsEvent;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;

/** @template-implements IEventListener<RegisterOperationsEvent|LoadSettingsScriptsEvent> */
class RegisterWorkflowIntegrationListener implements IEventListener {

	public function __construct(
		private readonly BlockVersioningOperation $operation,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof RegisterOperationsEvent) {
			$event->registerOperation($this->operation);
		} elseif ($event instanceof LoadSettingsScriptsEvent) {
			Util::addScript('files_versions', 'workflow', 'workflowengine');
		}
	}
}
