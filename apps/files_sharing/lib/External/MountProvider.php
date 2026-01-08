<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\External;

use OCA\Files_Sharing\External\Storage as ExternalShareStorage;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\ICertificateManager;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Server;
use OCP\Share\IShare;

class MountProvider implements IMountProvider {
	public const STORAGE = ExternalShareStorage::class;

	/**
	 * @var callable
	 */
	private $managerProvider;

	/**
	 * @param callable $managerProvider due to set up order we need a callable that return the manager instead of the manager itself
	 */
	public function __construct(
		private readonly IDBConnection $connection,
		callable $managerProvider,
		private readonly ICloudIdManager $cloudIdManager,
	) {
		$this->managerProvider = $managerProvider;
	}

	private function getMount(IUser $user, array $data, IStorageFactory $storageFactory): Mount {
		$managerProvider = $this->managerProvider;
		$manager = $managerProvider();
		$data['manager'] = $manager;
		$mountPoint = '/' . $user->getUID() . '/files/' . ltrim($data['mountpoint'], '/');
		$data['mountpoint'] = $mountPoint;
		$data['cloudId'] = $this->cloudIdManager->getCloudId($data['owner'], $data['remote']);
		$data['certificateManager'] = Server::get(ICertificateManager::class);
		$data['HttpClientService'] = Server::get(IClientService::class);

		return new Mount(self::STORAGE, $mountPoint, $data, $manager, $storageFactory);
	}

	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id', 'remote', 'share_token', 'password', 'mountpoint', 'owner')
			->from('share_external')
			->where($qb->expr()->eq('user', $qb->createNamedParameter($user->getUID())))
			->andWhere($qb->expr()->eq('accepted', $qb->createNamedParameter(IShare::STATUS_ACCEPTED, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		$mounts = [];
		while ($row = $result->fetchAssociative()) {
			$row['manager'] = $this;
			$row['token'] = $row['share_token'];
			$mounts[] = $this->getMount($user, $row, $loader);
		}
		$result->closeCursor();
		return $mounts;
	}
}
