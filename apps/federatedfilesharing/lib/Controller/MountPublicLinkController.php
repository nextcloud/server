<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Björn Schießle <bjoern@schiessle.org>
 *
 * @author Allan Nordhøy <epost@anotheragency.no>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\FederatedFileSharing\Controller;

use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
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
#[OpenAPI(scope: OpenAPI::SCOPE_FEDERATION)]
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
	 * @NoCSRFRequired
	 * @PublicPage
	 * @BruteForceProtection(action=publicLink2FederatedShare)
	 *
	 * @param string $shareWith Username to share with
	 * @param string $token Token of the share
	 * @param string $password Password of the share
	 * @return JSONResponse<Http::STATUS_OK, array{remoteUrl: string}, array{}>|JSONResponse<Http::STATUS_BAD_REQUEST, array{message: string}, array{}>
	 * 200: Remote URL returned
	 * 400: Creating share is not possible
	 */
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
		$authenticated = $this->session->get('public_link_authenticated') === $share->getId() ||
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
	 * @NoAdminRequired
	 *
	 * @param string $token
	 * @param string $remote
	 * @param string $password
	 * @param string $owner (only for legacy reasons, can be removed with legacyMountPublicLink())
	 * @param string $ownerDisplayName (only for legacy reasons, can be removed with legacyMountPublicLink())
	 * @param string $name (only for legacy reasons, can be removed with legacyMountPublicLink())
	 * @return JSONResponse
	 */
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
