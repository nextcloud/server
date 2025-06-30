<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Controller;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\Service\ExampleContactService;
use OCA\DAV\Service\ExampleEventService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ExampleContentController extends ApiController {
	public function __construct(
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly ExampleEventService $exampleEventService,
		private readonly ExampleContactService $exampleContactService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[FrontpageRoute(verb: 'PUT', url: '/api/defaultcontact/config')]
	public function setEnableDefaultContact(bool $allow): JSONResponse {
		if ($allow && !$this->exampleContactService->defaultContactExists()) {
			try {
				$this->exampleContactService->setCard();
			} catch (\Exception $e) {
				$this->logger->error('Could not create default contact', ['exception' => $e]);
				return new JSONResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
			}
		}
		$this->exampleContactService->setDefaultContactEnabled($allow);
		return new JSONResponse([], Http::STATUS_OK);
	}

	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/defaultcontact/contact')]
	public function getDefaultContact(): DataDownloadResponse {
		$cardData = $this->exampleContactService->getCard()
			?? file_get_contents(__DIR__ . '/../ExampleContentFiles/exampleContact.vcf');
		return new DataDownloadResponse($cardData, 'example_contact.vcf', 'text/vcard');
	}

	#[FrontpageRoute(verb: 'PUT', url: '/api/defaultcontact/contact')]
	public function setDefaultContact(?string $contactData = null) {
		if (!$this->exampleContactService->isDefaultContactEnabled()) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		$this->exampleContactService->setCard($contactData);
		return new JSONResponse([], Http::STATUS_OK);
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
