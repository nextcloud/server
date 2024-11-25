<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Preview;

use OC\Preview\Storage\Root;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;

class BackgroundCleanupJob extends TimedJob {
	/** @var IDBConnection */
	private $connection;

	/** @var Root */
	private $previewFolder;

	/** @var bool */
	private $isCLI;

	/** @var IMimeTypeLoader */
	private $mimeTypeLoader;

	public function __construct(ITimeFactory $timeFactory,
		IDBConnection $connection,
		Root $previewFolder,
		IMimeTypeLoader $mimeTypeLoader,
		bool $isCLI) {
		parent::__construct($timeFactory);
		// Run at most once an hour
		$this->setInterval(60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);

		$this->connection = $connection;
		$this->previewFolder = $previewFolder;
		$this->isCLI = $isCLI;
		$this->mimeTypeLoader = $mimeTypeLoader;
	}

	public function run($argument) {
		foreach ($this->getDeletedFiles() as $fileId) {
			try {
				$preview = $this->previewFolder->getFolder((string)$fileId);
				$preview->delete();
			} catch (NotFoundException $e) {
				// continue
			} catch (NotPermittedException $e) {
				// continue
			}
		}
	}

	private function getDeletedFiles(): \Iterator {
		yield from $this->getOldPreviewLocations();
		yield from $this->getNewPreviewLocations();
	}

	private function getOldPreviewLocations(): \Iterator {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('a.name')
			->from('filecache', 'a')
			->leftJoin('a', 'filecache', 'b', $qb->expr()->eq(
				$qb->expr()->castColumn('a.name', IQueryBuilder::PARAM_INT), 'b.fileid'
			))
			->where(
				$qb->expr()->isNull('b.fileid')
			)->andWhere(
				$qb->expr()->eq('a.parent', $qb->createNamedParameter($this->previewFolder->getId()))
			)->andWhere(
				$qb->expr()->like('a.name', $qb->createNamedParameter('__%'))
			);

		if (!$this->isCLI) {
			$qb->setMaxResults(10);
		}

		$cursor = $qb->execute();

		while ($row = $cursor->fetch()) {
			yield $row['name'];
		}

		$cursor->closeCursor();
	}

	private function getNewPreviewLocations(): \Iterator {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('path', 'mimetype')
			->from('filecache')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($this->previewFolder->getId())));
		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === null) {
			return [];
		}

		/*
		 * This lovely like is the result of the way the new previews are stored
		 * We take the md5 of the name (fileid) and split the first 7 chars. That way
		 * there are not a gazillion files in the root of the preview appdata.
		 */
		$like = $this->connection->escapeLikeParameter($data['path']) . '/_/_/_/_/_/_/_/%';

		/*
		 * Deleting a file will not delete related previews right away.
		 *
		 * A delete request is usually an HTTP request.
		 * The preview deleting is done by a background job to avoid timeouts.
		 *
		 * Previews for a file are stored within a folder in appdata_/preview using the fileid as folder name.
		 * Preview folders in oc_filecache are identified by a.storage, a.path (cf. $like) and a.mimetype.
		 *
		 * To find preview folders to delete, we query oc_filecache for a preview folder in app data, matching the preview folder structure
		 * and use the name to left join oc_filecache on a.name = b.fileid. A left join returns all rows from the left table (a),
		 * even if there are no matches in the right table (b).
		 *
		 * If the related file is deleted, b.fileid will be null and the preview folder can be deleted.
		 */
		$qb = $this->connection->getQueryBuilder();
		$qb->select('a.name')
			->from('filecache', 'a')
			->leftJoin('a', 'filecache', 'b', $qb->expr()->eq(
				$qb->expr()->castColumn('a.name', IQueryBuilder::PARAM_INT), 'b.fileid'
			))
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('a.storage', $qb->createNamedParameter($this->previewFolder->getStorageId())),
					$qb->expr()->isNull('b.fileid'),
					$qb->expr()->like('a.path', $qb->createNamedParameter($like)),
					$qb->expr()->eq('a.mimetype', $qb->createNamedParameter($this->mimeTypeLoader->getId('httpd/unix-directory')))
				)
			);

		if (!$this->isCLI) {
			$qb->setMaxResults(10);
		}

		$cursor = $qb->execute();

		while ($row = $cursor->fetch()) {
			yield $row['name'];
		}

		$cursor->closeCursor();
	}
}
