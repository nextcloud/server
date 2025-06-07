<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\Service\ExampleEventService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ExampleContentController extends ApiController {
	private IAppData $appData;

	public function __construct(
		IRequest $request,
		private IConfig $config,
		private IAppConfig $appConfig,
		private IAppDataFactory $appDataFactory,
		private LoggerInterface $logger,
		private ExampleEventService $exampleEventService,
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->appData = $this->appDataFactory->get('dav');
	}

	public function setEnableDefaultContact($allow) {
		if ($allow === 'yes' && !$this->defaultContactExists()) {
			try {
				$this->setCard();
			} catch (\Exception $e) {
				$this->logger->error('Could not create default contact', ['exception' => $e]);
				return new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
			}
		}
		$this->config->setAppValue(Application::APP_ID, 'enableDefaultContact', $allow);
		return new JSONResponse([], Http::STATUS_OK);
	}

	#[NoCSRFRequired]
	public function getDefaultContact(): DataDownloadResponse {
		$cardData = $this->getCard()
			?? file_get_contents(__DIR__ . '/../ExampleContentFiles/exampleContact.vcf');
		return new DataDownloadResponse($cardData, 'example_contact.vcf', 'text/vcard');
	}

	public function setDefaultContact(?string $contactData = null) {
		if (!$this->config->getAppValue(Application::APP_ID, 'enableDefaultContact', 'yes')) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		$this->setCard($contactData);
		return new JSONResponse([], Http::STATUS_OK);
	}

	private function getCard(): ?string {
		try {
			$folder = $this->appData->getFolder('defaultContact');
		} catch (NotFoundException $e) {
			return null;
		}

		if (!$folder->fileExists('defaultContact.vcf')) {
			return null;
		}

		return $folder->getFile('defaultContact.vcf')->getContent();
	}

	private function setCard(?string $cardData = null) {
		try {
			$folder = $this->appData->getFolder('defaultContact');
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder('defaultContact');
		}

		$isCustom = true;
		if (is_null($cardData)) {
			$cardData = file_get_contents(__DIR__ . '/../ExampleContentFiles/exampleContact.vcf');
			$isCustom = false;
		}

		if (!$cardData) {
			throw new \Exception('Could not read exampleContact.vcf');
		}

		$file = (!$folder->fileExists('defaultContact.vcf')) ? $folder->newFile('defaultContact.vcf') : $folder->getFile('defaultContact.vcf');
		$file->putContent($cardData);

		$this->appConfig->setValueBool(Application::APP_ID, 'hasCustomDefaultContact', $isCustom);
	}

	private function defaultContactExists(): bool {
		try {
			$folder = $this->appData->getFolder('defaultContact');
		} catch (NotFoundException $e) {
			return false;
		}
		return $folder->fileExists('defaultContact.vcf');
	}

	#[FrontpageRoute(verb: 'POST', url: '/api/exampleEvent/enable')]
	public function setCreateExampleEvent(bool $enable): JSONResponse {
		$this->exampleEventService->setCreateExampleEvent($enable);
		return new JsonResponse([]);
	}

	#[FrontpageRoute(verb: 'GET', url: '/api/exampleEvent/event')]
	#[NoCSRFRequired]
	public function downloadExampleEvent(): DataDownloadResponse {
		$exampleEvent = $this->exampleEventService->getExampleEvent();
		return new DataDownloadResponse(
			$exampleEvent->getIcs(),
			'example_event.ics',
			'text/calendar',
		);
	}

	#[FrontpageRoute(verb: 'POST', url: '/api/exampleEvent/event')]
	public function uploadExampleEvent(string $ics): JSONResponse {
		if (!$this->exampleEventService->shouldCreateExampleEvent()) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->exampleEventService->saveCustomExampleEvent($ics);
		return new JsonResponse([]);
	}

	#[FrontpageRoute(verb: 'DELETE', url: '/api/exampleEvent/event')]
	public function deleteExampleEvent(): JSONResponse {
		$this->exampleEventService->deleteCustomExampleEvent();
		return new JsonResponse([]);
	}

}
