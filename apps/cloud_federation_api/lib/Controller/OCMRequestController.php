<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\IRequest;
use OCP\OCM\Events\OCMEndpointRequestEvent;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use Psr\Log\LoggerInterface;

class OCMRequestController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly IOCMDiscoveryService $ocmDiscoveryService,
		private readonly ICloudFederationProviderManager $cloudFederationProviderManager,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Method will catch any request done to /ocm/[...] and will broadcast an event.
	 * The first parameter of the remaining subpath (post-/ocm/) is defined as
	 * capability and should be used by listeners to filter incoming requests.
	 *
	 * @see OCMEndpointRequestEvent
	 * @see OCMEndpointRequestEvent::getArgs
	 *
	 * @param string $ocmPath
	 * @return Response
	 * @throws OCMArgumentException
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[BruteForceProtection(action: 'receiveOcmRequest')]
	public function manageOCMRequests(string $ocmPath): Response {
		if (!mb_check_encoding($ocmPath, 'UTF-8')) {
			throw new OCMArgumentException('path is not UTF-8');
		}

		// Resolve the signer origin from the payload before verification.
		$payload = $this->request->getParams();
		$origin = null;
		if ($payload !== []) {
			$identity = $this->cloudFederationProviderManager->resolveSenderIdentity($payload);
			if ($identity !== null) {
				try {
					$origin = $this->ocmDiscoveryService->getHostFromOcmAddress($identity);
				} catch (IncomingRequestException) {
					// unresolvable origin; verification will fail without one
				}
			}
		}

		try {
			$signedRequest = $this->ocmDiscoveryService->getIncomingSignedRequest($origin);
		} catch (IncomingRequestException $e) {
			$this->logger->warning('incoming ocm request exception', ['exception' => $e]);
			$response = new JSONResponse(['message' => $e->getMessage(), 'validationErrors' => []], Http::STATUS_BAD_REQUEST);
			$response->throttle();
			return $response;
		}

		$event = new OCMEndpointRequestEvent(
			$this->request->getMethod(),
			preg_replace('@/+@', '/', $ocmPath),
			$payload,
			$signedRequest?->getOrigin()
		);
		$this->eventDispatcher->dispatchTyped($event);

		return $event->getResponse() ?? new Response(Http::STATUS_NOT_FOUND);
	}
}
