<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing\Controller;

use OCA\DAV\Connector\Sabre\PublicAuth;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Constants;
use OCP\Federation\ICloudIdManager;
use OCP\HintException;
use OCP\Http\Client\IClientService;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/**
 * Class MountPublicLinkController
 *
 * convert public links to federated shares
 *
 * @package OCA\FederatedFileSharing\Controller
 */
class MountPublicLinkController extends Controller {
	/**
	 * MountPublicLinkController constructor.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private FederatedShareProvider $federatedShareProvider,
		private IManager $shareManager,
		private AddressHandler $addressHandler,
		private ISession $session,
		private IL10N $l,
		private IUserSession $userSession,
		private IClientService $clientService,
		private ICloudIdManager $cloudIdManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * send federated share to a user of a public link
	 *
	 * @param string $shareWith Username to share with
	 * @param string $token Token of the share
	 * @param string $password Password of the share
	 * @return JSONResponse<Http::STATUS_OK, array{remoteUrl: string}, array{}>|JSONResponse<Http::STATUS_BAD_REQUEST, array{message: string}, array{}>
	 *
	 * 200: Remote URL returned
	 * 400: Creating share is not possible
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	#[BruteForceProtection(action: 'publicLink2FederatedShare')]
	#[OpenAPI(scope: OpenAPI::SCOPE_FEDERATION)]
	public function createFederatedShare($shareWith, $token, $password = '') {
		if (!$this->federatedShareProvider->isOutgoingServer2serverShareEnabled()) {
			return new JSONResponse(
				['message' => 'This server doesn\'t support outgoing federated shares'],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			[, $server] = $this->addressHandler->splitUserRemote($shareWith);
			$share = $this->shareManager->getShareByToken($token);
		} catch (HintException $e) {
			$response = new JSONResponse(['message' => $e->getHint()], Http::STATUS_BAD_REQUEST);
			$response->throttle();
			return $response;
		}

		// make sure that user is authenticated in case of a password protected link
		$storedPassword = $share->getPassword();
		$authenticated = $this->session->get(PublicAuth::DAV_AUTHENTICATED) === $share->getId() ||
			$this->shareManager->checkPassword($share, $password);
		if (!empty($storedPassword) && !$authenticated) {
			$response = new JSONResponse(
				['message' => 'No permission to access the share'],
				Http::STATUS_BAD_REQUEST
			);
			$response->throttle();
			return $response;
		}

		if (($share->getPermissions() & Constants::PERMISSION_READ) === 0) {
			$response = new JSONResponse(
				['message' => 'Mounting file drop not supported'],
				Http::STATUS_BAD_REQUEST
			);
			$response->throttle();
			return $response;
		}

		$share->setSharedWith($shareWith);
		$share->setShareType(IShare::TYPE_REMOTE);

		try {
			$this->federatedShareProvider->create($share);
		} catch (\Exception $e) {
			$this->logger->warning($e->getMessage(), [
				'app' => 'federatedfilesharing',
				'exception' => $e,
			]);
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse(['remoteUrl' => $server]);
	}

	/**
	 * ask other server to get a federated share
	 *
	 * @param string $token
	 * @param string $remote
	 * @param string $password
	 * @param string $owner (only for legacy reasons, can be removed with legacyMountPublicLink())
	 * @param string $ownerDisplayName (only for legacy reasons, can be removed with legacyMountPublicLink())
	 * @param string $name (only for legacy reasons, can be removed with legacyMountPublicLink())
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function askForFederatedShare($token, $remote, $password = '', $owner = '', $ownerDisplayName = '', $name = '') {
		// check if server admin allows to mount public links from other servers
		if ($this->federatedShareProvider->isIncomingServer2serverShareEnabled() === false) {
			return new JSONResponse(['message' => $this->l->t('Server to server sharing is not enabled on this server')], Http::STATUS_BAD_REQUEST);
		}

		$cloudId = $this->cloudIdManager->getCloudId($this->userSession->getUser()->getUID(), $this->addressHandler->generateRemoteURL());

		$httpClient = $this->clientService->newClient();

		try {
			$response = $httpClient->post($remote . '/index.php/apps/federatedfilesharing/createFederatedShare',
				[
					'body' =>
						[
							'token' => $token,
							'shareWith' => rtrim($cloudId->getId(), '/'),
							'password' => $password
						],
					'connect_timeout' => 10,
				]
			);
		} catch (\Exception $e) {
			if (empty($password)) {
				$message = $this->l->t("Couldn't establish a federated share.");
			} else {
				$message = $this->l->t("Couldn't establish a federated share, maybe the password was wrong.");
			}
			return new JSONResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}

		$body = $response->getBody();
		$result = json_decode($body, true);

		if (is_array($result) && isset($result['remoteUrl'])) {
			return new JSONResponse(['message' => $this->l->t('Federated Share request sent, you will receive an invitation. Check your notifications.')]);
		}

		// if we doesn't get the expected response we assume that we try to add
		// a federated share from a Nextcloud <= 9 server
		$message = $this->l->t("Couldn't establish a federated share, it looks like the server to federate with is too old (Nextcloud <= 9).");
		return new JSONResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
	}
}
