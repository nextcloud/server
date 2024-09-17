<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Controller;

use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Group\ISubAdmin;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Settings\IDeclarativeManager;
use OCP\Settings\IManager as ISettingsManager;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class AdminSettingsController extends Controller {
	use CommonSettingsTrait;

	public function __construct(
		$appName,
		IRequest $request,
		INavigationManager $navigationManager,
		ISettingsManager $settingsManager,
		IUserSession $userSession,
		IGroupManager $groupManager,
		ISubAdmin $subAdmin,
		IDeclarativeManager $declarativeSettingsManager,
		IInitialState $initialState,
	) {
		parent::__construct($appName, $request);
		$this->navigationManager = $navigationManager;
		$this->settingsManager = $settingsManager;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->subAdmin = $subAdmin;
		$this->declarativeSettingsManager = $declarativeSettingsManager;
		$this->initialState = $initialState;
	}

	/**
	 * @NoSubAdminRequired
	 * We are checking the permissions in the getSettings method. If there is no allowed
	 * settings for the given section. The user will be gretted by an error message.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(string $section): TemplateResponse {
		return $this->getIndexResponse('admin', $section);
	}

	/**
	 * @param string $section
	 * @return array
	 */
	protected function getSettings($section) {
		/** @var IUser $user */
		$user = $this->userSession->getUser();
		$settings = $this->settingsManager->getAllowedAdminSettings($section, $user);
		$declarativeFormIDs = $this->declarativeSettingsManager->getFormIDs($user, 'admin', $section);
		if (empty($settings) && empty($declarativeFormIDs)) {
			throw new NotAdminException("Logged in user doesn't have permission to access these settings.");
		}
		$formatted = $this->formatSettings($settings);
		return $formatted;
	}
}
