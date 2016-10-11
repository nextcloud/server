<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Type;

use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * Mimetype database loader
 *
 * @package OC\Files\Type
 */
class Loader implements IMimeTypeLoader {

	/** @var IDBConnection */
	private $dbConnection;

	/** @var array [id => mimetype] */
	protected $mimetypes;

	/** @var array [mimetype => id] */
	protected $mimetypeIds;

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
	 *
	 * @param int $id
	 * @return string|null
	 */
	public function getMimetypeById($id) {
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
	 *
	 * @param string $mimetype
	 * @return int
	 */
	public function getId($mimetype) {
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
	 *
	 * @param string $mimetype
	 * @return bool
	 */
	public function exists($mimetype) {
		if (!$this->mimetypeIds) {
			$this->loadMimetypes();
		}
		return isset($this->mimetypeIds[$mimetype]);
	}

	/**
	 * Clear all loaded mimetypes, allow for re-loading
	 */
	public function reset() {
		$this->mimetypes = [];
		$this->mimetypeIds = [];
	}

	/**
	 * Store a mimetype in the DB
	 *
	 * @param string $mimetype
	 * @param int inserted ID
	 */
	protected function store($mimetype) {
		try {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb->insert('mimetypes')
				->values([
					'mimetype' => $qb->createNamedParameter($mimetype)
				]);
			$qb->execute();
		} catch (UniqueConstraintViolationException $e) {
			// something inserted it before us
		}

		$fetch = $this->dbConnection->getQueryBuilder();
		$fetch->select('id')
			->from('mimetypes')
			->where(
				$fetch->expr()->eq('mimetype', $fetch->createNamedParameter($mimetype)
			));
		$row = $fetch->execute()->fetch();

		$this->mimetypes[$row['id']] = $mimetype;
		$this->mimetypeIds[$mimetype] = $row['id'];
		return $row['id'];
	}

	/**
	 * Load all mimetypes from DB
	 */
	private function loadMimetypes() {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('id', 'mimetype')
			->from('mimetypes');
		$results = $qb->execute()->fetchAll();

		foreach ($results as $row) {
			$this->mimetypes[$row['id']] = $row['mimetype'];
			$this->mimetypeIds[$row['mimetype']] = $row['id'];
		}
	}

	/**
	 * Update filecache mimetype based on file extension
	 *
	 * @param string $ext file extension
	 * @param int $mimetypeId
	 * @return int number of changed rows
	 */
	public function updateFilecache($ext, $mimetypeId) {
		$update = $this->dbConnection->getQueryBuilder();
		$update->update('filecache')
			->set('mimetype', $update->createNamedParameter($mimetypeId))
			->where($update->expr()->neq(
				'mimetype', $update->createNamedParameter($mimetypeId)
			))
			->andWhere($update->expr()->like(
				$update->createFunction('LOWER(`name`)'), $update->createNamedParameter($ext)
			));
		return $update->execute();
	}

}
