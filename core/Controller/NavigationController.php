<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
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
	 * Get the apps navigation
	 *
	 * @param bool $absolute Rewrite URLs to absolute ones
	 * @return DataResponse<Http::STATUS_OK, list<CoreNavigationEntry>, array{}>|DataResponse<Http::STATUS_NOT_MODIFIED, list<empty>, array{}>
	 *
	 * 200: Apps navigation returned
	 * 304: No apps navigation changed
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/navigation/apps', root: '/core')]
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
	 * Get the settings navigation
	 *
	 * @param bool $absolute Rewrite URLs to absolute ones
	 * @return DataResponse<Http::STATUS_OK, list<CoreNavigationEntry>, array{}>|DataResponse<Http::STATUS_NOT_MODIFIED, list<empty>, array{}>
	 *
	 * 200: Apps navigation returned
	 * 304: No apps navigation changed
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/navigation/settings', root: '/core')]
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
			/* If parse_url finds no host it means the URL is not absolute */
			if (!isset(\parse_url($entry['href'])['host'])) {
				$entry['href'] = $this->urlGenerator->getAbsoluteURL($entry['href']);
			}
			if (!str_starts_with($entry['icon'], $this->urlGenerator->getBaseUrl())) {
				$entry['icon'] = $this->urlGenerator->getAbsoluteURL($entry['icon']);
			}
		}
		return $navigation;
	}
}
