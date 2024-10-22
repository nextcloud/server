<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\External;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IDBConnection;
use OCP\IUser;

class MountProvider implements IMountProvider {
	public const STORAGE = '\OCA\Files_Sharing\External\Storage';

	/**
	 * @var callable
	 */
	private $managerProvider;

	/**
	 * @param IDBConnection $connection
	 * @param callable $managerProvider due to setup order we need a callable that return the manager instead of the manager itself
	 * @param ICloudIdManager $cloudIdManager
	 */
	public function __construct(
		private IDBConnection $connection,
		callable $managerProvider,
		private ICloudIdManager $cloudIdManager,
	) {
		$this->managerProvider = $managerProvider;
	}

	public function getMount(IUser $user, $data, IStorageFactory $storageFactory) {
		$managerProvider = $this->managerProvider;
		$manager = $managerProvider();
		$data['manager'] = $manager;
		$mountPoint = '/' . $user->getUID() . '/files/' . ltrim($data['mountpoint'], '/');
		$data['mountpoint'] = $mountPoint;
		$data['cloudId'] = $this->cloudIdManager->getCloudId($data['owner'], $data['remote']);
		$data['certificateManager'] = \OC::$server->getCertificateManager();
		$data['HttpClientService'] = \OC::$server->getHTTPClientService();
		return new Mount(self::STORAGE, $mountPoint, $data, $manager, $storageFactory);
	}

	public function getMountsForUser(IUser $user, IStorageFactory $loader) {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('remote', 'share_token', 'password', 'mountpoint', 'owner')
			->from('share_external')
			->where($qb->expr()->eq('user', $qb->createNamedParameter($user->getUID())))
			->andWhere($qb->expr()->eq('accepted', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		$mounts = [];
		while ($row = $result->fetch()) {
			$row['manager'] = $this;
			$row['token'] = $row['share_token'];
			$mounts[] = $this->getMount($user, $row, $loader);
		}
		$result->closeCursor();
		return $mounts;
	}
}
