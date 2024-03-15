<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Rello <Rello@users.noreply.github.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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
namespace OC\Files\Type;

use OC\DB\Exceptions\DbalException;
use OCP\AppFramework\Db\TTransactional;
use OCP\DB\Exception as DBException;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;

/**
 * Mimetype database loader
 *
 * @package OC\Files\Type
 */
class Loader implements IMimeTypeLoader {
	use TTransactional;

	private IDBConnection $dbConnection;

	/** @psalm-var array<int, string> */
	protected array $mimetypes;

	/** @psalm-var array<string, int> */
	protected array $mimetypeIds;

	/**
	 * @param IDBConnection $dbConnection
	 */
	public function __construct(IDBConnection $dbConnection) {
		$this->dbConnection = $dbConnection;
		$this->mimetypes = [];
		$this->mimetypeIds = [];
	}

	/**
	 * Get a mimetype from its ID
	 */
	public function getMimetypeById(int $id): ?string {
		if (!$this->mimetypes) {
			$this->loadMimetypes();
		}
		if (isset($this->mimetypes[$id])) {
			return $this->mimetypes[$id];
		}
		return null;
	}

	/**
	 * Get a mimetype ID, adding the mimetype to the DB if it does not exist
	 */
	public function getId(string $mimetype): int {
		if (!$this->mimetypeIds) {
			$this->loadMimetypes();
		}
		if (isset($this->mimetypeIds[$mimetype])) {
			return $this->mimetypeIds[$mimetype];
		}
		return $this->store($mimetype);
	}

	/**
	 * Test if a mimetype exists in the database
	 */
	public function exists(string $mimetype): bool {
		if (!$this->mimetypeIds) {
			$this->loadMimetypes();
		}
		return isset($this->mimetypeIds[$mimetype]);
	}

	/**
	 * Clear all loaded mimetypes, allow for re-loading
	 */
	public function reset(): void {
		$this->mimetypes = [];
		$this->mimetypeIds = [];
	}

	/**
	 * Store a mimetype in the DB
	 *
	 * @param string $mimetype
	 * @return int inserted ID
	 */
	protected function store(string $mimetype): int {
		try {
			$mimetypeId = $this->atomic(function () use ($mimetype) {
				$insert = $this->dbConnection->getQueryBuilder();
				$insert->insert('mimetypes')
					->values([
						'mimetype' => $insert->createNamedParameter($mimetype)
					])
					->executeStatement();
				return $insert->getLastInsertId();
			}, $this->dbConnection);
		} catch (DbalException $e) {
			if ($e->getReason() !== DBException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}

			$qb = $this->dbConnection->getQueryBuilder();
			$qb->select('id')
				->from('mimetypes')
				->where($qb->expr()->eq('mimetype', $qb->createNamedParameter($mimetype)));
			$result = $qb->executeQuery();
			$id = $result->fetchOne();
			$result->closeCursor();
			if ($id === false) {
				throw new \Exception("Database threw an unique constraint on inserting a new mimetype, but couldn't return the ID for this very mimetype");
			}

			$mimetypeId = (int) $id;
		}

		$this->mimetypes[$mimetypeId] = $mimetype;
		$this->mimetypeIds[$mimetype] = $mimetypeId;
		return $mimetypeId;
	}

	/**
	 * Load all mimetypes from DB
	 */
	private function loadMimetypes(): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('id', 'mimetype')
			->from('mimetypes');

		$result = $qb->executeQuery();
		$results = $result->fetchAll();
		$result->closeCursor();

		foreach ($results as $row) {
			$this->mimetypes[(int) $row['id']] = $row['mimetype'];
			$this->mimetypeIds[$row['mimetype']] = (int) $row['id'];
		}
	}

	/**
	 * Update filecache mimetype based on file extension
	 *
	 * @return int number of changed rows
	 */
	public function updateFilecache(string $ext, int $mimeTypeId): int {
		$folderMimeTypeId = $this->getId('httpd/unix-directory');
		$update = $this->dbConnection->getQueryBuilder();
		$update->update('filecache')
			->set('mimetype', $update->createNamedParameter($mimeTypeId))
			->where($update->expr()->neq(
				'mimetype', $update->createNamedParameter($mimeTypeId)
			))
			->andWhere($update->expr()->neq(
				'mimetype', $update->createNamedParameter($folderMimeTypeId)
			))
			->andWhere($update->expr()->like(
				$update->func()->lower('name'),
				$update->createNamedParameter('%' . $this->dbConnection->escapeLikeParameter('.' . $ext))
			));
		return $update->execute();
	}
}
