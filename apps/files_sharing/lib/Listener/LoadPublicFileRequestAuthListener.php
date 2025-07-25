<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\AppInfo\Application;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Share\IManager;
use OCP\Util;

/** @template-implements IEventListener<BeforeTemplateRenderedEvent> */
class LoadPublicFileRequestAuthListener implements IEventListener {
	public function __construct(
		private IManager $shareManager,
		private IInitialState $initialState,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof BeforeTemplateRenderedEvent) {
			return;
		}

		// Make sure we are on a public page rendering
		if ($event->getResponse()->getRenderAs() !== TemplateResponse::RENDER_AS_PUBLIC) {
			return;
		}

		$token = $event->getResponse()->getParams()['sharingToken'] ?? null;
		if ($token === null || $token === '') {
			return;
		}

		// Check if the share is a file request
		$isFileRequest = false;
		try {
			$share = $this->shareManager->getShareByToken($token);
			$attributes = $share->getAttributes();
			if ($attributes === null) {
				return;
			}

			$isFileRequest = $attributes->getAttribute('fileRequest', 'enabled') === true;
		} catch (\Exception $e) {
			// Ignore, this is not a file request or the share does not exist
		}

		Util::addScript(Application::APP_ID, 'public-nickname-handler');

		// Add file-request script if needed
		$this->initialState->provideInitialState('isFileRequest', $isFileRequest);
	}
}
