<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Julius Härtl <jus@bitgrid.net>
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

namespace OCA\UserStatus\Listener;

use OCA\UserStatus\AppInfo\Application;
use OCA\UserStatus\Service\JSDataService;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IInitialStateService;
use OCP\IUserSession;

class BeforeTemplateRenderedListener implements IEventListener {
	use \OC\Profile\TProfileHelper;

	/** @var IAccountManager */
	private $accountManager;

	/** @var IUserSession */
	private $userSession;

	/** @var IInitialStateService */
	private $initialState;

	/** @var JSDataService */
	private $jsDataService;

	/**
	 * BeforeTemplateRenderedListener constructor.
	 *
	 * @param IAccountManager $accountManager
	 * @param IUserSession $userSession
	 * @param IInitialStateService $initialState
	 * @param JSDataService $jsDataService
	 */
	public function __construct(
		IAccountManager $accountManager,
		IUserSession $userSession,
		IInitialStateService $initialState,
		JSDataService $jsDataService
	) {
		$this->accountManager = $accountManager;
		$this->userSession = $userSession;
		$this->initialState = $initialState;
		$this->jsDataService = $jsDataService;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}
		$account = $this->accountManager->getAccount($user);

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

		$this->initialState->provideLazyInitialState(Application::APP_ID, 'profileEnabled', function () use ($account) {
			return ['profileEnabled' => $this->isProfileEnabled($account)];
		});

		\OCP\Util::addScript('user_status', 'menu');
		\OCP\Util::addStyle('user_status', 'user-status-menu');
	}
}
