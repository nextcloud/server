<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OC\Core\Controller;

use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;

/**
 * @psalm-import-type CoreNavigationEntry from ResponseDefinitions
 */
class NavigationController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private INavigationManager $navigationManager,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get the apps navigation
	 *
	 * @param bool $absolute Rewrite URLs to absolute ones
	 * @return DataResponse<Http::STATUS_OK, CoreNavigationEntry[], array{}>|DataResponse<Http::STATUS_NOT_MODIFIED, array<empty>, array{}>
	 *
	 * 200: Apps navigation returned
	 * 304: No apps navigation changed
	 */
	public function getAppsNavigation(bool $absolute = false): DataResponse {
		$navigation = $this->navigationManager->getAll();
		if ($absolute) {
			$navigation = $this->rewriteToAbsoluteUrls($navigation);
		}
		$navigation = array_values($navigation);
		$etag = $this->generateETag($navigation);
		if ($this->request->getHeader('If-None-Match') === $etag) {
			return new DataResponse([], Http::STATUS_NOT_MODIFIED);
		}
		$response = new DataResponse($navigation);
		$response->setETag($etag);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get the settings navigation
	 *
	 * @param bool $absolute Rewrite URLs to absolute ones
	 * @return DataResponse<Http::STATUS_OK, CoreNavigationEntry[], array{}>|DataResponse<Http::STATUS_NOT_MODIFIED, array<empty>, array{}>
	 *
	 * 200: Apps navigation returned
	 * 304: No apps navigation changed
	 */
	public function getSettingsNavigation(bool $absolute = false): DataResponse {
		$navigation = $this->navigationManager->getAll('settings');
		if ($absolute) {
			$navigation = $this->rewriteToAbsoluteUrls($navigation);
		}
		$navigation = array_values($navigation);
		$etag = $this->generateETag($navigation);
		if ($this->request->getHeader('If-None-Match') === $etag) {
			return new DataResponse([], Http::STATUS_NOT_MODIFIED);
		}
		$response = new DataResponse($navigation);
		$response->setETag($etag);
		return $response;
	}

	/**
	 * Generate an ETag for a list of navigation entries
	 */
	private function generateETag(array $navigation): string {
		foreach ($navigation as &$nav) {
			if ($nav['id'] === 'logout') {
				$nav['href'] = 'logout';
			}
		}
		return md5(json_encode($navigation));
	}

	/**
	 * Rewrite href attribute of navigation entries to an absolute URL
	 */
	private function rewriteToAbsoluteUrls(array $navigation): array {
		foreach ($navigation as &$entry) {
			if (!str_starts_with($entry['href'], 'https://') && !str_starts_with($entry['href'], 'http://')) {
				$entry['href'] = $this->urlGenerator->getAbsoluteURL($entry['href']);
			}
			if (!str_starts_with($entry['icon'], 'https://') && !str_starts_with($entry['icon'], 'http://')) {
				$entry['icon'] = $this->urlGenerator->getAbsoluteURL($entry['icon']);
			}
		}
		return $navigation;
	}
}
