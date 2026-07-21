<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Controller;

use OC\AppFramework\Http\Attributes\FederationRateLimit;
use OC\Authentication\Token\PublicKeyTokenProvider;
use OC\OCM\OCMSignatoryManager;
use OCA\CloudFederationAPI\Config;
use OCA\CloudFederationAPI\Db\OcmTokenMapMapper;
use OCA\CloudFederationAPI\ResponseDefinitions;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\Exceptions\ActionNotSupportedException;
use OCP\Federation\Exceptions\AuthenticationFailedException;
use OCP\Federation\Exceptions\BadRequestException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\Exceptions\ProviderDoesNotExistsException;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\Federation\ISignedCloudFederationProvider;
use OCP\Federation\IValidationAwareCloudFederationProvider;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\OCM\Events\OCMNotificationReceivedEvent;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\IIncomingSignedRequest;
use OCP\Server;
use OCP\Share\Exceptions\ShareNotFound;
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
		private IEventDispatcher $dispatcher,
		private readonly IAppConfig $appConfig,
		private ICloudFederationFactory $factory,
		private ICloudIdManager $cloudIdManager,
		private readonly IOCMDiscoveryService $ocmDiscoveryService,
		private ITimeFactory $timeFactory,
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
	 * @param array{name: string, options?: array<string, mixed>, webdav?: array<string, mixed>} $protocol Legacy format: ['name' => 'webdav', 'options' => ['sharedSecret' => '...', 'permissions' => '...']], new single-protocol format: ['name' => 'webdav', 'webdav' => ['uri' => '...', 'sharedSecret' => '...', 'permissions' => [...]]], or multi-protocol envelope per OCM spec: ['name' => 'multi', 'webdav' => ['sharedSecret' => '...', ...], 'webapp' => [...]]
	 * @param string $shareType 'group' or 'user' share
	 * @param string $resourceType 'file', 'calendar',...
	 *
	 * @return JSONResponse<Http::STATUS_CREATED, CloudFederationAPIAddShare, array{}>|JSONResponse<Http::STATUS_BAD_REQUEST, CloudFederationAPIValidationError, array{}>|JSONResponse<Http::STATUS_NOT_IMPLEMENTED, CloudFederationAPIError, array{}>
	 *
	 * 201: The notification was successfully received. The display name of the recipient might be returned in the body
	 * 400: Bad request due to invalid parameters, e.g. when `shareWith` is not found or required properties are missing
	 * 501: Share type or the resource type is not supported
	 *
	 * @psalm-suppress InvalidReturnType
	 * @psalm-suppress InvalidReturnStatement
	 * @psalm-suppress LessSpecificReturnStatement
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'receiveFederatedShare')]
	#[FederationRateLimit(limit: 5, period: 1200)]
	public function addShare($shareWith, $name, $description, $providerId, $owner, $ownerDisplayName, $sharedBy, $sharedByDisplayName, $protocol, $shareType, $resourceType) {
		if (!$this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, lazy: true)) {
			try {
				// if request is signed and well signed, no exceptions are thrown
				// if request is not signed and host is known for not supporting signed request, no exception are thrown
				$signedRequest = $this->ocmDiscoveryService->getIncomingSignedRequest();
				$this->confirmSignedOrigin($signedRequest, 'owner', $owner);
			} catch (IncomingRequestException $e) {
				$this->logger->warning('incoming request exception', ['exception' => $e]);
				return new JSONResponse(['message' => $e->getMessage(), 'validationErrors' => []], Http::STATUS_BAD_REQUEST);
			}
		}

		// check if all required parameters are set
		if (
			$shareWith === null
			|| $name === null
			|| $providerId === null
			|| $resourceType === null
			|| $shareType === null
			|| !is_array($protocol)
			|| !isset($protocol['name'])
		) {
			return new JSONResponse(
				[
					'message' => 'Missing arguments',
					'validationErrors' => [],
				],
				Http::STATUS_BAD_REQUEST
			);
		}

		if (!$this->protocolCarriesSharedSecret($protocol)) {
			return new JSONResponse(
				[
					'message' => 'Missing sharedSecret in protocol',
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
		$shareWithCloudId = $shareWith; // preserve full cloud ID for factory capability discovery
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

		$ownerDomain = str_contains($owner, '@') ? substr(strrchr($owner, '@'), 1) : null;
		$sharedByDomain = str_contains($sharedBy, '@') ? substr(strrchr($sharedBy, '@'), 1) : null;
		$domainsToCheck = array_unique(array_filter([$ownerDomain, $sharedByDomain]));
		if (count($domainsToCheck) !== 0) {
			$spoofChecker = new \Spoofchecker();
			foreach ($domainsToCheck as $domain) {
				// detect suspicious chars (e.g. "pаypаl" spelled with Cyrillic "а" characters)
				// see https://www.php.net/manual/en/spoofchecker.issuspicious.php
				if ($spoofChecker->isSuspicious($domain)) {
					$response = new JSONResponse(
						[
							'message' => 'Suspicious domain detected on owner or sharedBy field',
							'validationErrors' => [],
						],
						Http::STATUS_BAD_REQUEST
					);
					$response->throttle();
					return $response;
				}
			}
		}

		try {
			$provider = $this->cloudFederationProviderManager->getCloudFederationProvider($resourceType);
			// Pass the original cloud ID so the factory can discover capabilities without warning.
			// Then reset shareWith to the local username that shareReceived() needs for user lookup.
			$share = $this->factory->getCloudFederationShare($shareWithCloudId, $name, $description, $providerId, $owner, $ownerDisplayName, $sharedBy, $sharedByDisplayName, '', $shareType, $resourceType);
			$share->setShareWith($shareWith);
			$share->setProtocol($protocol);
			if ($provider instanceof IValidationAwareCloudFederationProvider) {
				$provider->validateShare($share);
			}
			$provider->shareReceived($share);
		} catch (BadRequestException $e) {
			return new JSONResponse($e->getReturnMessage(), Http::STATUS_BAD_REQUEST);
		} catch (ProviderDoesNotExistsException $e) {
			return new JSONResponse(
				['message' => $e->getMessage()],
				Http::STATUS_NOT_IMPLEMENTED
			);
		} catch (ProviderCouldNotAddShareException $e) {
			$status = $e->getCode() ?: Http::STATUS_NOT_IMPLEMENTED;
			return new JSONResponse(
				['message' => $e->getMessage()],
				$status
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
		// check if all required parameters are set
		if (
			$notificationType === null
			|| $resourceType === null
			|| $providerId === null
			|| !is_array($notification)
		) {
			return new JSONResponse(
				[
					'message' => 'Missing arguments',
					'validationErrors' => [],
				],
				Http::STATUS_BAD_REQUEST
			);
		}

		if (!$this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, lazy: true)) {
			try {
				// if request is signed and well signed, no exception are thrown
				// if request is not signed and host is known for not supporting signed request, no exception are thrown
				$signedRequest = $this->ocmDiscoveryService->getIncomingSignedRequest();
				$this->confirmNotificationIdentity($signedRequest, $resourceType, $notification);
			} catch (IncomingRequestException $e) {
				$this->logger->warning('incoming request exception', ['exception' => $e]);
				return new JSONResponse(['message' => $e->getMessage(), 'validationErrors' => []], Http::STATUS_BAD_REQUEST);
			}
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
			$this->logger->warning('incoming notification exception', ['exception' => $e]);
			return new JSONResponse(
				[
					'message' => 'Internal error at ' . $this->urlGenerator->getBaseUrl(),
					'validationErrors' => [],
				],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$notificationObject = $this->factory->getCloudFederationNotification();
			$notificationObject->setMessage($notificationType, $resourceType, $providerId, $notification);
			$notificationEvent = new OCMNotificationReceivedEvent($notificationObject);
			$this->dispatcher->dispatchTyped($notificationEvent);
		} catch (\Exception $e) {
			$this->logger->warning('error while dispatching OCM notification received event', ['exception' => $e]);
		}

		return new JSONResponse($result, Http::STATUS_CREATED);
	}

	/**
	 * Check that the protocol envelope carries at least one sharedSecret.
	 *
	 * Accepts the legacy single-protocol shape ['name' => '<type>', 'options' => ['sharedSecret' => ...]],
	 * the new single-protocol shape ['name' => '<type>', '<type>' => ['sharedSecret' => ...]] and the
	 * multi-protocol envelope from the OCM spec ['name' => 'multi', '<type>' => ['sharedSecret' => ...], ...].
	 * Because a payload may use 'name' => 'multi' even when it carries a single inner protocol, we do not
	 * gate on the name value but scan every sibling entry for the first sharedSecret. The full envelope is
	 * forwarded to the resource provider unchanged; the provider decides which entries it understands.
	 *
	 * @param array<string, mixed> $protocol
	 * @see https://github.com/cs3org/OCM-API/
	 */
	private function protocolCarriesSharedSecret(array $protocol): bool {
		if (
			isset($protocol['options'])
			&& is_array($protocol['options'])
			&& isset($protocol['options']['sharedSecret'])
			&& is_string($protocol['options']['sharedSecret'])
		) {
			return true;
		}
		foreach ($protocol as $key => $value) {
			if ($key === 'name' || $key === 'options' || !is_array($value)) {
				continue;
			}
			if (isset($value['sharedSecret']) && is_string($value['sharedSecret'])) {
				return true;
			}
		}
		return false;
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
	 * confirm that the value related to $key entry from the payload is in format userid@hostname
	 * and compare hostname with the origin of the signed request.
	 *
	 * If request is not signed, we still verify that the hostname from the extracted value does,
	 * actually, not support signed request
	 *
	 * Delegates to {@see IOCMDiscoveryService::confirmRequestOrigin()}.
	 *
	 * @param IIncomingSignedRequest|null $signedRequest
	 * @param string $key entry from data available in data
	 * @param string $value value itself used in case request is not signed
	 *
	 * @throws IncomingRequestException
	 */
	private function confirmSignedOrigin(?IIncomingSignedRequest $signedRequest, string $key, string $value): void {
		if ($signedRequest !== null) {
			$body = json_decode($signedRequest->getBody(), true) ?? [];
			$entry = trim(($body[$key] ?? ''), '@');
		} else {
			$entry = trim($value, '@');
		}
		$this->ocmDiscoveryService->confirmRequestOrigin($signedRequest?->getOrigin(), $entry);
	}

	/**
	 *  confirm identity of the remote instance on notification, based on the share token.
	 *
	 *  If request is not signed, we still verify that the hostname from the extracted value does,
	 *  actually, not support signed request
	 *
	 * @param IIncomingSignedRequest|null $signedRequest
	 * @param string $resourceType
	 *
	 * @throws IncomingRequestException
	 * @throws BadRequestException
	 */
	private function confirmNotificationIdentity(
		?IIncomingSignedRequest $signedRequest,
		string $resourceType,
		array $notification,
	): void {
		$sharedSecret = $notification['sharedSecret'] ?? '';
		if ($sharedSecret === '') {
			throw new BadRequestException(['sharedSecret']);
		}

		try {
			$provider = $this->cloudFederationProviderManager->getCloudFederationProvider($resourceType);
			if ($provider instanceof ISignedCloudFederationProvider || $provider instanceof \NCU\Federation\ISignedCloudFederationProvider) {
				$identity = $provider->getFederationIdFromSharedSecret($sharedSecret, $notification);
				if ($identity === '') {
					$tokenProvider = Server::get(PublicKeyTokenProvider::class);
					$accessTokenDb = $tokenProvider->getToken($sharedSecret);
					$mapping = Server::get(OcmTokenMapMapper::class)->getByAccessTokenId($accessTokenDb->getId());
					$identity = $provider->getFederationIdFromSharedSecret($mapping->getRefreshToken(), $notification);
				}
			} else {
				$this->logger->debug('cloud federation provider {provider} does not implements ISignedCloudFederationProvider', ['provider' => $provider::class]);
				return;
			}
		} catch (\Exception $e) {
			throw new IncomingRequestException($e->getMessage(), previous: $e);
		}

		$this->ocmDiscoveryService->confirmRequestOrigin($signedRequest?->getOrigin(), $identity);
	}
}
