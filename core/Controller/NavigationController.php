<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;

class NavigationController extends Controller {

	/** @var INavigationManager */
	private $navigationManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(string $appName, IRequest $request, INavigationManager $navigationManager, IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);
		$this->navigationManager = $navigationManager;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param bool $absolute
	 * @return JSONResponse
	 */
	public function getAppsNavigation(bool $absolute = false) {
		$navigation = $this->navigationManager->getAll('link');
		if ($absolute) {
			$this->rewriteToAbsoluteUrls($navigation);
		}
		return new JSONResponse($navigation);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param bool $absolute
	 * @return JSONResponse
	 */
	public function getSettingsNavigation(bool $absolute = false) {
		$navigation = $this->navigationManager->getAll('settings');
		if ($absolute) {
			$this->rewriteToAbsoluteUrls($navigation);
		}
		return new JSONResponse($navigation);
	}

	/**
	 * Rewrite href attribute of navigation entries to an absolute URL
	 *
	 * @param array $navigation
	 */
	private function rewriteToAbsoluteUrls(array &$navigation) {
		foreach ($navigation as &$entry) {
			if (substr($entry['href'], 0, strlen($this->urlGenerator->getBaseUrl())) !== $this->urlGenerator->getBaseUrl()) {
				$entry['href'] = $this->urlGenerator->getAbsoluteURL($entry['href']);
			}
		}
	}
}
