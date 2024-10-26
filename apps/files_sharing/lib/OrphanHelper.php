<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing;

use OC\User\NoUserException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;

class OrphanHelper {
	public function __construct(
		private IDBConnection $connection,
		private IRootFolder $rootFolder,
	) {
	}

	public function isShareValid(string $owner, int $fileId): bool {
		try {
			$userFolder = $this->rootFolder->getUserFolder($owner);
		} catch (NoUserException $e) {
			return false;
		}
		$node = $userFolder->getFirstNodeById($fileId);
		return $node !== null;
	}

	/**
	 * @param int[] $ids
	 * @return void
	 */
	public function deleteShares(array $ids): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete('share')
			->where($query->expr()->in('id', $query->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));
		$query->executeStatement();
	}

	public function fileExists(int $fileId): bool {
		$query = $this->connection->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
		return $query->executeQuery()->fetchOne() !== false;
	}

	/**
	 * @return \Traversable<int, array{id: int, owner: string, fileid: int, target: string}>
	 */
	public function getAllShares() {
		$query = $this->connection->getQueryBuilder();
		$query->select('id', 'file_source', 'uid_owner', 'file_target')
			->from('share')
			->where($query->expr()->eq('item_type', $query->createNamedParameter('file')))
			->orWhere($query->expr()->eq('item_type', $query->createNamedParameter('folder')));
		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			yield [
				'id' => (int)$row['id'],
				'owner' => (string)$row['uid_owner'],
				'fileid' => (int)$row['file_source'],
				'target' => (string)$row['file_target'],
			];
		}
	}
}
