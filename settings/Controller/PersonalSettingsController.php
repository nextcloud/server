<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\Settings\IManager as ISettingsManager;

class PersonalSettingsController extends Controller {
	use CommonSettingsTrait {
		getSettings as private;
	}

	/** @var INavigationManager */
	private $navigationManager;

	public function __construct(
		$appName,
		IRequest $request,
		INavigationManager $navigationManager,
		ISettingsManager $settingsManager
	) {
		parent::__construct($appName, $request);
		$this->navigationManager = $navigationManager;
		$this->settingsManager = $settingsManager;
	}

	/**
	 * @param string $section
	 * @return TemplateResponse
	 *
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 */
	public function index($section) {
		$this->navigationManager->setActiveEntry('personal');
		return $this->getIndexResponse($section);
	}

	/**
	 * @param string $section
	 * @return array
	 */
	private function getSettings($section) {
		// PhpStorm shows this as unused, but is required by CommonSettingsTrait
		$settings = $this->settingsManager->getPersonalSettings($section);
		return $this->formatSettings($settings);
	}
}
