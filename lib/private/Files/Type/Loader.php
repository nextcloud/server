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

	private const string CACHE_KEY = 'mimetypes';
	private const int CACHE_TTL = 3600;

	/** @psalm-var array<int, string> */
	protected array $mimetypes = [];

	/** @psalm-var array<string, int> */
	protected array $mimetypeIds = [];

	private ICache $cache;

	/**
	 * The cached copy of the table may lack rows added by another process,
	 * so a miss reloads from the database once per instance.
	 */
	private bool $reloaded = false;

	/**
	 * @param IDBConnection $dbConnection
	 * @param ICacheFactory $cacheFactory
	 */
	public function __construct(
		private IDBConnection $dbConnection,
		ICacheFactory $cacheFactory,
	) {
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
		if (!isset($this->mimetypes[$id])) {
			$this->reloadOnMiss();
		}
		return $this->mimetypes[$id] ?? null;
	}

	/**
	 * Get a mimetype ID, adding the mimetype to the DB if it does not exist
	 */
	#[\Override]
	public function getId(string $mimetype): int {
		if (!$this->mimetypeIds) {
			$this->loadMimetypes();
		}
		if (!isset($this->mimetypeIds[$mimetype])) {
			$this->reloadOnMiss();
		}
		if (isset($this->mimetypeIds[$mimetype])) {
			return $this->mimetypeIds[$mimetype];
		}
		return $this->store($mimetype);
	}

	/**
	 * Test if a mimetype exists in the database
	 */
	#[\Override]
	public function exists(string $mimetype): bool {
		if (!$this->mimetypeIds) {
			$this->loadMimetypes();
		}
		if (!isset($this->mimetypeIds[$mimetype])) {
			$this->reloadOnMiss();
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
		$this->reloaded = false;
		$this->cache->remove(self::CACHE_KEY);
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
		$this->cache->remove(self::CACHE_KEY);

		return $mimetypeId;
	}

	/**
	 * Load all mimetypes from the cache, falling back to the DB
	 */
	private function loadMimetypes(): void {
		$cached = $this->cache->get(self::CACHE_KEY);
		if (is_array($cached) && $cached !== []) {
			$this->setMimetypes($cached);
			return;
		}
		$this->loadMimetypesFromDatabase();
	}

	private function reloadOnMiss(): void {
		if ($this->reloaded) {
			return;
		}
		$this->reloaded = true;
		$this->loadMimetypesFromDatabase();
	}

	private function loadMimetypesFromDatabase(): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('id', 'mimetype')
			->from('mimetypes');
		$result = $qb->executeQuery();
		$results = $result->fetchAllAssociative();
		$result->closeCursor();

		$mimetypes = [];
		foreach ($results as $row) {
			$mimetypes[(int)$row['id']] = (string)$row['mimetype'];
		}
		$this->setMimetypes($mimetypes);
		if ($mimetypes !== []) {
			$this->cache->set(self::CACHE_KEY, $mimetypes, self::CACHE_TTL);
		}
	}

	/**
	 * @param array<int, string> $mimetypes
	 */
	private function setMimetypes(array $mimetypes): void {
		$this->mimetypes = [];
		$this->mimetypeIds = [];
		foreach ($mimetypes as $id => $mimetype) {
			$this->mimetypes[(int)$id] = $mimetype;
			$this->mimetypeIds[$mimetype] = (int)$id;
		}
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
