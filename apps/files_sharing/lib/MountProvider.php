<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use Exception;
use InvalidArgumentException;
use LogicException;
use OC\Files\View;
use OCA\Files_Sharing\Event\ShareMountedEvent;
use OCA\Talk\Share\RoomShareProvider;
use OCP\Cache\CappedMemoryCache;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IPartialMountProvider;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\Share\IAttributes;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;
use function array_filter;
use function array_values;
use function count;

class MountProvider implements IMountProvider, IPartialMountProvider {

	private const SUPPORTED_SHARE_TYPES = [
		IShare::TYPE_USER,
		IShare::TYPE_GROUP,
		IShare::TYPE_USERGROUP,
		RoomShareProvider::SHARE_TYPE_USERROOM,
		IShare::TYPE_CIRCLE,
		IShare::TYPE_ROOM,
		IShare::TYPE_DECK,
		IShare::TYPE_SCIENCEMESH,
	];

	/**
	 * Maps internal share types to the right provider
	 *
	 * NOTE: this information should come either from the provider or from
	 * the provider factory!
	 */
	private const TYPE_MAPPING = [
		IShare::TYPE_USERGROUP => IShare::TYPE_GROUP,
		RoomShareProvider::SHARE_TYPE_USERROOM => IShare::TYPE_ROOM,
	];

	/**
	 * @param IConfig $config
	 * @param IManager $shareManager
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		protected IConfig $config,
		protected IManager $shareManager,
		protected LoggerInterface $logger,
		protected IEventDispatcher $eventDispatcher,
		protected ICacheFactory $cacheFactory,
		protected IMountManager $mountManager,
		protected IDBConnection $dbConn,
		protected IRootFolder $rootFolder,
	) {
	}

	/**
	 * Get all mountpoints applicable for the user and check for shares where we need to update the etags
	 *
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 * @return IMountPoint[]
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader) {
		$userId = $user->getUID();
		$shares = array_merge(
			$this->shareManager->getSharedWith($userId, IShare::TYPE_USER, null, -1),
			$this->shareManager->getSharedWith($userId, IShare::TYPE_GROUP, null, -1),
			$this->shareManager->getSharedWith($userId, IShare::TYPE_CIRCLE, null, -1),
			$this->shareManager->getSharedWith($userId, IShare::TYPE_ROOM, null, -1),
			$this->shareManager->getSharedWith($userId, IShare::TYPE_DECK, null, -1),
			$this->shareManager->getSharedWith($userId, IShare::TYPE_SCIENCEMESH, null, -1),
		);

		$shares = $this->filterShares($shares, $userId);
		$superShares = $this->buildSuperShares($shares, $user);

		return $this->getMountsFromSuperShares($userId, $superShares, $loader, $user);
	}

	/**
	 * Groups shares by path (nodeId) and target path
	 *
	 * @param IShare[] $shares
	 * @return IShare[][] array of grouped shares, each element in the
	 *                    array is a group which itself is an array of shares
	 */
	private function groupShares(array $shares) {
		$tmp = [];

		foreach ($shares as $share) {
			$nodeId = $share->getNodeId();
			if (!isset($tmp[$nodeId])) {
				$tmp[$nodeId] = [];
			}
			$tmp[$nodeId][] = $share;
		}

		$result = [];
		// sort by stime, the super share will be based on the least recent share
		foreach ($tmp as &$tmp2) {
			@usort($tmp2, function ($a, $b) {
				$aTime = $a->getShareTime()->getTimestamp();
				$bTime = $b->getShareTime()->getTimestamp();
				if ($aTime === $bTime) {
					return $a->getId() < $b->getId() ? -1 : 1;
				}
				return $aTime < $bTime ? -1 : 1;
			});
			$result[] = $tmp2;
		}

		return $result;
	}

	/**
	 * Groups shares by node ID and builds a new share object (super share)
	 * which represents a summarized version of all the shares in the group.
	 *
	 * The permissions and attributes of the super share are accumulated from
	 * the shares in the group, forming the most permissive combination
	 * possible.
	 *
	 * @param IShare[] $allShares
	 * @param IUser $user user
	 * @return list<array{IShare, array<IShare>}> Tuple of [superShare, groupedShares]
	 */
	private function buildSuperShares(array $allShares, IUser $user) {
		$result = [];

		$groupedShares = $this->groupShares($allShares);

		foreach ($groupedShares as $shares) {
			if (count($shares) === 0) {
				continue;
			}

			$superShare = $this->shareManager->newShare();

			// compute super share based on first entry of the group
			$superShare->setId($shares[0]->getId())
				->setShareOwner($shares[0]->getShareOwner())
				->setNodeId($shares[0]->getNodeId())
				->setShareType($shares[0]->getShareType())
				->setTarget($shares[0]->getTarget());

			$this->combineNotes($shares, $superShare);

			// use most permissive permissions
			// this covers the case where there are multiple shares for the same
			// file e.g. from different groups and different permissions
			$superPermissions = 0;
			$superAttributes = $this->shareManager->newShare()->newAttributes();
			$status = IShare::STATUS_PENDING;
			foreach ($shares as $share) {
				$status = max($status, $share->getStatus());
				// update permissions
				$superPermissions |= $share->getPermissions();

				// update share permission attributes
				$attributes = $share->getAttributes();
				if ($attributes !== null) {
					$this->mergeAttributes($attributes, $superAttributes);
				}

				$this->adjustTarget($share, $superShare, $user);
				if ($share->getNodeCacheEntry() !== null) {
					$superShare->setNodeCacheEntry($share->getNodeCacheEntry());
				}
			}

			$superShare->setPermissions($superPermissions);
			$superShare->setStatus($status);
			$superShare->setAttributes($superAttributes);

			$result[] = [$superShare, $shares];
		}

		return $result;
	}

