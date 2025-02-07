<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use OCA\DAV\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;

class ExampleContentController extends ApiController {
	private IAppData $appData;
	public function __construct(
		IRequest $request,
		private IConfig $config,
		private IAppDataFactory $appDataFactory,
		private IAppManager $appManager,
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->appData = $this->appDataFactory->get('dav');
	}

	public function setEnableDefaultContact($allow) {
		if ($allow === 'yes' && !$this->defaultContactExists()) {
			$this->setCard();
		}
		$this->config->setAppValue(Application::APP_ID, 'enableDefaultContact', $allow);
		return new JSONResponse([], Http::STATUS_OK);
	}

	public function setDefaultContact(?string $contactData = null) {
		if (!$this->config->getAppValue(Application::APP_ID, 'enableDefaultContact', 'no')) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		$this->setCard($contactData);
		return new JSONResponse([], Http::STATUS_OK);
	}

	private function setCard(?string $cardData = null) {
		try {
			$folder = $this->appData->getFolder('defaultContact');
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder('defaultContact');
		}

		if (is_null($cardData)) {
			$cardData = file_get_contents(__DIR__ . '/../ExampleContentFiles/exampleContact.vcf');
		}

		$file = (!$folder->fileExists('defaultContact.vcf')) ? $folder->newFile('defaultContact.vcf') : $folder->getFile('defaultContact.vcf');
		$file->putContent($cardData);
	}

	private function defaultContactExists(): bool {
		try {
			$folder = $this->appData->getFolder('defaultContact');
		} catch (NotFoundException $e) {
			return false;
		}
		return $folder->fileExists('defaultContact.vcf');
	}

}
