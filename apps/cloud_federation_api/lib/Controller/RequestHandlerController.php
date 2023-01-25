<?php
/**
 * @copyright Copyright (c) 2018 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\CloudFederationAPI\Controller;

use Exception;
use OCA\CloudFederationAPI\Config;
use OCA\CloudFederationAPI\ResponseDefinitions;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Federation\Exceptions\ActionNotSupportedException;
use OCP\Federation\Exceptions\AuthenticationFailedException;
use OCP\Federation\Exceptions\BadRequestException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\Exceptions\ProviderDoesNotExistsException;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * Class RequestHandlerController
 *
 * handle API between different Cloud instances
 *
 * @package OCA\CloudFederationAPI\Controller
 */
class RequestHandlerController extends Controller {

	/** @var LoggerInterface */
	private $logger;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ICloudFederationProviderManager */
	private $cloudFederationProviderManager;

	/** @var Config */
	private $config;

	/** @var ICloudFederationFactory */
	private $factory;

	/** @var ICloudIdManager */
	private $cloudIdManager;

	public function __construct($appName,
		IRequest $request,
		LoggerInterface $logger,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IURLGenerator $urlGenerator,
		ICloudFederationProviderManager $cloudFederationProviderManager,
		Config $config,
		ICloudFederationFactory $factory,
		ICloudIdManager $cloudIdManager
	) {
		parent::__construct($appName, $request);

		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->urlGenerator = $urlGenerator;
		$this->cloudFederationProviderManager = $cloudFederationProviderManager;
		$this->config = $config;
		$this->factory = $factory;
		$this->cloudIdManager = $cloudIdManager;
	}

	/**
	 * add share
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @BruteForceProtection(action=receiveFederatedShare)
	 *
	 * @param string $shareWith
	 * @param string $name resource name (e.g. document.odt)
	 * @param string|null $description share description
	 * @param string $providerId resource UID on the provider side
	 * @param string $owner provider specific UID of the user who owns the resource
	 * @param string|null $ownerDisplayName display name of the user who shared the item
	 * @param string|null $sharedBy provider specific UID of the user who shared the resource
	 * @param string|null $sharedByDisplayName display name of the user who shared the resource
	 * @param array{name: string[], options: array{}} $protocol e,.g. ['name' => 'webdav', 'options' => ['username' => 'john', 'permissions' => 31]]
	 * @param string $shareType 'group' or 'user' share
	 * @param string $resourceType 'file', 'calendar',...
	 *
	 * @psalm-import-type CloudFederationAddShare from ResponseDefinitions
	 * @psalm-import-type CloudFederationValidationError from ResponseDefinitions
	 * @psalm-import-type CloudFederationError from ResponseDefinitions
	 * @return JSONResponse<CloudFederationAddShare> 201 The notification was successfully received. The display name of the recepient might be returned in the body.
	 * @return JSONResponse<CloudFederationValidationError> 400 Bad request due to invalid parameters, e.g. when `shareWith` is not found or required properties are missing.
	 * @return JSONResponse<CloudFederationError> 501 Share type or the resource type is not supported.
	 *
	 * Example: curl -H "Content-Type: application/json" -X POST -d '{"shareWith":"admin1@serve1","name":"welcome server2.txt","description":"desc","providerId":"2","owner":"admin2@http://localhost/server2","ownerDisplayName":"admin2 display","shareType":"user","resourceType":"file","protocol":{"name":"webdav","options":{"sharedSecret":"secret","permissions":"webdav-property"}}}' http://localhost/server/index.php/ocm/shares
	 */
	public function addShare(string $shareWith, string $name, ?string $description, string $providerId, string $owner, ?string $ownerDisplayName, ?string $sharedBy, ?string $sharedByDisplayName, array $protocol, string $shareType, string $resourceType): JSONResponse {

		// check if all required parameters are set
		if (!isset($protocol['name']) ||
			!isset($protocol['options']) ||
			!is_array($protocol['options']) ||
			!isset($protocol['options']['sharedSecret'])
		) {
			return new JSONResponse(
				['message' => 'Missing arguments'],
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
					['message' => 'User "' . $shareWith . '" does not exists at ' . $this->urlGenerator->getBaseUrl()],
					Http::STATUS_BAD_REQUEST
				);
				$response->throttle();
				return $response;
			}
		}

		if ($shareType === 'group') {
			if (!$this->groupManager->groupExists($shareWith)) {
				$response = new JSONResponse(
					['message' => 'Group "' . $shareWith . '" does not exists at ' . $this->urlGenerator->getBaseUrl()],
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
		} catch (ProviderDoesNotExistsException $e) {
			return new JSONResponse(
				['message' => $e->getMessage()],
				Http::STATUS_NOT_IMPLEMENTED
			);
		} catch (ProviderCouldNotAddShareException $e) {
			return new JSONResponse(
				['message' => $e->getMessage()],
				$e->getCode()
			);
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return new JSONResponse(
				['message' => 'Internal error at ' . $this->urlGenerator->getBaseUrl()],
				Http::STATUS_BAD_REQUEST
			);
		}

		$user = $this->userManager->get($shareWith);
		$recipientDisplayName = '';
		if ($user) {
			$recipientDisplayName = $user->getDisplayName();
		}

		return new JSONResponse(
			['recipientDisplayName' => $recipientDisplayName],
			Http::STATUS_CREATED);
	}

	/**
	 * receive notification about existing share
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @BruteForceProtection(action=receiveFederatedShareNotification)
	 *
	 * @param string $notificationType notification type, e.g. SHARE_ACCEPTED
	 * @param string $resourceType calendar, file, contact,...
	 * @param string|null $providerId id of the share
	 * @param array{}|null $notification the actual payload of the notification
	 *
	 * @psalm-import-type CloudFederationValidationError from ResponseDefinitions
	 * @psalm-import-type CloudFederationError from ResponseDefinitions
	 * @return JSONResponse<array{}> 201 The notification was successfully received
	 * @return JSONResponse<CloudFederationValidationError> 400 Bad request due to invalid parameters, e.g. when `type` is invalid or missing.
	 * @return JSONResponse<CloudFederationError> 501 The resource type is not supported.
	 */
	public function receiveNotification(string $notificationType, string $resourceType, ?string $providerId, ?array $notification): JSONResponse {

		// check if all required parameters are set
		if ($providerId === null || !is_array($notification)) {
			return new JSONResponse(
				['message' => 'Missing arguments'],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$provider = $this->cloudFederationProviderManager->getCloudFederationProvider($resourceType);
			$result = $provider->notificationReceived($notificationType, $providerId, $notification);
		} catch (ProviderDoesNotExistsException $e) {
			return new JSONResponse(
				['message' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		} catch (ShareNotFound $e) {
			$response = new JSONResponse(
				['message' => $e->getMessage()],
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
		} catch (Exception $e) {
			return new JSONResponse(
				['message' => 'Internal error at ' . $this->urlGenerator->getBaseUrl()],
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
}
