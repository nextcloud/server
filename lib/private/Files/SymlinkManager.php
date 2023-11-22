<?php
/**
 * @copyright Copyright (c) 2023, Tamino Bauknecht <dev@tb6.eu>
 *
 * @author Tamino Bauknecht <dev@tb6.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\Files;

use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;

/**
 * Class to manage symlink representation on server
 */
class SymlinkManager {
	/**
	 * @var \OCP\IDBConnection
	 */
	protected $connection;

	/**
	 * @var string
	 */
	protected const TABLE_NAME = 'symlinks';

	/**
	 * @param IStorage $storage
	 */
	public function __construct() {
		$this->connection = \OC::$server->get(IDBConnection::class);
	}

	/**
	 * Check if given node is a symlink
	 *
	 * @param \OCP\Files\FileInfo $node
	 *
	 * @return bool
	 */
	public function isSymlink($node) {
		return $this->getId($node) === false;
	}

	/**
	 * Store given node in database
	 *
	 * @param \OCP\Files\FileInfo $node
	 */
	public function storeSymlink($node) {
		if ($this->isSymlink($node)) {
			$this->insertSymlink($node);
		} else {
			$this->updateSymlink($node);
		}
	}

	/**
	 * Delete given node from database
	 *
	 * @param \OCP\Files\FileInfo $node
	 *
	 * @return bool
	 */
	public function deleteSymlink($node) {
		$id = $this->getId($node);
		if ($id === false) {
			return false;
		}

		return $this->deleteSymlinkById($id);
	}

	/**
	 * Delete all symlinks that have no file representation in filesystem.
	 * Optionally, a path can be given to only purge symlinks that are recursively located in the path.
	 *
	 * @param string $path
	 */
	public function purgeSymlink($path = '/') {
		$path = rtrim($path, '/');
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from(self::TABLE_NAME)
			->where($query->expr()->like('storage', $query->createNamedParameter($this->connection->escapeLikeParameter($path) . '/%')));
		$result = $query->executeQuery();

		while ($row = $result->fetch()) {
			if (!\OC\Files\Filesystem::file_exists($row['path'])) {
				$this->deleteSymlinkById($row['id']);
			}
		}
	}

	/**
	 * @param \OCP\Files\FileInfo $node
	 *
	 * @return int|false
	 */
	private function getId($node) {
		$name = $this->getNameFromNode($node);
		$storageId = $this->getStorageIdFromNode($node);
		$path = $this->getPathFromNode($node);

		$query = $this->connection->getQueryBuilder();
		$query->select('id')
			->from(self::TABLE_NAME)
			->where($query->expr()->eq('storage', $query->createNamedParameter($storageId)))
			->andWhere($query->expr()->eq('path', $query->createNamedParameter($path)))
			->andWhere($query->expr()->eq('name', $query->createNamedParameter($name)));
		$result = $query->executeQuery();

		if ($result->rowCount() > 1) {
			throw new \OCP\DB\Exception("Node ('$name', '$storageId', '$path') is not unique in database!");
		}

		return $result->fetchOne();
	}

	/**
	 * @param \OCP\Files\Node $node
	 */
	private function updateSymlink($node) {
		$name = $this->getNameFromNode($node);
		$storageId = $this->getStorageIdFromNode($node);
		$path = $this->getPathFromNode($node);
		$lastUpdated = $this->getLastUpdatedFromNode($node);

		$query = $this->connection->getQueryBuilder();
		$query->update(self::TABLE_NAME)
			->set('name', $query->createNamedParameter($name))
			->set('storage', $query->createNamedParameter($storageId))
			->set('path', $query->createNamedParameter($path))
			->set('last_updated', $query->createNamedParameter($lastUpdated));
	}

	/**
	 * @param \OCP\Files\FileInfo $node
	 */
	private function insertSymlink($node) {
		$name = $this->getNameFromNode($node);
		$storageId = $this->getStorageIdFromNode($node);
		$path = $this->getPathFromNode($node);
		$lastUpdated = $this->getLastUpdatedFromNode($node);

		$query = $this->connection->getQueryBuilder();
		$query->insert(self::TABLE_NAME)
			->setValue('name', $query->createNamedParameter($name))
			->setValue('storage', $query->createNamedParameter($storageId))
			->setValue('path', $query->createNamedParameter($path))
			->setValue('last_updated', $query->createNamedParameter($lastUpdated));
	}

	/**
	 * @param \OCP\Files\FileInfo $node
	 *
	 * @return bool
	 */
	private function deleteSymlinkById($id) {
		$query = $this->connection->getQueryBuilder();
		$query->delete(self::TABLE_NAME)
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$rowsChanged = $query->executeStatement();
		if ($rowsChanged > 1) {
			throw new \OCP\DB\Exception("Too many symlink rows deleted!");
		}
		return $rowsChanged == 1;
	}

	/**
	 * @param \OCP\Files\FileInfo $node
	 */
	private function getNameFromNode($node) {
		return $node->getName();
	}

	/**
	 * @param \OCP\Files\FileInfo $node
	 */
	private function getStorageIdFromNode($node) {
		return $node->getStorage()->getId();
	}

	/**
	 * @param \OCP\Files\FileInfo $node
	 */
	private function getPathFromNode($node) {
		return $node->getPath();
	}

	/**
	 * @param \OCP\Files\FileInfo $node
	 */
	private function getLastUpdatedFromNode($node) {
		return $node->getMtime();
	}
}
