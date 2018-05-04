<?php
/**
 * @copyright Copyright (c) 2018 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\CloudFederationAPI\Controller;

use OCA\CloudFederationAPI\Config;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\Exceptions\ShareNotFoundException;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\Exceptions\ProviderDoesNotExistsException;
use OCP\Federation\ICloudIdManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;


/**
 * Class RequestHandlerController
 *
 * handle API between different Cloud instances
 *
 * @package OCA\CloudFederationAPI\Controller
 */
class RequestHandlerController extends Controller {

	/** @var ILogger */
	private $logger;

	/** @var IUserManager */
	private $userManager;

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
								ILogger $logger,
								IUserManager $userManager,
								IURLGenerator $urlGenerator,
								ICloudFederationProviderManager $cloudFederationProviderManager,
								Config $config,
								ICloudFederationFactory $factory,
								ICloudIdManager $cloudIdManager
	) {
		parent::__construct($appName, $request);

		$this->logger = $logger;
		$this->userManager = $userManager;
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
	 * @param string $description share description (optional)
	 * @param string $providerId resource UID on the provider side
	 * @param string $owner provider specific UID of the user who owns the resource
	 * @param string $ownerDisplayName display name of the user who shared the item
	 * @param string $sharedBy provider specific UID of the user who shared the resource
	 * @param $sharedByDisplayName display name of the user who shared the resource
	 * @param array $protocol (e,.g. ['name' => 'webdav', 'options' => ['username' => 'john', 'permissions' => 31]])
	 * @param string $shareType ('group' or 'user' share)
	 * @param $resourceType ('file', 'calendar',...)
	 * @return Http\DataResponse|JSONResponse
	 *
	 * Example: curl -H "Content-Type: application/json" -X POST -d '{"shareWith":"admin1@serve1","name":"welcome server2.txt","description":"desc","providerId":"2","owner":"admin2@http://localhost/server2","ownerDisplayName":"admin2 display","shareType":"user","resourceType":"file","protocol":{"name":"webdav","options":{"access_token":"8Lrd1FVEREthux7","permissions":31}}}' http://localhost/server/index.php/ocm/shares
	 */
	public function addShare($shareWith, $name, $description, $providerId, $owner, $ownerDisplayName, $sharedBy, $sharedByDisplayName, $protocol, $shareType, $resourceType) {

		if (!$this->config->incomingRequestsEnabled()) {
			return new JSONResponse(
				['message' => 'This server doesn\'t support outgoing federated shares'],
			Http::STATUS_NOT_IMPLEMENTED
			);
		}

		// check if all required parameters are set
		if ($shareWith === null ||
			$name === null ||
			$providerId === null ||
			$owner === null ||
			$resourceType === null ||
			$shareType === null ||
			!is_array($protocol) ||
			!isset($protocol['name']) ||
			!isset ($protocol['options']) ||
			!is_array($protocol['options'])
		) {
			return new JSONResponse(
				['message' => 'Missing arguments'],
				Http::STATUS_BAD_REQUEST
			);
		}

		$cloudId = $this->cloudIdManager->resolveCloudId($shareWith);
		$shareWithLocalId = $cloudId->getUser();
		$shareWith = $this->mapUid($shareWithLocalId);

		if (!$this->userManager->userExists($shareWith)) {
			return new JSONResponse(
				['message' => 'User "' . $shareWith . '" does not exists at ' . $this->urlGenerator->getBaseUrl()],
				Http::STATUS_BAD_REQUEST
			);
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
			$share = $this->factory->getCloudFederationShare($shareWith, $name, $description, $providerId, $owner, $ownerDisplayName, $sharedBy, $sharedByDisplayName, $protocol, $shareType, $resourceType);
			$id = $provider->shareReceived($share);
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
		} catch (\Exception $e) {
			return new JSONResponse(
				['message' => 'Internal error at ' . $this->urlGenerator->getBaseUrl()],
				Http::STATUS_BAD_REQUEST
			);
		}

		return new JSONResponse(
			['id' => $id, 'createdAt' => time()],
			Http::STATUS_CREATED);

	}

	/**
	 * receive notification about existing share
	 *
	 * @param $resourceType ('file', 'calendar',...)
	 * @param string $name resource name (e.g "file", "calendar",...)
	 * @param string $id unique id of the corresponding item on the receiving site
	 * @param array $notification contain the actual notification, content is defined by cloud federation provider
	 * @return JSONResponse
	 */
	public function receiveNotification($resourceType, $name, $id, $notification) {
		if (!$this->config->incomingRequestsEnabled()) {
			return new JSONResponse(
				['message' => 'This server doesn\'t support outgoing federated shares'],
				Http::STATUS_NOT_IMPLEMENTED
			);
		}

		// check if all required parameters are set
		if ($name === null ||
			$id === null ||
			!is_array($notification)
		) {
			return new JSONResponse(
				['message' => 'Missing arguments'],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$provider = $this->cloudFederationProviderManager->getCloudFederationProvider($resourceType);
			$provider->notificationReceived($id, $notification);
		} catch (ProviderDoesNotExistsException $e) {
			return new JSONResponse(
				['message' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		} catch (ShareNotFoundException $e) {
			return new JSONResponse(
				['message' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		} catch (\Exception $e) {
			return new JSONResponse(
				['message' => 'Internal error at ' . $this->urlGenerator->getBaseUrl()],
				Http::STATUS_BAD_REQUEST
			);
		}


		return new JSONResponse(
			['id' => $id, 'createdAt' => date()],
			Http::STATUS_CREATED);


	}

	/**
	 * map login name to internal LDAP UID if a LDAP backend is in use
	 *
	 * @param string $uid
	 * @return string mixed
	 */
	private function mapUid($uid) {
		\OC::$server->getURLGenerator()->linkToDocs('key');
		// FIXME this should be a method in the user management instead
		$this->logger->debug('shareWith before, ' . $uid, ['app' => $this->appName]);
		\OCP\Util::emitHook(
			'\OCA\Files_Sharing\API\Server2Server',
			'preLoginNameUsedAsUserName',
			array('uid' => &$uid)
		);
		$this->logger->debug('shareWith after, ' . $uid, ['app' => $this->appName]);

		return $uid;
	}

}
