<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Listener;

use OC\Profile\ProfileManager;
use OCA\UserStatus\AppInfo\Application;
use OCA\UserStatus\Service\JSDataService;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IInitialStateService;
use OCP\IUserSession;
use OCP\Util;

/** @template-implements IEventListener<BeforeTemplateRenderedEvent> */
class BeforeTemplateRenderedListener implements IEventListener {

	/** @var ProfileManager */
	private $profileManager;

	/**
	 * BeforeTemplateRenderedListener constructor.
	 *
	 * @param ProfileManager $profileManager
	 * @param IUserSession $userSession
	 * @param IInitialStateService $initialState
	 * @param JSDataService $jsDataService
	 */
	public function __construct(
		ProfileManager $profileManager,
		private IUserSession $userSession,
		private IInitialStateService $initialState,
		private JSDataService $jsDataService,
	) {
		$this->profileManager = $profileManager;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}

		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			// Unrelated
			return;
		}

		if (!$event->isLoggedIn() || $event->getResponse()->getRenderAs() !== TemplateResponse::RENDER_AS_USER) {
			return;
		}

		$this->initialState->provideLazyInitialState(Application::APP_ID, 'status', function () {
			return $this->jsDataService;
		});

		$this->initialState->provideLazyInitialState(Application::APP_ID, 'profileEnabled', function () use ($user) {
			return ['profileEnabled' => $this->profileManager->isProfileEnabled($user)];
		});

		Util::addScript('user_status', 'menu');
		Util::addStyle('user_status', 'user-status-menu');
	}
}
