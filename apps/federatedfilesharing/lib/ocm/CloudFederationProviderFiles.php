<?php
/**
 * @copyright Copyright (c) 2018 Bjoern Schiessle <bjoern@schiessle.org>
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

namespace OCA\FederatedFileSharing\OCM;

use OC\AppFramework\Http;
use OCA\Files_Sharing\Activity\Providers\RemoteShares;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\Activity\IManager as IActivityManager;
use OCP\App\IAppManager;
use OCP\Federation\Exceptions\ActionNotSupportedException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\Exceptions\ShareNotFoundException;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudIdManager;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;

class CloudFederationProviderFiles implements ICloudFederationProvider {

	/** @var IAppManager */
	private $appManager;

	/** @var FederatedShareProvider */
	private $federatedShareProvider;

	/** @var AddressHandler */
	private $addressHandler;

	/** @var ILogger */
	private $logger;

	/** @var IUserManager */
	private $userManager;

	/** @var ICloudIdManager */
	private $cloudIdManager;

	/** @var IActivityManager */
	private $activityManager;

	/** @var INotificationManager */
	private $notificationManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * CloudFederationProvider constructor.
	 *
	 * @param IAppManager $appManager
	 * @param FederatedShareProvider $federatedShareProvider
	 * @param AddressHandler $addressHandler
	 * @param ILogger $logger
	 * @param IUserManager $userManager
	 * @param ICloudIdManager $cloudIdManager
	 * @param IActivityManager $activityManager
	 * @param INotificationManager $notificationManager
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IAppManager $appManager,
								FederatedShareProvider $federatedShareProvider,
								AddressHandler $addressHandler,
								ILogger $logger,
								IUserManager $userManager,
								ICloudIdManager $cloudIdManager,
								IActivityManager $activityManager,
								INotificationManager $notificationManager,
								IURLGenerator $urlGenerator
	) {
		$this->appManager = $appManager;
		$this->federatedShareProvider = $federatedShareProvider;
		$this->addressHandler = $addressHandler;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->cloudIdManager = $cloudIdManager;
		$this->activityManager = $activityManager;
		$this->notificationManager = $notificationManager;
		$this->urlGenerator = $urlGenerator;
	}



	/**
	 * @return string
	 */
	public function getShareType() {
		return 'file';
	}

	/**
	 * share received from another server
	 *
	 * @param ICloudFederationShare $share
	 * @return string provider specific unique ID of the share
	 *
	 * @throws ProviderCouldNotAddShareException
	 * @throws \OCP\AppFramework\QueryException
	 * @throws \OC\HintException
	 * @since 14.0.0
	 */
	public function shareReceived(ICloudFederationShare $share) {

		if (!$this->isS2SEnabled(true)) {
			throw new ProviderCouldNotAddShareException('Server does not support federated cloud sharing', '', Http::STATUS_SERVICE_UNAVAILABLE);
		}

		$protocol = $share->getProtocol();
		if ($protocol['name'] !== 'webdav') {
			throw new ProviderCouldNotAddShareException('Unsupported protocol for data exchange.', '', Http::STATUS_NOT_IMPLEMENTED);
		}

		list($ownerUid, $remote) = $this->addressHandler->splitUserRemote($share->getOwner());

		$remote = $remote;
		$token = $share->getShareSecret();
		$name = $share->getResourceName();
		$owner = $share->getOwnerDisplayName();
		$sharedBy = $share->getSharedByDisplayName();
		$shareWith = $share->getShareWith();
		$remoteId = $share->getProviderId();
		$sharedByFederatedId = $share->getSharedBy();
		$ownerFederatedId = $share->getOwner();

		// if no explicit information about the person who created the share was send
		// we assume that the share comes from the owner
		if ($sharedByFederatedId === null) {
			$sharedBy = $owner;
			$sharedByFederatedId = $ownerFederatedId;
		}

		if ($remote && $token && $name && $owner && $remoteId && $shareWith) {

			if (!\OCP\Util::isValidFileName($name)) {
				throw new ProviderCouldNotAddShareException('The mountpoint name contains invalid characters.', '', Http::STATUS_BAD_REQUEST);
			}

			// FIXME this should be a method in the user management instead
			$this->logger->debug('shareWith before, ' . $shareWith, ['app' => 'files_sharing']);
			\OCP\Util::emitHook(
				'\OCA\Files_Sharing\API\Server2Server',
				'preLoginNameUsedAsUserName',
				array('uid' => &$shareWith)
			);
			$this->logger->debug('shareWith after, ' . $shareWith, ['app' => 'files_sharing']);

			if (!$this->userManager->userExists($shareWith)) {
				throw new ProviderCouldNotAddShareException('User does not exists', '',Http::STATUS_BAD_REQUEST);
			}

			\OC_Util::setupFS($shareWith);

			$externalManager = new \OCA\Files_Sharing\External\Manager(
				\OC::$server->getDatabaseConnection(),
				\OC\Files\Filesystem::getMountManager(),
				\OC\Files\Filesystem::getLoader(),
				\OC::$server->getHTTPClientService(),
				\OC::$server->getNotificationManager(),
				\OC::$server->query(\OCP\OCS\IDiscoveryService::class),
				\OC::$server->getCloudFederationProviderManager(),
				\OC::$server->getCloudFederationFactory(),
				$shareWith
			);

			try {
				$externalManager->addShare($remote, $token, '', $name, $owner, false, $shareWith, $remoteId);
				$shareId = \OC::$server->getDatabaseConnection()->lastInsertId('*PREFIX*share_external');

				$event = $this->activityManager->generateEvent();
				$event->setApp('files_sharing')
					->setType('remote_share')
					->setSubject(RemoteShares::SUBJECT_REMOTE_SHARE_RECEIVED, [$ownerFederatedId, trim($name, '/')])
					->setAffectedUser($shareWith)
					->setObject('remote_share', (int)$shareId, $name);
				\OC::$server->getActivityManager()->publish($event);

				$notification = $this->notificationManager->createNotification();
				$notification->setApp('files_sharing')
					->setUser($shareWith)
					->setDateTime(new \DateTime())
					->setObject('remote_share', $shareId)
					->setSubject('remote_share', [$ownerFederatedId, $sharedByFederatedId, trim($name, '/')]);

				$declineAction = $notification->createAction();
				$declineAction->setLabel('decline')
					->setLink($this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkTo('', 'ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending/' . $shareId)), 'DELETE');
				$notification->addAction($declineAction);

				$acceptAction = $notification->createAction();
				$acceptAction->setLabel('accept')
					->setLink($this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkTo('', 'ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending/' . $shareId)), 'POST');
				$notification->addAction($acceptAction);

				$this->notificationManager->notify($notification);

				return $shareId;
			} catch (\Exception $e) {
				$this->logger->logException($e, [
					'message' => 'Server can not add remote share.',
					'level' => \OCP\Util::ERROR,
					'app' => 'files_sharing'
				]);
				throw new ProviderCouldNotAddShareException('internal server error, was not able to add share from ' . $remote, '', HTTP::STATUS_INTERNAL_SERVER_ERROR);
			}
		}

		throw new ProviderCouldNotAddShareException('server can not add remote share, missing parameter', '', HTTP::STATUS_BAD_REQUEST);

	}

	/**
	 * notification received from another server
	 *
	 * @param string $notificationType (e.g. SHARE_ACCEPTED)
	 * @param string $providerId id of the share
	 * @param array $notification payload of the notification
	 *
	 * @throws ShareNotFoundException
	 * @throws ActionNotSupportedException
	 *
	 * @since 14.0.0
	 */
	public function notificationReceived($notificationType, $providerId, array $notification) {
		switch ($notificationType) {
			case 'SHARE_ACCEPTED' :
				return;
		}


		throw new ActionNotSupportedException($notification);
	}

	/**
	 * check if server-to-server sharing is enabled
	 *
	 * @param bool $incoming
	 * @return bool
	 */
	private function isS2SEnabled($incoming = false) {

		$result = $this->appManager->isEnabledForUser('files_sharing');

		if ($incoming) {
			$result = $result && $this->federatedShareProvider->isIncomingServer2serverShareEnabled();
		} else {
			$result = $result && $this->federatedShareProvider->isOutgoingServer2serverShareEnabled();
		}

		return $result;
	}


}
