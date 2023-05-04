<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OCA\Settings\Controller;

use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Group\ISubAdmin;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Settings\IManager as ISettingsManager;
use OCP\Template;

class AdminSettingsController extends Controller {
	use CommonSettingsTrait;

	public function __construct(
		$appName,
		IRequest $request,
		INavigationManager $navigationManager,
		ISettingsManager $settingsManager,
		IUserSession $userSession,
		IGroupManager $groupManager,
		ISubAdmin $subAdmin
	) {
		parent::__construct($appName, $request);
		$this->navigationManager = $navigationManager;
		$this->settingsManager = $settingsManager;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->subAdmin = $subAdmin;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * We are checking the permissions in the getSettings method. If there is no allowed
	 * settings for the given section. The user will be gretted by an error message.
	 */
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
		$isSubAdmin = !$this->groupManager->isAdmin($user->getUID()) && $this->subAdmin->isSubAdmin($user);
		$settings = $this->settingsManager->getAllowedAdminSettings($section, $user);
		if (empty($settings)) {
			throw new NotAdminException("Logged in user doesn't have permission to access these settings.");
		}
		$formatted = $this->formatSettings($settings);
		// Do not show legacy forms for sub admins
		if ($section === 'additional' && !$isSubAdmin) {
			$formatted['content'] .= $this->getLegacyForms();
		}
		return $formatted;
	}

	/**
	 * @return bool|string
	 */
	private function getLegacyForms() {
		$forms = \OC_App::getForms('admin');

		$forms = array_map(function ($form) {
			if (preg_match('%(<h2(?P<class>[^>]*)>.*?</h2>)%i', $form, $regs)) {
				$sectionName = str_replace('<h2' . $regs['class'] . '>', '', $regs[0]);
				$sectionName = str_replace('</h2>', '', $sectionName);
				$anchor = strtolower($sectionName);
				$anchor = str_replace(' ', '-', $anchor);

				return [
					'anchor' => $anchor,
					'section-name' => $sectionName,
					'form' => $form
				];
			}
			return [
				'form' => $form
			];
		}, $forms);

		$out = new Template('settings', 'settings/additional');
		$out->assign('forms', $forms);

		return $out->fetchPage();
	}
}
