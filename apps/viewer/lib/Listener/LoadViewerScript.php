<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Viewer\AppInfo\Application;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IPreview;
use OCP\Util;

/**
 * @template-implements IEventListener<Event>
 */
class LoadViewerScript implements IEventListener {
	private IInitialState $initialStateService;
	private IPreview $previewManager;

	public function __construct(
		IInitialState $initialStateService,
		IPreview $previewManager,
	) {
		$this->initialStateService = $initialStateService;
		$this->previewManager = $previewManager;
	}

	public function handle(Event $event): void {
		if (!($event instanceof LoadViewer || $event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}

		Util::addStyle(Application::APP_ID, 'viewer-init');
		Util::addStyle(Application::APP_ID, 'viewer-main');
		Util::addInitScript(Application::APP_ID, 'viewer-init');
		Util::addScript(Application::APP_ID, 'viewer-main', 'files');
		$this->initialStateService->provideInitialState('enabled_preview_providers', array_keys($this->previewManager->getProviders()));
	}
}
