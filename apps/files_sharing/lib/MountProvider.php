<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use OC\Files\View;
use OCA\Files_Sharing\Event\ShareMountedEvent;
use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class MountProvider implements IMountProvider {
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
		$shares = $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_USER, null, -1);
		$shares = array_merge($shares, $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_GROUP, null, -1));
		$shares = array_merge($shares, $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_CIRCLE, null, -1));
		$shares = array_merge($shares, $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_ROOM, null, -1));
		$shares = array_merge($shares, $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_DECK, null, -1));
		$shares = array_merge($shares, $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_SCIENCEMESH, null, -1));


		// filter out excluded shares and group shares that includes self
		$shares = array_filter($shares, function (IShare $share) use ($user) {
			return $share->getPermissions() > 0 && $share->getShareOwner() !== $user->getUID();
		});

		$superShares = $this->buildSuperShares($shares, $user);

		$mounts = $this->mountManager->getAll();
		$view = new View('/' . $user->getUID() . '/files');
		$ownerViews = [];
		$sharingDisabledForUser = $this->shareManager->sharingDisabledForUser($user->getUID());
		/** @var CappedMemoryCache<bool> $folderExistCache */
		$foldersExistCache = new CappedMemoryCache();

		$validShareCache = $this->cacheFactory->createLocal('share-valid-mountpoint-max');
		$maxValidatedShare = $validShareCache->get($user->getUID()) ?? 0;
		$newMaxValidatedShare = $maxValidatedShare;

		foreach ($superShares as $share) {
			try {
				/** @var IShare $parentShare */
				$parentShare = $share[0];

				if ($parentShare->getStatus() !== IShare::STATUS_ACCEPTED &&
					($parentShare->getShareType() === IShare::TYPE_GROUP ||
						$parentShare->getShareType() === IShare::TYPE_USERGROUP ||
						$parentShare->getShareType() === IShare::TYPE_USER)) {
					continue;
				}

				$owner = $parentShare->getShareOwner();
				if (!isset($ownerViews[$owner])) {
					$ownerViews[$owner] = new View('/' . $parentShare->getShareOwner() . '/files');
				}
				$shareId = (int)$parentShare->getId();
				$mount = new SharedMount(
					'\OCA\Files_Sharing\SharedStorage',
					$mounts,
					[
						'user' => $user->getUID(),
						// parent share
						'superShare' => $parentShare,
						// children/component of the superShare
						'groupedShares' => $share[1],
						'ownerView' => $ownerViews[$owner],
						'sharingDisabledForUser' => $sharingDisabledForUser
					],
					$loader,
					$view,
					$foldersExistCache,
					$this->eventDispatcher,
					$user,
					($shareId <= $maxValidatedShare)
				);

				$newMaxValidatedShare = max($shareId, $newMaxValidatedShare);

				$event = new ShareMountedEvent($mount);
				$this->eventDispatcher->dispatchTyped($event);

				$mounts[$mount->getMountPoint()] = $mount;
				foreach ($event->getAdditionalMounts() as $additionalMount) {
					$mounts[$additionalMount->getMountPoint()] = $additionalMount;
				}
			} catch (\Exception $e) {
				$this->logger->error(
					'Error while trying to create shared mount',
					[
						'app' => 'files_sharing',
						'exception' => $e,
					],
				);
			}
		}

		$validShareCache->set($user->getUID(), $newMaxValidatedShare, 24 * 60 * 60);

		// array_filter removes the null values from the array
		return array_values(array_filter($mounts));
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
			if (!isset($tmp[$share->getNodeId()])) {
				$tmp[$share->getNodeId()] = [];
			}
			$tmp[$share->getNodeId()][] = $share;
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

		return array_values($result);
	}

	/**
	 * Build super shares (virtual share) by grouping them by node id and target,
	 * then for each group compute the super share and return it along with the matching
	 * grouped shares. The most permissive permissions are used based on the permissions
	 * of all shares within the group.
	 *
	 * @param IShare[] $allShares
	 * @param IUser $user user
	 * @return array Tuple of [superShare, groupedShares]
	 */
	private function buildSuperShares(array $allShares, IUser $user) {
		$result = [];

		$groupedShares = $this->groupShares($allShares);

		/** @var IShare[] $shares */
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

			// Gather notes from all the shares.
			// Since these are readly available here, storing them
			// enables the DAV FilesPlugin to avoid executing many
			// DB queries to retrieve the same information.
			$allNotes = implode("\n", array_map(function ($sh) {
				return $sh->getNote();
			}, $shares));
			$superShare->setNote($allNotes);

			// use most permissive permissions
			// this covers the case where there are multiple shares for the same
			// file e.g. from different groups and different permissions
			$superPermissions = 0;
			$superAttributes = $this->shareManager->newShare()->newAttributes();
			$status = IShare::STATUS_PENDING;
			foreach ($shares as $share) {
				$superPermissions |= $share->getPermissions();
				$status = max($status, $share->getStatus());
				// update permissions
				$superPermissions |= $share->getPermissions();

				// update share permission attributes
				$attributes = $share->getAttributes();
				if ($attributes !== null) {
					foreach ($attributes->toArray() as $attribute) {
						if ($superAttributes->getAttribute($attribute['scope'], $attribute['key']) === true) {
							// if super share attribute is already enabled, it is most permissive
							continue;
						}
						// update supershare attributes with subshare attribute
						$superAttributes->setAttribute($attribute['scope'], $attribute['key'], $attribute['value']);
					}
				}

				// adjust target, for database consistency if needed
				if ($share->getTarget() !== $superShare->getTarget()) {
					$share->setTarget($superShare->getTarget());
					try {
						$this->shareManager->moveShare($share, $user->getUID());
					} catch (\InvalidArgumentException $e) {
						// ignore as it is not important and we don't want to
						// block FS setup

						// the subsequent code anyway only uses the target of the
						// super share

						// such issue can usually happen when dealing with
						// null groups which usually appear with group backend
						// caching inconsistencies
						$this->logger->debug(
							'Could not adjust share target for share ' . $share->getId() . ' to make it consistent: ' . $e->getMessage(),
							['app' => 'files_sharing']
						);
					}
				}
				if (!is_null($share->getNodeCacheEntry())) {
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
}
