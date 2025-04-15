<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Db;

use OC\Async\Enum\ProcessStatus;
use OC\Async\Exceptions\ProcessNotFoundException;
use OC\Async\Model\Process;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<Process>
 */
class ProcessMapper extends QBMapper {
	public const TABLE = 'async_process';

	public function __construct(
		IDBConnection $db,
		private LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, Process::class);
	}

	/**
	 * @return Process[]
	 */
	public function getByStatus(ProcessStatus $status): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('status', $qb->createNamedParameter($status->value, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}

	/**
	 * @return Process[]
	 */
	public function getBySession(string $sessionToken): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('session_token', $qb->createNamedParameter($sessionToken)))
			->orderBy('id', 'asc');

		return $this->findEntities($qb);
	}

	/**
	 * @return string[]
	 */
	public function getSessionOnStandBy(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->selectDistinct('session_token')
			->from($this->getTableName())
			->where($qb->expr()->eq('status', $qb->createNamedParameter(ProcessStatus::STANDBY->value, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->notIn('status', $qb->createNamedParameter([ProcessStatus::RUNNING->value, ProcessStatus::BLOCKER->value], IQueryBuilder::PARAM_INT_ARRAY)));
		$result = $qb->executeQuery();

		$sessions = [];
		while ($row = $result->fetch()) {
			$sessions[] = $row['session_token'];
		}
		$result->closeCursor();

		return $sessions;
	}

	public function getByToken(string $token): Process {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new ProcessNotFoundException('no process found');
		}
	}

	public function updateStatus(Process $process, ?ProcessStatus $prevStatus = null, string $lockToken = ''): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('status', $qb->createNamedParameter($process->getStatus()))
	 	    ->where($qb->expr()->andX(
				$qb->expr()->eq('session_token', $qb->createNamedParameter($process->getSessionToken())),
				$qb->expr()->eq('token', $qb->createNamedParameter($process->getToken()))
			)
			);

		// if process contain a lockToken, we conflict with the stored one
		if ($process->getLockToken() !== '') {
			$qb->andWhere($qb->expr()->eq('lock_token', $qb->createNamedParameter($process->getLockToken())));
		}

		// if status is updated to success or error, we store result (or exception)
		if (in_array($process->getProcessStatus(), [ProcessStatus::SUCCESS, ProcessStatus::ERROR], true)) {
			try {
				$qb->set('result', $qb->createNamedParameter(json_encode($process->getResult(), JSON_THROW_ON_ERROR)));
			} catch (\JsonException $e) {
				$this->logger->warning('could not json_encode process result', ['exception' => $e]);
			}
		}

		if ($lockToken !== '') {
			$qb->set('lock_token', $qb->createNamedParameter($lockToken));
		}

		if ($prevStatus !== null) {
			$qb->andWhere($qb->expr()->eq('status', $qb->createNamedParameter($prevStatus->value, IQueryBuilder::PARAM_INT)));
		}

		return ($qb->executeStatement() === 1);
	}

	public function updateSessionStatus(string $sessionToken, ProcessStatus $status, ?ProcessStatus $prevStatus = null): int {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('status', $qb->createNamedParameter($status->value, IQueryBuilder::PARAM_INT))
	 	    ->where($qb->expr()->eq('session_token', $qb->createNamedParameter($sessionToken)));

		if ($prevStatus !== null) {
			$qb->andWhere($qb->expr()->eq('status', $qb->createNamedParameter($prevStatus->value, IQueryBuilder::PARAM_INT)));
		}

		return $qb->executeStatement();
	}

//	public function updateDataset(string $token, array $dataset): void {
//		$qb = $this->db->getQueryBuilder();
//		$qb->update($this->getTableName())
//		   ->set('dataset', $qb->createNamedParameter(json_encode($dataset)))
//		   ->where($qb->expr()->eq('token', $qb->createNamedParameter($token)));
//
//		$qb->executeStatement();
//	}

	public function deleteAll() {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName());
		$qb->executeStatement();
	}
}
