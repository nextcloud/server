<?php

namespace OC\Repair;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class RepairMimeTypeJob extends QueuedJob {
	private IDBConnection $connection;
	private LoggerInterface $logger;

	public function __construct(ITimeFactory $time, IDBConnection $connection, LoggerInterface $logger) {
		parent::__construct($time);
		$this->connection = $connection;
		$this->logger = $logger;
	}

	protected function run($argument) {
		$storageId = (int)$argument['storageId'];
		$mimetypes = $argument['mimetypes'];
		$this->updateMimetypesForStorage($storageId, $mimetypes);
	}

	private function updateMimetypesForStorage(int $storageId, array $updatedMimetypes) {
		$query = $this->connection->getQueryBuilder();
		$query->select('id')
			->from('mimetypes')
			->where($query->expr()->eq('mimetype', $query->createParameter('mimetype'), IQueryBuilder::PARAM_INT));
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('mimetypes')
			->setValue('mimetype', $insert->createParameter('mimetype'));

		if (empty($this->folderMimeTypeId)) {
			$query->setParameter('mimetype', 'httpd/unix-directory');
			$result = $query->execute();
			$this->folderMimeTypeId = (int)$result->fetchOne();
			$result->closeCursor();
		}

		$update = $this->connection->getQueryBuilder();
		$update->update('filecache')
			->set('mimetype', $update->createParameter('mimetype'))
			->where($update->expr()->eq('storage', $update->createParameter('storage'), IQueryBuilder::PARAM_INT))
			->andWhere($update->expr()->neq('mimetype', $update->createParameter('mimetype'), IQueryBuilder::PARAM_INT))
			->andWhere($update->expr()->neq('mimetype', $update->createParameter('folder'), IQueryBuilder::PARAM_INT))
			->andWhere($update->expr()->iLike('name', $update->createParameter('name')))
			->setParameter('folder', $this->folderMimeTypeId)
			->setParameter('storage', $storageId);

		$count = 0;
		foreach ($updatedMimetypes as $extension => $mimetype) {
			// get target mimetype id
			$query->setParameter('mimetype', $mimetype);
			$result = $query->execute();
			$mimetypeId = (int)$result->fetchOne();
			$result->closeCursor();

			if (!$mimetypeId) {
				// insert mimetype
				$insert->setParameter('mimetype', $mimetype);
				$insert->execute();
				$mimetypeId = $insert->getLastInsertId();
			}

			// change mimetype for files with x extension
			$update->setParameter('mimetype', $mimetypeId)
				->setParameter('name', '%' . $this->connection->escapeLikeParameter('.' . $extension));
			$count = $update->execute();
			$this->logger->info('Updated storage ' . $storageId . ' with ' . $count . ' file entry mimetypes for ' . $extension . ' to ' . $mimetype);
		}
	}
}
