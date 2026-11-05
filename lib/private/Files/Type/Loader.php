<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Type;

use OC\DB\Exceptions\DbalException;
use OCP\AppFramework\Db\TTransactional;
use OCP\DB\Exception as DBException;
use OCP\Files\IMimeTypeLoader;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;

/**
 * Mimetype database loader
 *
 * @package OC\Files\Type
 */
class Loader implements IMimeTypeLoader {
	use TTransactional;

	/** @psalm-var array<int, string> */
	protected array $mimetypes;

	/** @psalm-var array<string, int> */
	protected array $mimetypeIds;

	private bool $loadedFromDatabase = false;
	private ICache $cache;

	public function __construct(
		private IDBConnection $dbConnection,
		ICacheFactory $cacheFactory,
	) {
		$this->mimetypes = [];
		$this->mimetypeIds = [];
		$this->cache = $cacheFactory->createLocal('mimetypes');
	}

	/**
	 * Get a mimetype from its ID
	 */
	#[\Override]
	public function getMimetypeById(int $id): ?string {
		if (!$this->mimetypes) {
			$this->loadMimetypes();
		}
		if (!isset($this->mimetypes[$id]) && !$this->loadedFromDatabase) {
			$this->loadMimetypes(true);
		}
		return $this->mimetypes[$id] ?? null;
	}

	/**
	 * Get a mimetype ID, adding the mimetype to the DB if it does not exist
	 */
	#[\Override]
	public function getId(string $mimetype): int {
		if (!$this->exists($mimetype)) {
			return $this->store($mimetype);
		}
		return $this->mimetypeIds[$mimetype];
	}

	/**
	 * Test if a mimetype exists in the database
	 */
	#[\Override]
	public function exists(string $mimetype): bool {
		if (!$this->mimetypeIds) {
			$this->loadMimetypes();
		}
		if (!isset($this->mimetypeIds[$mimetype]) && !$this->loadedFromDatabase) {
			$this->loadMimetypes(true);
		}
		return isset($this->mimetypeIds[$mimetype]);
	}

	/**
	 * Clear all loaded mimetypes, allow for re-loading
	 */
	#[\Override]
	public function reset(): void {
		$this->mimetypes = [];
		$this->mimetypeIds = [];
		$this->loadedFromDatabase = false;
		$this->cache->remove('mimetypes');
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

			$mimetypeId = (int)$id;
		}

		$this->mimetypes[$mimetypeId] = $mimetype;
		$this->mimetypeIds[$mimetype] = $mimetypeId;
		$this->cache->remove('mimetypes');
		return $mimetypeId;
	}

	/**
	 * Load all mimetypes, from the cache unless $fromDatabase is set. Mimetypes
	 * are only ever added, so the cache can only miss new ones, which are
	 * then loaded from the database.
	 */
	private function loadMimetypes(bool $fromDatabase = false): void {
		$mimetypes = $fromDatabase ? null : $this->cache->get('mimetypes');
		if (!is_array($mimetypes)) {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb->select('id', 'mimetype')
				->from('mimetypes');
			$result = $qb->executeQuery();
			$mimetypes = [];
			foreach ($result->fetchAllAssociative() as $row) {
				$mimetypes[(int)$row['id']] = (string)$row['mimetype'];
			}
			$result->closeCursor();
			$this->cache->set('mimetypes', $mimetypes);
			$this->loadedFromDatabase = true;
		}

		$this->mimetypes = $mimetypes;
		$this->mimetypeIds = array_flip($mimetypes);
	}

	/**
	 * Update filecache mimetype based on file extension
	 *
	 * @return int number of changed rows
	 */
	#[\Override]
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
		return $update->executeStatement();
	}
}
