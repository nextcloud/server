<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Controller;

use NCU\Federation\ISignedCloudFederationProvider;
use NCU\Security\Signature\Exceptions\IdentityNotFoundException;
use NCU\Security\Signature\Exceptions\IncomingRequestException;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureException;
use NCU\Security\Signature\Exceptions\SignatureNotFoundException;
use NCU\Security\Signature\IIncomingSignedRequest;
use NCU\Security\Signature\ISignatureManager;
use OC\OCM\OCMSignatoryManager;
use OCA\CloudFederationAPI\Config;
use OCA\CloudFederationAPI\Db\FederatedInviteMapper;
use OCA\CloudFederationAPI\Events\FederatedInviteAcceptedEvent;
use OCA\CloudFederationAPI\ResponseDefinitions;
use OCA\FederatedFileSharing\AddressHandler;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
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
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
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
		private FederatedInviteMapper $federatedInviteMapper,
		private readonly AddressHandler $addressHandler,
		private readonly IAppConfig $appConfig,
		private ICloudFederationFactory $factory,
		private ICloudIdManager $cloudIdManager,
		private readonly ISignatureManager $signatureManager,
		private readonly OCMSignatoryManager $signatoryManager,
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
		if (
			$shareWith === null ||
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
	 * Inform the sender that an invitation was accepted to start sharing
	 *
	 * Inform about an accepted invitation so the user on the sender provider's side
	 * can initiate the OCM share creation. To protect the identity of the parties,
	 * for shares created following an OCM invitation, the user id MAY be hashed,
	 * and recipients implementing the OCM invitation workflow MAY refuse to process
	 * shares coming from unknown parties.
	 * @link https://cs3org.github.io/OCM-API/docs.html?branch=v1.1.0&repo=OCM-API&user=cs3org#/paths/~1invite-accepted/post
	 *
	 * @param string $recipientProvider The address of the recipent's provider
	 * @param string $token The token used for the invitation
	 * @param string $userId The userId of the recipient at the recipient's provider
	 * @param string $email The email address of the recipient
	 * @param string $name The display name of the recipient
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{userID: string, email: string, name: string}, array{}>|JSONResponse<Http::STATUS_FORBIDDEN|Http::STATUS_BAD_REQUEST|Http::STATUS_CONFLICT, array{message: string, error: true}, array{}>
	 *
	 * Note: Not implementing 404 Invitation token does not exist, instead using 400
	 * 200: Invitation accepted
	 * 400: Invalid token
	 * 403: Invitation token does not exist
	 * 409: User is already known by the OCM provider
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'inviteAccepted')]
	public function inviteAccepted(string $recipientProvider, string $token, string $userId, string $email, string $name): JSONResponse {
		$this->logger->debug('Processing share invitation for ' . $userId . ' with token ' . $token . ' and email ' . $email . ' and name ' . $name);

		$updated = $this->timeFactory->getTime();

		if ($token === '') {
			$response = new JSONResponse(['message' => 'Invalid or non existing token', 'error' => true], Http::STATUS_BAD_REQUEST);
			$response->throttle();
			return $response;
		}

		try {
			$invitation = $this->federatedInviteMapper->findByToken($token);
		} catch (DoesNotExistException) {
			$response = ['message' => 'Invalid or non existing token', 'error' => true];
			$status = Http::STATUS_BAD_REQUEST;
			$response = new JSONResponse($response, $status);
			$response->throttle();
			return $response;
		}

		if ($invitation->isAccepted() === true) {
			$response = ['message' => 'Invite already accepted', 'error' => true];
			$status = Http::STATUS_CONFLICT;
			return new JSONResponse($response, $status);
		}

		if ($invitation->getExpiredAt() !== null && $updated > $invitation->getExpiredAt()) {
			$response = ['message' => 'Invitation expired', 'error' => true];
			$status = Http::STATUS_BAD_REQUEST;
			return new JSONResponse($response, $status);
		}
		$localUser = $this->userManager->get($invitation->getUserId());
		if ($localUser === null) {
			$response = ['message' => 'Invalid or non existing token', 'error' => true];
			$status = Http::STATUS_BAD_REQUEST;
			$response = new JSONResponse($response, $status);
			$response->throttle();
			return $response;
		}

		$sharedFromEmail = $localUser->getEMailAddress();
		if ($sharedFromEmail === null) {
			$response = ['message' => 'Invalid or non existing token', 'error' => true];
			$status = Http::STATUS_BAD_REQUEST;
			$response = new JSONResponse($response, $status);
			$response->throttle();
			return $response;
		}
		$sharedFromDisplayName = $localUser->getDisplayName();

		$response = ['userID' => $localUser->getUID(), 'email' => $sharedFromEmail, 'name' => $sharedFromDisplayName];
		$status = Http::STATUS_OK;

		$invitation->setAccepted(true);
		$invitation->setRecipientEmail($email);
		$invitation->setRecipientName($name);
		$invitation->setRecipientProvider($recipientProvider);
		$invitation->setRecipientUserId($userId);
		$invitation->setAcceptedAt($updated);
		$invitation = $this->federatedInviteMapper->update($invitation);

		$event = new FederatedInviteAcceptedEvent($invitation);
		$this->dispatcher->dispatchTyped($event);

		return new JSONResponse($response, $status);
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
			$notificationType === null ||
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
			// if request is signed and well signed, no exception are thrown
			// if request is not signed and host is known for not supporting signed request, no exception are thrown
			$signedRequest = $this->getSignedRequest();
			$this->confirmNotificationIdentity($signedRequest, $resourceType, $notification);
		} catch (IncomingRequestException $e) {
			$this->logger->warning('incoming request exception', ['exception' => $e]);
			return new JSONResponse(['message' => $e->getMessage(), 'validationErrors' => []], Http::STATUS_BAD_REQUEST);
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
			$signedRequest = $this->signatureManager->getIncomingSignedRequest($this->signatoryManager);
			$this->logger->debug('signed request available', ['signedRequest' => $signedRequest]);
			return $signedRequest;
		} catch (SignatureNotFoundException|SignatoryNotFoundException $e) {
			$this->logger->debug('remote does not support signed request', ['exception' => $e]);
			// remote does not support signed request.
			// currently we still accept unsigned request until lazy appconfig
			// core.enforce_signed_ocm_request is set to true (default: false)
			if ($this->appConfig->getValueBool('core', OCMSignatoryManager::APPCONFIG_SIGN_ENFORCED, lazy: true)) {
				$this->logger->notice('ignored unsigned request', ['exception' => $e]);
				throw new IncomingRequestException('Unsigned request');
			}
		} catch (SignatureException $e) {
			$this->logger->warning('wrongly signed request', ['exception' => $e]);
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
				$this->signatureManager->getSignatory($instance);
				throw new IncomingRequestException('instance is supposed to sign its request');
			} catch (SignatoryNotFoundException) {
				return;
			}
		}

		$body = json_decode($signedRequest->getBody(), true) ?? [];
		$entry = trim($body[$key] ?? '', '@');
		if ($this->getHostFromFederationId($entry) !== $signedRequest->getOrigin()) {
			throw new IncomingRequestException('share initiation (' . $signedRequest->getOrigin() . ') from different instance (' . $entry . ') [key=' . $key . ']');
		}
	}

	/**
	 *  confirm identity of the remote instance on notification, based on the share token.
	 *
	 *  If request is not signed, we still verify that the hostname from the extracted value does,
	 *  actually, not support signed request
	 *
	 * @param IIncomingSignedRequest|null $signedRequest
	 * @param string $resourceType
	 * @param string $sharedSecret
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
			if ($provider instanceof ISignedCloudFederationProvider) {
				$identity = $provider->getFederationIdFromSharedSecret($sharedSecret, $notification);
			} else {
				$this->logger->debug('cloud federation provider {provider} does not implements ISignedCloudFederationProvider', ['provider' => $provider::class]);
				return;
			}
		} catch (\Exception $e) {
			throw new IncomingRequestException($e->getMessage());
		}

		$this->confirmNotificationEntry($signedRequest, $identity);
	}


	/**
	 * @param IIncomingSignedRequest|null $signedRequest
	 * @param string $entry
	 *
	 * @return void
	 * @throws IncomingRequestException
	 */
	private function confirmNotificationEntry(?IIncomingSignedRequest $signedRequest, string $entry): void {
		$instance = $this->getHostFromFederationId($entry);
		if ($signedRequest === null) {
			try {
				$this->signatureManager->getSignatory($instance);
				throw new IncomingRequestException('instance is supposed to sign its request');
			} catch (SignatoryNotFoundException) {
				return;
			}
		} elseif ($instance !== $signedRequest->getOrigin()) {
			throw new IncomingRequestException('remote instance ' . $instance . ' not linked to origin ' . $signedRequest->getOrigin());
		}
	}

	/**
	 * @param string $entry
	 * @return string
	 * @throws IncomingRequestException
	 */
	private function getHostFromFederationId(string $entry): string {
		if (!str_contains($entry, '@')) {
			throw new IncomingRequestException('entry ' . $entry . ' does not contain @');
		}
		$rightPart = substr($entry, strrpos($entry, '@') + 1);

		// in case the full scheme is sent; getting rid of it
		$rightPart = $this->addressHandler->removeProtocolFromUrl($rightPart);
		try {
			return $this->signatureManager->extractIdentityFromUri('https://' . $rightPart);
		} catch (IdentityNotFoundException) {
			throw new IncomingRequestException('invalid host within federation id: ' . $entry);
		}
	}
}
