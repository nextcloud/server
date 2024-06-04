<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Files\AppData\Factory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class CssController extends Controller {
	protected IAppData $appData;

	public function __construct(
		string $appName,
		IRequest $request,
		Factory $appDataFactory,
		protected ITimeFactory $timeFactory,
	) {
		parent::__construct($appName, $request);

		$this->appData = $appDataFactory->get('css');
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @NoSameSiteCookieRequired
	 *
	 * @param string $fileName css filename with extension
	 * @param string $appName css folder name
	 * @return FileDisplayResponse|NotFoundResponse
	 */
	#[FrontpageRoute(verb: 'GET', url: '/css/{appName}/{fileName}')]
	public function getCss(string $fileName, string $appName): Response {
		try {
			$folder = $this->appData->getFolder($appName);
			$gzip = false;
			$file = $this->getFile($folder, $fileName, $gzip);
		} catch (NotFoundException $e) {
			return new NotFoundResponse();
		}

		$response = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'text/css']);
		if ($gzip) {
			$response->addHeader('Content-Encoding', 'gzip');
		}

		$ttl = 31536000;
		$response->addHeader('Cache-Control', 'max-age='.$ttl.', immutable');

		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT'.$ttl.'S'));
		$response->addHeader('Expires', $expires->format(\DateTime::RFC1123));
		return $response;
	}

	/**
	 * @param ISimpleFolder $folder
	 * @param string $fileName
	 * @param bool $gzip is set to true if we use the gzip file
	 * @return ISimpleFile
	 * @throws NotFoundException
	 */
	private function getFile(ISimpleFolder $folder, string $fileName, bool &$gzip): ISimpleFile {
		$encoding = $this->request->getHeader('Accept-Encoding');

		if (str_contains($encoding, 'gzip')) {
			try {
				$gzip = true;
				return $folder->getFile($fileName . '.gzip'); # Safari doesn't like .gz
			} catch (NotFoundException $e) {
				// continue
			}
		}

		$gzip = false;
		return $folder->getFile($fileName);
	}
}
