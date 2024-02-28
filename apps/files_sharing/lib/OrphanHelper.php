<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
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

namespace OCA\Files_Sharing;

use OC\User\NoUserException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;

class OrphanHelper {
	private IDBConnection $connection;
	private IRootFolder $rootFolder;

	public function __construct(
		IDBConnection $connection,
		IRootFolder $rootFolder
	) {
		$this->connection = $connection;
		$this->rootFolder = $rootFolder;
	}

	public function isShareValid(string $owner, int $fileId): bool {
		try {
			$userFolder = $this->rootFolder->getUserFolder($owner);
		} catch (NoUserException $e) {
			return false;
		}
		$nodes = $userFolder->getById($fileId);
		return count($nodes) > 0;
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
