<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\CloudFederationAPI\Controller;

use NCU\Security\Signature\Exceptions\IncomingRequestException;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureException;
use NCU\Security\Signature\Exceptions\SignatureNotFoundException;
use NCU\Security\Signature\ISignatureManager;
use NCU\Security\Signature\Model\IIncomingSignedRequest;
use OC\OCM\OCMSignatoryManager;
use OCA\CloudFederationAPI\Config;
use OCA\CloudFederationAPI\ResponseDefinitions;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Federation\Exceptions\ActionNotSupportedException;
use OCP\Federation\Exceptions\AuthenticationFailedException;
use OCP\Federation\Exceptions\BadRequestException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\Exceptions\ProviderDoesNotExistsException;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * Open-Cloud-Mesh-API
 *
 * @package OCA\CloudFederationAPI\Controller
 *
 * @psalm-import-type CloudFederationAPIAddShare from ResponseDefinitions
 * @psalm-import-type CloudFederationAPIValidationError from ResponseDefinitions
 * @psalm-import-type CloudFederationAPIError from ResponseDefinitions
 */
#[OpenAPI(scope: OpenAPI::SCOPE_FEDERATION)]
class RequestHandlerController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private LoggerInterface $logger,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private IURLGenerator $urlGenerator,
		private ICloudFederationProviderManager $cloudFederationProviderManager,
		private Config $config,
		private readonly IAppConfig $appConfig,
		private ICloudFederationFactory $factory,
		private ICloudIdManager $cloudIdManager,
		private readonly ISignatureManager $signatureManager,
		private readonly OCMSignatoryManager $signatoryManager,
		private readonly IProviderFactory $shareProviderFactory,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Add share
	 *
	 * @param string $shareWith The user who the share will be shared with
	 * @param string $name The resource name (e.g. document.odt)
	 * @param string|null $description Share description
	 * @param string $providerId Resource UID on the provider side
	 * @param string $owner Provider specific UID of the user who owns the resource
	 * @param string|null $ownerDisplayName Display name of the user who shared the item
	 * @param string|null $sharedBy Provider specific UID of the user who shared the resource
	 * @param string|null $sharedByDisplayName Display name of the user who shared the resource
	 * @param array{name: list<string>, options: array<string, mixed>} $protocol e,.g. ['name' => 'webdav', 'options' => ['username' => 'john', 'permissions' => 31]]
	 * @param string $shareType 'group' or 'user' share
	 * @param string $resourceType 'file', 'calendar',...
	 *
	 * @return JSONResponse<Http::STATUS_CREATED, CloudFederationAPIAddShare, array{}>|JSONResponse<Http::STATUS_BAD_REQUEST, CloudFederationAPIValidationError, array{}>|JSONResponse<Http::STATUS_NOT_IMPLEMENTED, CloudFederationAPIError, array{}>
	 *
	 * 201: The notification was successfully received. The display name of the recipient might be returned in the body
	 * 400: Bad request due to invalid parameters, e.g. when `shareWith` is not found or required properties are missing
	 * 501: Share type or the resource type is not supported
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'receiveFederatedShare')]
	public function addShare($shareWith, $name, $description, $providerId, $owner, $ownerDisplayName, $sharedBy, $sharedByDisplayName, $protocol, $shareType, $resourceType) {
		try {
			// if request is signed and well signed, no exception are thrown
			// if request is not signed and host is known for not supporting signed request, no exception are thrown
			$signedRequest = $this->getSignedRequest();
			$this->confirmSignedOrigin($signedRequest, 'owner', $owner);
		} catch (IncomingRequestException $e) {
			$this->logger->warning('incoming request exception', ['exception' => $e]);
			return new JSONResponse(['message' => $e->getMessage(), 'validationErrors' => []], Http::STATUS_BAD_REQUEST);
		}

		// check if all required parameters are set
		if ($shareWith === null ||
			$name === null ||
			$providerId === null ||
			$resourceType === null ||
			$shareType === null ||
			!is_array($protocol) ||
			!isset($protocol['name']) ||
			!isset($protocol['options']) ||
			!is_array($protocol['options']) ||
			!isset($protocol['options']['sharedSecret'])
		) {
			return new JSONResponse(
				[
					'message' => 'Missing arguments',
					'validationErrors' => [],
				],
				Http::STATUS_BAD_REQUEST
			);
		}

		$supportedShareTypes = $this->config->getSupportedShareTypes($resourceType);
		if (!in_array($shareType, $supportedShareTypes)) {
			return new JSONResponse(
				['message' => 'Share type "' . $shareType . '" not implemented'],
				Http::STATUS_NOT_IMPLEMENTED
			);
		}

		$cloudId = $this->cloudIdManager->resolveCloudId($shareWith);
		$shareWith = $cloudId->getUser();

		if ($shareType === 'user') {
			$shareWith = $this->mapUid($shareWith);

			if (!$this->userManager->userExists($shareWith)) {
				$response = new JSONResponse(
					[
						'message' => 'User "' . $shareWith . '" does not exists at ' . $this->urlGenerator->getBaseUrl(),
						'validationErrors' => [],
					],
					Http::STATUS_BAD_REQUEST
				);
				$response->throttle();
				return $response;
			}
		}

		if ($shareType === 'group') {
			if (!$this->groupManager->groupExists($shareWith)) {
				$response = new JSONResponse(
					[
						'message' => 'Group "' . $shareWith . '" does not exists at ' . $this->urlGenerator->getBaseUrl(),
						'validationErrors' => [],
					],
					Http::STATUS_BAD_REQUEST
				);
				$response->throttle();
				return $response;
			}
		}

		// if no explicit display name is given, we use the uid as display name
		$ownerDisplayName = $ownerDisplayName === null ? $owner : $ownerDisplayName;
		$sharedByDisplayName = $sharedByDisplayName === null ? $sharedBy : $sharedByDisplayName;

		// sharedBy* parameter is optional, if nothing is set we assume that it is the same user as the owner
		if ($sharedBy === null) {
			$sharedBy = $owner;
			$sharedByDisplayName = $ownerDisplayName;
		}

		try {
			$provider = $this->cloudFederationProviderManager->getCloudFederationProvider($resourceType);
			$share = $this->factory->getCloudFederationShare($shareWith, $name, $description, $providerId, $owner, $ownerDisplayName, $sharedBy, $sharedByDisplayName, '', $shareType, $resourceType);
			$share->setProtocol($protocol);
			$provider->shareReceived($share);
		} catch (ProviderDoesNotExistsException|ProviderCouldNotAddShareException $e) {
			return new JSONResponse(
				['message' => $e->getMessage()],
				Http::STATUS_NOT_IMPLEMENTED
			);
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return new JSONResponse(
				[
					'message' => 'Internal error at ' . $this->urlGenerator->getBaseUrl(),
					'validationErrors' => [],
				],
				Http::STATUS_BAD_REQUEST
			);
		}

		$responseData = ['recipientDisplayName' => ''];
		if ($shareType === 'user') {
			$user = $this->userManager->get($shareWith);
			if ($user) {
				$responseData = [
					'recipientDisplayName' => $user->getDisplayName(),
					'recipientUserId' => $user->getUID(),
				];
			}
		}

		return new JSONResponse($responseData, Http::STATUS_CREATED);
	}

	/**
	 * Send a notification about an existing share
	 *
	 * @param string $notificationType Notification type, e.g. SHARE_ACCEPTED
	 * @param string $resourceType calendar, file, contact,...
	 * @param string|null $providerId ID of the share
	 * @param array<string, mixed>|null $notification The actual payload of the notification
	 *
	 * @return JSONResponse<Http::STATUS_CREATED, array<string, mixed>, array{}>|JSONResponse<Http::STATUS_BAD_REQUEST, CloudFederationAPIValidationError, array{}>|JSONResponse<Http::STATUS_FORBIDDEN|Http::STATUS_NOT_IMPLEMENTED, CloudFederationAPIError, array{}>
	 *
	 * 201: The notification was successfully received
	 * 400: Bad request due to invalid parameters, e.g. when `type` is invalid or missing
	 * 403: Getting resource is not allowed
	 * 501: The resource type is not supported
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[BruteForceProtection(action: 'receiveFederatedShareNotification')]
	public function receiveNotification($notificationType, $resourceType, $providerId, ?array $notification) {
		try {
			// if request is signed and well signed, no exception are thrown
			// if request is not signed and host is known for not supporting signed request, no exception are thrown
			$signedRequest = $this->getSignedRequest();
			$this->confirmShareOrigin($signedRequest, $notification['sharedSecret'] ?? '');
		} catch (IncomingRequestException $e) {
			$this->logger->warning('incoming request exception', ['exception' => $e]);
			return new JSONResponse(['message' => $e->getMessage(), 'validationErrors' => []], Http::STATUS_BAD_REQUEST);
		}

		// check if all required parameters are set
		if ($notificationType === null ||
			$resourceType === null ||
			$providerId === null ||
			!is_array($notification)
		) {
			return new JSONResponse(
				[
					'message' => 'Missing arguments',
					'validationErrors' => [],
				],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$provider = $this->cloudFederationProviderManager->getCloudFederationProvider($resourceType);
			$result = $provider->notificationReceived($notificationType, $providerId, $notification);
		} catch (ProviderDoesNotExistsException $e) {
			return new JSONResponse(
				[
					'message' => $e->getMessage(),
					'validationErrors' => [],
				],
				Http::STATUS_BAD_REQUEST
			);
		} catch (ShareNotFound $e) {
			$response = new JSONResponse(
				[
					'message' => $e->getMessage(),
					'validationErrors' => [],
				],
				Http::STATUS_BAD_REQUEST
			);
			$response->throttle();
			return $response;
		} catch (ActionNotSupportedException $e) {
			return new JSONResponse(
				['message' => $e->getMessage()],
				Http::STATUS_NOT_IMPLEMENTED
			);
		} catch (BadRequestException $e) {
			return new JSONResponse($e->getReturnMessage(), Http::STATUS_BAD_REQUEST);
		} catch (AuthenticationFailedException $e) {
			$response = new JSONResponse(['message' => 'RESOURCE_NOT_FOUND'], Http::STATUS_FORBIDDEN);
			$response->throttle();
			return $response;
		} catch (\Exception $e) {
			return new JSONResponse(
				[
					'message' => 'Internal error at ' . $this->urlGenerator->getBaseUrl(),
					'validationErrors' => [],
				],
				Http::STATUS_BAD_REQUEST
			);
		}

		return new JSONResponse($result, Http::STATUS_CREATED);
	}

	/**
	 * map login name to internal LDAP UID if a LDAP backend is in use
	 *
	 * @param string $uid
	 * @return string mixed
	 */
	private function mapUid($uid) {
		// FIXME this should be a method in the user management instead
		$this->logger->debug('shareWith before, ' . $uid, ['app' => $this->appName]);
		Util::emitHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			['uid' => &$uid]
		);
		$this->logger->debug('shareWith after, ' . $uid, ['app' => $this->appName]);

		return $uid;
	}


	/**
	 * returns signed request if available.
	 * throw an exception:
	 * - if request is signed, but wrongly signed
	 * - if request is not signed but instance is configured to only accept signed ocm request
	 *
	 * @return IIncomingSignedRequest|null null if remote does not (and never did) support signed request
	 * @throws IncomingRequestException
	 */
	private function getSignedRequest(): ?IIncomingSignedRequest {
		try {
			return $this->signatureManager->getIncomingSignedRequest($this->signatoryManager);
		} catch (SignatureNotFoundException|SignatoryNotFoundException $e) {
			// remote does not support signed request.
			// currently we still accept unsigned request until lazy appconfig
			// core.enforce_signed_ocm_request is set to true (default: false)
			if ($this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, lazy: true)) {
				$this->logger->notice('ignored unsigned request', ['exception' => $e]);
				throw new IncomingRequestException('Unsigned request');
			}
		} catch (SignatureException $e) {
			$this->logger->notice('wrongly signed request', ['exception' => $e]);
			throw new IncomingRequestException('Invalid signature');
		}
		return null;
	}


	/**
	 * confirm that the value related to $key entry from the payload is in format userid@hostname
	 * and compare hostname with the origin of the signed request.
	 *
	 * If request is not signed, we still verify that the hostname from the extracted value does,
	 * actually, not support signed request
	 *
	 * @param IIncomingSignedRequest|null $signedRequest
	 * @param string $key entry from data available in data
	 * @param string $value value itself used in case request is not signed
	 *
	 * @throws IncomingRequestException
	 */
	private function confirmSignedOrigin(?IIncomingSignedRequest $signedRequest, string $key, string $value): void {
		if ($signedRequest === null) {
			$instance = $this->getHostFromFederationId($value);
			try {
				$this->signatureManager->searchSignatory($instance);
				throw new IncomingRequestException('instance is supposed to sign its request');
			} catch (SignatoryNotFoundException) {
				return;
			}
		}

		$body = json_decode($signedRequest->getBody(), true) ?? [];
		$entry = trim($body[$key] ?? '', '@');
		if ($this->getHostFromFederationId($entry) !== $signedRequest->getOrigin()) {
			throw new IncomingRequestException('share initiation from different instance');
		}
	}


	/**
	 *  confirm that the value related to share token is in format userid@hostname
	 *  and compare hostname with the origin of the signed request.
	 *
	 *  If request is not signed, we still verify that the hostname from the extracted value does,
	 *  actually, not support signed request
	 *
	 * @param IIncomingSignedRequest|null $signedRequest
	 * @param string $token
	 *
	 * @return void
	 * @throws IncomingRequestException
	 */
	private function confirmShareOrigin(?IIncomingSignedRequest $signedRequest, string $token): void {
		if ($token === '') {
			throw new BadRequestException(['sharedSecret']);
		}

		$provider = $this->shareProviderFactory->getProviderForType(IShare::TYPE_REMOTE);
		$share = $provider->getShareByToken($token);
		$entry = $share->getSharedWith();

		$instance = $this->getHostFromFederationId($entry);
		if ($signedRequest === null) {
			try {
				$this->signatureManager->searchSignatory($instance);
				throw new IncomingRequestException('instance is supposed to sign its request');
			} catch (SignatoryNotFoundException) {
				return;
			}
		} elseif ($instance !== $signedRequest->getOrigin()) {
			throw new IncomingRequestException('token sharedWith from different instance');
		}
	}

	/**
	 * @param string $entry
	 * @return string
	 * @throws IncomingRequestException
	 */
	private function getHostFromFederationId(string $entry): string {
		if (!str_contains($entry, '@')) {
			throw new IncomingRequestException('entry does not contains @');
		}
		[, $rightPart] = explode('@', $entry, 2);

		$host = parse_url($rightPart, PHP_URL_HOST);
		$port = parse_url($rightPart, PHP_URL_PORT);
		if ($port !== null && $port !== false) {
			$host .= ':' . $port;
		}

		if (is_string($host) && $host !== '') {
			return $host;
		}

		throw new IncomingRequestException('host is empty');
	}
}
