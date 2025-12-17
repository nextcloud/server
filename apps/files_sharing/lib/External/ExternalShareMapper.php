<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\External;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Share\IShare;
use Override;

/**
 * @template-extends QBMapper<ExternalShare>
 */
class ExternalShareMapper extends QBMapper {
	private const TABLE_NAME = 'share_external';

	public function __construct(
		IDBConnection $db,
		private readonly IGroupManager $groupManager,
	) {
		parent::__construct($db, self::TABLE_NAME);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function getById(string $id): ExternalShare {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->setMaxResults(1);
		return $this->findEntity($qb);
	}

	/**
	 * Get share by token.
	 *
	 * @throws DoesNotExistException
	 */
	public function getShareByToken(string $token): ExternalShare {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq('share_token', $qb->createNamedParameter($token, IQueryBuilder::PARAM_STR)))
			->setMaxResults(1);
		return $this->findEntity($qb);
	}

	/**
	 * Get share by parent id and user.
	 *
	 * @throws DoesNotExistException
	 */
	public function getUserShare(ExternalShare $parentShare, IUser $user): ExternalShare {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->andX(
				$qb->expr()->eq('parent', $qb->createNamedParameter($parentShare->getId())),
				$qb->expr()->eq('user', $qb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR)),
			));
		return $this->findEntity($qb);
	}

	public function getByMountPointAndUser(string $mountPoint, IUser $user) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->andX(
				$qb->expr()->eq('mountpoint_hash', $qb->createNamedParameter(md5($mountPoint))),
				$qb->expr()->eq('user', $qb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR)),
			));
		return $this->findEntity($qb);
	}

	/**
	 * @return \Generator<ExternalShare>
	 * @throws Exception
	 */
	public function getUserShares(IUser $user): \Generator {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq('user', $qb->createNamedParameter($user->getUID(), IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_USER, IQueryBuilder::PARAM_INT)));

		return $this->yieldEntities($qb);
	}

	public function deleteUserShares(IUser $user): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE_NAME)
			// user field can specify a user or a group
			->where($qb->expr()->eq('user', $qb->createNamedParameter($user->getUID())))
			->andWhere(
				$qb->expr()->orX(
					// delete direct shares
					$qb->expr()->eq('share_type', $qb->expr()->literal(IShare::TYPE_USER)),
					// delete sub-shares of group shares for that user
					$qb->expr()->andX(
						$qb->expr()->eq('share_type', $qb->expr()->literal(IShare::TYPE_GROUP)),
						$qb->expr()->neq('parent', $qb->expr()->literal(-1)),
					)
				)
			);
		$qb->executeStatement();
	}

	/**
	 * @throws Exception
	 */
	public function deleteGroupShares(IGroup $group): void {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq('user', $qb->createNamedParameter($group->getGID())))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_GROUP)));

		$this->yieldEntities($qb);

		$delete = $this->db->getQueryBuilder();
		$delete->delete(self::TABLE_NAME)
			->where(
				$qb->expr()->orX(
					$qb->expr()->eq('id', $qb->createParameter('share_id')),
					$qb->expr()->eq('parent', $qb->createParameter('share_parent_id'))
				)
			);

		foreach ($this->yieldEntities($qb) as $share) {
			$delete->setParameter('share_id', $share->getId());
			$delete->setParameter('share_parent_id', $share->getId());
			$delete->executeStatement();
		}
	}

	/**
	 * @return \Generator<ExternalShare>
	 */
	public function getAllShares(): \Generator {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME);

		return $this->yieldEntities($qb);
	}

	/**
	 * Return a list of shares for the user.
	 *
	 * @psalm-param IShare::STATUS_PENDING|IShare::STATUS_ACCEPTED|null $status Filter share by their status or return all shares of the user if null.
	 * @return list<ExternalShare> list of open server-to-server shares
	 * @throws Exception
	 */
	public function getShares(IUser $user, ?int $status): array {
		// Not allowing providing a user here,
		// as we only want to retrieve shares for the current user.
		$groups = $this->groupManager->getUserGroups($user);
		$userGroups = [];
		foreach ($groups as $group) {
			$userGroups[] = $group->getGID();
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('share_external')
			->where(
				$qb->expr()->orX(
					$qb->expr()->eq('user', $qb->createNamedParameter($user->getUID())),
					$qb->expr()->in(
						'user',
						$qb->createNamedParameter($userGroups, IQueryBuilder::PARAM_STR_ARRAY)
					)
				)
			)
			->orderBy('id', 'ASC');

		$shares = $this->findEntities($qb);

		// remove parent group share entry if we have a specific user share entry for the user
		$toRemove = [];
		foreach ($shares as $share) {
			if ($share->getShareType() === IShare::TYPE_GROUP && $share->getParent() !== '-1') {
				$toRemove[] = $share->getParent();
			}
		}
		$shares = array_filter($shares, function (ExternalShare $share) use ($toRemove): bool {
			return !in_array($share->getId(), $toRemove, true);
		});

		if (!is_null($status)) {
			$shares = array_filter($shares, function (ExternalShare $share) use ($status): bool {
				return $share->getAccepted() === $status;
			});
		}
		return array_values($shares);
	}

	public function deleteAll(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('share_external')
			->executeStatement();
	}

	#[Override]
	public function delete(Entity $entity): ExternalShare {
		/** @var ExternalShare $share */
		$share = $entity;
		$qb = $this->db->getQueryBuilder();

		$qb->delete(self::TABLE_NAME)
			// delete the share itself
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
			// delete all child in case of a group share
			->orWhere($qb->expr()->eq('parent', $qb->createNamedParameter($share->getId())))
			->executeStatement();
		return $share;
	}

	public function getShareByRemoteIdAndToken(string $id, mixed $token): ?ExternalShare {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('remote_id', $qb->createNamedParameter($id)),
					$qb->expr()->eq('share_token', $qb->createNamedParameter($token))
				)
			);

		try {
			return $this->findEntity($qb);
		} catch (Exception) {
			return null;
		}
	}
}
