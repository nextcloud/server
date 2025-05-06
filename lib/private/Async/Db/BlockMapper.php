<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Db;

use OC\Async\Exceptions\BlockNotFoundException;
use OC\Async\Model\Block;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\Async\Enum\BlockStatus;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<Block>
 */
class BlockMapper extends QBMapper {
	public const TABLE = 'async_process';

	public function __construct(
		IDBConnection $db,
		private LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, Block::class);
	}

	/**
	 * @return Block[]
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
	public function getSessions(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->selectDistinct('session_token')
		   ->from($this->getTableName());

		$result = $qb->executeQuery();

		$sessions = [];
		while ($row = $result->fetch()) {
			$sessions[] = $row['session_token'];
		}
		$result->closeCursor();

		return $sessions;
	}


	/**
	 * returns list of sessionId that contains process with:
	 * - at least one is in STANDBY with an older timestamp in next_run
	 * - none as RUNNING or BLOCKER
	 *
	 * @return string[]
	 */
	public function getSessionOnStandBy(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->selectDistinct('t1.session_token')
		    ->from($this->getTableName(), 't1')
			->leftJoin(
				't1', $this->getTableName(), 't2',
				$qb->expr()->andX(
					$qb->expr()->eq('t1.session_token', 't2.session_token'),
					$qb->expr()->in('t2.status', $qb->createNamedParameter([BlockStatus::PREP->value, BlockStatus::RUNNING->value, BlockStatus::BLOCKER->value], IQueryBuilder::PARAM_INT_ARRAY))
				)
			)
			->where($qb->expr()->eq('t1.status', $qb->createNamedParameter(BlockStatus::STANDBY->value, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->lt('t1.next_run', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->isNull('t2.status'));

		$result = $qb->executeQuery();

		$sessions = [];
		while ($row = $result->fetch()) {
			$sessions[] = $row['session_token'];
		}
		$result->closeCursor();

		return $sessions;
	}

	/**
	 * reset to STANDBY all process:
	 * - marked as ERROR or BLOCKER,
	 * - next_run in an older timestamp
	 * - next_run not at zero
	 */
	public function resetFailedBlock(): int {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
		   ->set('status', $qb->createNamedParameter(BlockStatus::STANDBY->value, IQueryBuilder::PARAM_INT))
		   ->where($qb->expr()->in('status', $qb->createNamedParameter([BlockStatus::ERROR->value, BlockStatus::BLOCKER->value], IQueryBuilder::PARAM_INT_ARRAY)))
		   ->andWhere($qb->expr()->lt('next_run', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->neq('next_run', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}

	/**
	 * delete sessions that contain only process with status at ::SUCCESS
	 */
	public function removeSuccessfulBlock(): int {
		$qb = $this->db->getQueryBuilder();
		$qb->selectDistinct('t1.session_token')
		   ->from($this->getTableName(), 't1')
		   ->leftJoin(
			   't1', $this->getTableName(), 't2',
			   $qb->expr()->andX(
				   $qb->expr()->eq('t1.session_token', 't2.session_token'),
				   $qb->expr()->neq('t2.status', $qb->createNamedParameter(BlockStatus::SUCCESS->value, IQueryBuilder::PARAM_INT))
			   )
		   )
		   ->where($qb->expr()->eq('t1.status', $qb->createNamedParameter(BlockStatus::SUCCESS->value, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->lt('t1.next_run', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->isNull('t2.status'));

		$result = $qb->executeQuery();
		$sessions = [];
		while ($row = $result->fetch()) {
			$sessions[] = $row['session_token'];
		}
		$result->closeCursor();

		$count = 0;
		$chunks = array_chunk($sessions, 30);
		foreach($chunks as $chunk) {
			$delete = $this->db->getQueryBuilder();
			$delete->delete($this->getTableName())
				   ->where($qb->expr()->in('session_token', $delete->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));
			$count += $delete->executeStatement();
			unset($delete);
		}

		return $count;
	}

	/**
	 * return a Block from its token
	 *
	 * @throws BlockNotFoundException
	 */
	public function getByToken(string $token): Block {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new BlockNotFoundException('no process found');
		}
	}

	public function updateStatus(Block $block, ?BlockStatus $prevStatus = null, string $lockToken = ''): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('status', $qb->createNamedParameter($block->getStatus()))
	 	    ->where($qb->expr()->andX(
				$qb->expr()->eq('session_token', $qb->createNamedParameter($block->getSessionToken())),
				$qb->expr()->eq('token', $qb->createNamedParameter($block->getToken()))
			)
			);

		// if process contain a lockToken, we conflict with the stored one
		if ($block->getLockToken() !== '') {
			$qb->andWhere($qb->expr()->eq('lock_token', $qb->createNamedParameter($block->getLockToken())));
		}

		// if status is switching from standby to running, we set last_run to detect timeout
		if ($block->getBlockStatus() === BlockStatus::RUNNING && $prevStatus === BlockStatus::STANDBY) {
			$qb->set('last_run', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT));
		}

		// if status is updated to success, error or blocker, we store result (or exception)
		if (in_array($block->getBlockStatus(), [BlockStatus::SUCCESS, BlockStatus::ERROR, BlockStatus::BLOCKER], true)) {
			try {
				$qb->set('result', $qb->createNamedParameter(json_encode($block->getResult(), JSON_THROW_ON_ERROR)));
				$qb->set('metadata', $qb->createNamedParameter(json_encode($block->getMetadata(), JSON_THROW_ON_ERROR)));
				$qb->set('next_run', $qb->createNamedParameter($block->getNextRun(), IQueryBuilder::PARAM_INT));
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

	public function updateSessionStatus(string $sessionToken, BlockStatus $status, ?BlockStatus $prevStatus = null): int {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('status', $qb->createNamedParameter($status->value, IQueryBuilder::PARAM_INT))
	 	    ->where($qb->expr()->eq('session_token', $qb->createNamedParameter($sessionToken)));

		if ($prevStatus !== null) {
			$qb->andWhere($qb->expr()->eq('status', $qb->createNamedParameter($prevStatus->value, IQueryBuilder::PARAM_INT)));
		}

		return $qb->executeStatement();
	}



	/**
	 * set next_run to current time if next_run>0 in relation to session_token.
	 * Force process queued for replay to be run as soon as possible.
	 */
	public function resetSessionNextRun(string $sessionToken): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
		   ->set('next_run', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
		   ->where(
			   $qb->expr()->eq('session_token', $qb->createNamedParameter($sessionToken)),
			   $qb->expr()->gt('next_run', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)),
		   );

		 $qb->executeStatement();
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
