<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use OCP\IUser;

/**
 * @template-extends QBMapper<RecentContact>
 */
class RecentContactMapper extends QBMapper {
	public const TABLE_NAME = 'recent_contact';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME);
	}

	/**
	 * @return RecentContact[]
	 */
	public function findAll(string $uid): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('actor_uid', $qb->createNamedParameter($uid)));

		return $this->findEntities($select);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(string $uid, int $id): RecentContact {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, $qb::PARAM_INT)))
			->andWhere($qb->expr()->eq('actor_uid', $qb->createNamedParameter($uid)));

		return $this->findEntity($select);
	}

	/**
	 * @return RecentContact[]
	 */
	public function findMatch(IUser $user,
		?string $uid,
		?string $email,
		?string $cloudId): array {
		$qb = $this->db->getQueryBuilder();

		$additionalWheres = [];
		if ($uid !== null) {
			$additionalWheres[] = $qb->expr()->eq('uid', $qb->createNamedParameter($uid));
		}
		if ($email !== null) {
			$additionalWheres[] = $qb->expr()->eq('email', $qb->createNamedParameter($email));
		}
		if ($cloudId !== null) {
			$additionalWheres[] = $qb->expr()->eq('federated_cloud_id', $qb->createNamedParameter($cloudId));
		}

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('actor_uid', $qb->createNamedParameter($user->getUID())));

		if (!empty($additionalWheres)) {
			$select->andWhere($select->expr()->orX(...$additionalWheres));
		}
		return $this->findEntities($select);
	}

	public function findLastUpdatedForUserId(string $uid): ?int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('last_contact')
			->from($this->getTableName())
			->where($qb->expr()->eq('actor_uid', $qb->createNamedParameter($uid)))
			->orderBy('last_contact', 'DESC')
			->setMaxResults(1);

		$cursor = $select->executeQuery();
		$row = $cursor->fetch();

		if ($row === false) {
			return null;
		}

		return (int)$row['last_contact'];
	}

	public function cleanUp(int $olderThan): void {
		$qb = $this->db->getQueryBuilder();

		$delete = $qb
			->delete($this->getTableName())
			->where($qb->expr()->lt('last_contact', $qb->createNamedParameter($olderThan)));

		$delete->executeStatement();
	}
}
