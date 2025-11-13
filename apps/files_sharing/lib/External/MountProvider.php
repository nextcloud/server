<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\External;

use LogicException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IPartialMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use OCP\Share\IShare;
use function str_starts_with;
use function strlen;
use function substr;

class MountProvider implements IMountProvider, IPartialMountProvider {
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
		$data['HttpClientService'] = Server::get(IClientService::class);
		return new Mount(self::STORAGE, $mountPoint, $data, $manager, $storageFactory);
	}

	public function getMountsForUser(IUser $user, IStorageFactory $loader) {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('remote', 'share_token', 'password', 'mountpoint', 'owner')
			->from('share_external')
			->where($qb->expr()->eq('user', $qb->createNamedParameter($user->getUID())))
			->andWhere($qb->expr()->eq('accepted', $qb->createNamedParameter
			(IShare::STATUS_ACCEPTED, IQueryBuilder::PARAM_INT)));
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

	public function getMountsFromMountPoints(
		string $path,
		array $mountsInfo,
		array $mountsMetadata,
		IStorageFactory $loader,
	): array {
		if (empty($mountsInfo)) {
			return [];
		}

		$uniqueMountOwnerIds = [];
		$uniqueRootIds = [];
		$user = null;
		foreach ($mountsInfo as $mountInfo) {
			// get a list of unique owner IDs root mount IDs
			$user ??= $mountInfo->getUser();
			$uniqueMountOwnerIds[$user->getUID()] ??= true;
			$uniqueRootIds[$mountInfo->getRootId()] ??= true;
		}
		$uniqueMountOwnerIds = array_keys($uniqueMountOwnerIds);
		$uniqueRootIds = array_keys($uniqueRootIds);

		// make sure the MPs belong to the same user
		if (count($uniqueMountOwnerIds) !== 1) {
			// question: what kind of exception to throw in here?
			throw new LogicException();
		}

		$mountOwnerId = $user->getUID();
		$pathPrefix = "/$mountOwnerId/files";
		$pathHashes = [];
		foreach ($mountsInfo as $mountInfo) {
			$mountPoint = rtrim($mountInfo->getMountPoint(), '/');
			if (str_starts_with($mountPoint, $pathPrefix)) {
				$pathHashes[] = md5(substr($mountPoint, strlen($pathPrefix)));
			}
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->select('remote', 'share_token', 'password', 'mountpoint', 'owner')
			->from('share_external')
			->where($qb->expr()->eq('user', $qb->createNamedParameter($user->getUID())))
			->andWhere($qb->expr()->in('mountpoint_hash',
				$qb->createNamedParameter($pathHashes, IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($qb->expr()->eq('accepted', $qb->createNamedParameter
			(IShare::STATUS_ACCEPTED, IQueryBuilder::PARAM_INT)));
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
