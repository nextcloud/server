<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller;

use OCA\Settings\Controller\PersonalSettingsController;
use OCA\Settings\Settings\Personal\ServerDevNotice;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Group\ISubAdmin;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\Settings\IDeclarativeManager;
use OCP\Settings\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @package Tests\Settings\Controller
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class PersonalSettingsControllerTest extends TestCase {

	private IRequest&MockObject $request;
	private INavigationManager&MockObject $navigationManager;
	private IManager&MockObject $settingsManager;
	private IUserSession&MockObject $userSession;
	private IGroupManager&MockObject $groupManager;
	private ISubAdmin&MockObject $subAdmin;
	private IDeclarativeManager&MockObject $declarativeSettingsManager;
	private IInitialState&MockObject $initialState;

	private string $uid = 'personalsettingsuser';
	private PersonalSettingsController $personalSettingsController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->settingsManager = $this->createMock(IManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->subAdmin = $this->createMock(ISubAdmin::class);
		$this->declarativeSettingsManager = $this->createMock(IDeclarativeManager::class);
		$this->initialState = $this->createMock(IInitialState::class);

		$this->personalSettingsController = new PersonalSettingsController(
			'settings',
			$this->request,
			$this->navigationManager,
			$this->settingsManager,
			$this->userSession,
			$this->groupManager,
			$this->subAdmin,
			$this->declarativeSettingsManager,
			$this->initialState,
		);

		$user = Server::get(IUserManager::class)->createUser($this->uid, 'mylongrandompassword');
		\OC_User::setUserId($user->getUID());
	}

	protected function tearDown(): void {
		Server::get(IUserManager::class)
			->get($this->uid)
			->delete();
		\OC_User::setUserId(null);
		Server::get(IUserSession::class)->setUser(null);

		parent::tearDown();
	}

	/**
	 * Marks the section we are about to render so the rest of getIndexResponse()
	 * has something to format. The actual content is irrelevant to these tests.
	 */
	private function stubSettingsFor(string $section): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$this->userSession->method('getUser')->willReturn($user);

		$form = new TemplateResponse('settings', 'settings/empty');
		$setting = $this->createMock(ServerDevNotice::class);
		$setting->method('getForm')->willReturn($form);

		$this->settingsManager
			->method('getPersonalSettings')
			->with($section)
			->willReturn([5 => [$setting]]);
		$this->settingsManager->method('getPersonalSections')->willReturn([]);
		$this->settingsManager->method('getAdminSections')->willReturn([]);
		$this->declarativeSettingsManager->method('getFormIDs')->willReturn([]);
		$this->declarativeSettingsManager->method('getFormsWithValues')->willReturn([]);
	}

	public function testIndexActivatesPersonalNavEntry(): void {
		$this->stubSettingsFor('additional');

		// Must match the id the personal nav entry is registered under in
		// Application.php ('settings_personal'), otherwise the header
		// current-app button is hidden on personal settings pages.
		$this->navigationManager
			->expects($this->once())
			->method('setActiveEntry')
			->with('settings_personal');

		$this->personalSettingsController->index('additional');
	}

	public function testThemingSectionActivatesAccessibilityNavEntry(): void {
		$this->stubSettingsFor('theming');

		// The appearance/accessibility section keeps its own nav entry.
		$this->navigationManager
			->expects($this->once())
			->method('setActiveEntry')
			->with('accessibility_settings');

		$this->personalSettingsController->index('theming');
	}
}