	/**
	 * Combines $attributes into the most permissive set of attributes and
	 * sets them in $superAttributes.
	 */
	public function mergeAttributes(
		IAttributes $attributes,
		IAttributes $superAttributes,
	): void {
		foreach ($attributes->toArray() as $attribute) {
			if ($superAttributes->getAttribute(
				$attribute['scope'],
				$attribute['key']
			) === true) {
				// if super share attribute is already enabled, it is most permissive
				continue;
			}
			// update super share attributes with subshare attribute
			$superAttributes->setAttribute(
				$attribute['scope'],
				$attribute['key'],
				$attribute['value']
			);
		}
	}

	/**
	 * Gather notes from all the shares. Since these are readily available
	 * here, storing them enables the DAV FilesPlugin to avoid executing many
	 * DB queries to retrieve the same information.
	 *
	 * @param array<IShare> $shares
	 * @param IShare $superShare
	 * @return void
	 */
	public function combineNotes(
		array &$shares,
		IShare $superShare,
	): void {
		$allNotes = implode(
			"\n",
			array_map(static fn ($sh) => $sh->getNote(), $shares)
		);
		$superShare->setNote($allNotes);
	}

	/**
	 * Adjusts the target in $share for DB consistency, if needed.
	 */
	public function adjustTarget(
		IShare $share,
		IShare $superShare,
		IUser $user,
	): void {
		if ($share->getTarget() === $superShare->getTarget()) {
			return;
		}

		$share->setTarget($superShare->getTarget());
		try {
			$this->shareManager->moveShare($share, $user->getUID());
		} catch (InvalidArgumentException $e) {
			// ignore as it is not important and we don't want to
			// block FS setup

			// the subsequent code anyway only uses the target of the
			// super share

			// such issue can usually happen when dealing with
			// null groups which usually appear with group backend
			// caching inconsistencies
			$this->logger->debug(
				'Could not adjust share target for share ' . $share->getId(
				) . ' to make it consistent: ' . $e->getMessage(),
				['app' => 'files_sharing']
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getMountsFromMountPoints(
		string $path,
		array $mountProviderArgs,
		IStorageFactory $loader,
	): array {
		if (empty($mountProviderArgs)) {
			return [];
		}

		$uniqueMountOwnerIds = [];
		$uniqueRootIds = [];
		$user = null;
		foreach ($mountProviderArgs as $mountProviderArg) {
			$mountInfo = $mountProviderArg->mountInfo;
			// get a list of unique owner IDs root mount IDs
			$user ??= $mountInfo->getUser();
			$uniqueMountOwnerIds[$user->getUID()] ??= true;
			$uniqueRootIds[$mountInfo->getRootId()] ??= true;
		}
		$uniqueMountOwnerIds = array_keys($uniqueMountOwnerIds);
		$uniqueRootIds = array_keys($uniqueRootIds);

		// make sure the MPs belong to the same user
		if ($user === null || count($uniqueMountOwnerIds) !== 1) {
			// question: what kind of exception to throw in here?
			throw new LogicException();
		}

		$rootIdsByShareProviderType = $this->getShareInfo($user, $uniqueRootIds);
		$sharesInPath = [];
		foreach ($rootIdsByShareProviderType as $shareType => $rootIds) {
			// todo: pagination for many rootIds
			$sharesInPath[] = $this->shareManager->getSharedWithByNodes(
				$uniqueMountOwnerIds[0],
				$shareType,
				$rootIds,
				-1
			);
		}
		$sharesInPath = array_merge(...$sharesInPath);

		// filter out shares owned or shared by the user and ones for which
		// the user has no permissions
		$shares = $this->filterShares($sharesInPath, $user->getUID());
		$superShares = $this->buildSuperShares($shares, $user);

		return $this->getMountsFromSuperShares(
			$user->getUID(),
			$superShares,
			$loader,
			$user,
		);
	}

	/**
	 * @param string $userId
	 * @param array $superShares
	 * @param IStorageFactory $loader
	 * @param IUser $user
	 * @return array
	 * @throws Exception
	 */
	public function getMountsFromSuperShares(
		string $userId,
		array $superShares,
		IStorageFactory $loader,
		IUser $user,
	): array {
		$allMounts = $this->mountManager->getAll();
		$mounts = [];
		$view = new View('/' . $userId . '/files');
		$ownerViews = [];
		$sharingDisabledForUser
			= $this->shareManager->sharingDisabledForUser($userId);
		/** @var CappedMemoryCache<bool> $folderExistCache */
		$foldersExistCache = new CappedMemoryCache();

		$validShareCache
			= $this->cacheFactory->createLocal('share-valid-mountpoint-max');
		$maxValidatedShare = $validShareCache->get($userId) ?? 0;
		$newMaxValidatedShare = $maxValidatedShare;

		foreach ($superShares as $share) {
			[$parentShare, $groupedShares] = $share;
			try {
				if ($parentShare->getStatus() !== IShare::STATUS_ACCEPTED
					&& ($parentShare->getShareType() === IShare::TYPE_GROUP
						|| $parentShare->getShareType() === IShare::TYPE_USERGROUP
						|| $parentShare->getShareType() === IShare::TYPE_USER)
				) {
					continue;
				}

				$owner = $parentShare->getShareOwner();
				if (!isset($ownerViews[$owner])) {
					$ownerViews[$owner] = new View('/' . $owner . '/files');
				}
				$shareId = (int)$parentShare->getId();
				$mount = new SharedMount(
					'\OCA\Files_Sharing\SharedStorage',
					$allMounts,
					[
						'user' => $userId,
						// parent share
						'superShare' => $parentShare,
						// children/component of the superShare
						'groupedShares' => $groupedShares,
						'ownerView' => $ownerViews[$owner],
						'sharingDisabledForUser' => $sharingDisabledForUser
					],
					$loader,
					$view,
					$foldersExistCache,
					$this->eventDispatcher,
					$user,
					$shareId <= $maxValidatedShare,
				);

				$newMaxValidatedShare = max($shareId, $newMaxValidatedShare);

				$event = new ShareMountedEvent($mount);
				$this->eventDispatcher->dispatchTyped($event);

				$mounts[$mount->getMountPoint()]
				= $allMounts[$mount->getMountPoint()] = $mount;
				foreach ($event->getAdditionalMounts() as $additionalMount) {
					$allMounts[$additionalMount->getMountPoint()]
					= $mounts[$additionalMount->getMountPoint()]
						= $additionalMount;
				}
			} catch (Exception $e) {
				$this->logger->error(
					'Error while trying to create shared mount',
					[
						'app' => 'files_sharing',
						'exception' => $e,
					],
				);
			}
		}

		$validShareCache->set($userId, $newMaxValidatedShare, 24 * 60 * 60);

		// array_filter removes the null values from the array
		return array_filter($mounts);
	}

	/**
	 * Filters out shares owned or shared by the user and ones for which the
	 * user has no permissions.
	 *
	 * @param IShare[] $shares
	 * @return IShare[]
	 */
	public function filterShares(array $shares, string $userId): array {
		return array_filter(
			$shares,
			static function (IShare $share) use ($userId) {
				return $share->getPermissions() > 0
					&& $share->getShareOwner() !== $userId
					&& $share->getSharedBy() !== $userId;
			}
		);
	}

	/**
	 * Helper function to retrieve data needed to determine which
	 * IShareProviders need to be queried: IShare::TYPE_*.
	 *
	 * @see IShare::TYPE_* constants
	 *
	 * @param int[] $uniqueRootIds
	 * @return array<int, int[]> Array of the shared node IDs,
	 *  keyed by the share type.
	 * @throws \OCP\DB\Exception
	 */
	public function getShareInfo(IUser $user, array $uniqueRootIds): array {
		$mountOwnerId = $user->getUID();
		// retrieve the share type for the received files
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('file_source', 'share_type')
			->from('share')
			->where(
				$qb->expr()->in(
					'file_source',
					$qb->createNamedParameter(
						$uniqueRootIds,
						IQueryBuilder::PARAM_STR_ARRAY
					)
				)
			)
			->andWhere(
				$qb->expr()->in(
					'share_type',
					$qb->createNamedParameter(
						self::SUPPORTED_SHARE_TYPES,
						IQueryBuilder::PARAM_INT_ARRAY
					)
				)
			)
			->andWhere(
				$qb->expr()->eq(
					'share_with',
					$qb->createNamedParameter($mountOwnerId)
				)
			)
			->groupBy('file_source', 'share_type');
		$cursor = $qb->executeQuery();

		// group IDs of the roots of the mountpoints by type
		$rootIdsByType = [];
		while ($row = $cursor->fetch()) {
			$mappedType = self::TYPE_MAPPING[$row['share_type']] ?? $row['share_type'];
			$rootIdsByType[$mappedType][] = $row['file_source'];
		}
		$cursor->closeCursor();

		return $rootIdsByType;
	}
}
