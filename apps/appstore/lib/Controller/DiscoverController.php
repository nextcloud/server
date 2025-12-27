<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Appstore\Controller;

use OC\App\AppStore\Fetcher\AppDiscoverFetcher;
use OC\App\AppStore\Fetcher\ResponseDefinitions;
use OCA\Appstore\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Security\RateLimiting\ILimiter;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type AppStoreFetcherDiscoverElement from ResponseDefinitions
 */
#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class DiscoverController extends OCSController {

	private IAppData $appData;

	public function __construct(
		IRequest $request,
		IAppDataFactory $appDataFactory,
		private IClientService $clientService,
		private AppDiscoverFetcher $discoverFetcher,
		private LoggerInterface $logger,
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->appData = $appDataFactory->get(Application::APP_ID);
	}

	/**
	 * Get all active entries for the app discover section
	 *
	 * @return DataResponse<Http::STATUS_OK, list<AppStoreFetcherDiscoverElement>, array{}>
	 *
	 * 200: List of active entries for the app discover section
	 */
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url:'/api/v1/discover')]
	public function getAppDiscoverJSON(): DataResponse {
		$data = $this->discoverFetcher->get(true);
		return new DataResponse($data);
	}

	/**
	 * Get a image for the app discover section - this is proxied for privacy and CSP reasons
	 *
	 * @param string $fileName - The image file name
	 * @return FileDisplayResponse<Http::STATUS_OK, array{'Content-Type': string}>
	 * @throws OCSBadRequestException - if the media source is not trusted
	 * @throws OCSNotFoundException - if the media file could not be found
	 *
	 * 200: The media file was found and is returned
	 * 400: The media source is not trusted
	 * 404: The media file could not be found
	 */
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/discover/media')]
	public function getAppDiscoverMedia(string $fileName, ILimiter $limiter, IUserSession $session): FileDisplayResponse {
		$getEtag = $this->discoverFetcher->getETag() ?? date('Y-m');
		$etag = trim($getEtag, '"');

		$folder = null;
		try {
			$folder = $this->appData->getFolder('app-discover-cache');
			$this->cleanUpImageCache($folder, $etag);
		} catch (\Throwable $e) {
			$folder = $this->appData->newFolder('app-discover-cache');
		}

		// Get the current cache folder
		try {
			$folder = $folder->getFolder($etag);
		} catch (NotFoundException $e) {
			$folder = $folder->newFolder($etag);
		}

		$info = pathinfo($fileName);
		$hashName = md5($fileName);
		$allFiles = $folder->getDirectoryListing();
		// Try to find the file
		$file = array_filter($allFiles, function (ISimpleFile $file) use ($hashName) {
			return str_starts_with($file->getName(), $hashName);
		});
		// Get the first entry
		$file = reset($file);
		// If not found request from Web
		if ($file === false) {
			$user = $session->getUser();
			// this route is not public thus we can assume a user is logged-in
			assert($user !== null);
			// Register a user request to throttle fetching external data
			// this will prevent using the server for DoS of other systems.
			$limiter->registerUserRequest(
				'settings-discover-media',
				// allow up to 24 media requests per hour
				// this should be a sane default when a completely new section is loaded
				// keep in mind browsers request all files from a source-set
				24,
				60 * 60,
				$user,
			);

			if (!$this->checkCanDownloadMedia($fileName)) {
				$this->logger->warning('Tried to load media files for app discover section from untrusted source');
				throw new OCSBadRequestException('Untrusted media source');
			}

			try {
				$client = $this->clientService->newClient();
				$fileResponse = $client->get($fileName);
				$contentType = $fileResponse->getHeader('Content-Type');
				$extension = $info['extension'] ?? '';
				$file = $folder->newFile($hashName . '.' . base64_encode($contentType) . '.' . $extension, $fileResponse->getBody());
			} catch (\Throwable $e) {
				$this->logger->warning('Could not load media file for app discover section', ['media_src' => $fileName, 'exception' => $e]);
				throw new OCSNotFoundException('Media file not found');
			}
		} else {
			// File was found so we can get the content type from the file name
			$contentType = base64_decode(explode('.', $file->getName())[1] ?? '');
		}

		$response = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => $contentType]);
		// cache for 7 days
		$response->cacheFor(604800, false, true);
		return $response;
	}

	private function checkCanDownloadMedia(string $filename): bool {
		$urlInfo = parse_url($filename);
		if (!isset($urlInfo['host']) || !isset($urlInfo['path'])) {
			return false;
		}

		// Always allowed hosts
		if ($urlInfo['host'] === 'nextcloud.com') {
			return true;
		}

		// Hosts that need further verification
		// Github is only allowed if from our organization
		$ALLOWED_HOSTS = ['github.com', 'raw.githubusercontent.com'];
		if (!in_array($urlInfo['host'], $ALLOWED_HOSTS)) {
			return false;
		}

		if (str_starts_with($urlInfo['path'], '/nextcloud/') || str_starts_with($urlInfo['path'], '/nextcloud-gmbh/')) {
			return true;
		}

		return false;
	}

	/**
	 * Remove orphaned folders from the image cache that do not match the current etag
	 * @param ISimpleFolder $folder The folder to clear
	 * @param string $etag The etag (directory name) to keep
	 */
	private function cleanUpImageCache(ISimpleFolder $folder, string $etag): void {
		// Cleanup old cache folders
		$allFiles = $folder->getDirectoryListing();
		foreach ($allFiles as $dir) {
			try {
				if ($dir->getName() !== $etag) {
					$dir->delete();
				}
			} catch (NotPermittedException $e) {
				// ignore folder for now
			}
		}
	}
}
