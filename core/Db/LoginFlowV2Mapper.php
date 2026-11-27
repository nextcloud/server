<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<LoginFlowV2>
 */
class LoginFlowV2Mapper extends QBMapper {
	private const int lifetime = 1200;

	public function __construct(
		IDBConnection $db,
		private ITimeFactory $timeFactory,
	) {
		parent::__construct(
			$db,
			'login_flow_v2',
			LoginFlowV2::class,
		);
	}

	/**
	 * @param string $pollToken
	 * @return LoginFlowV2
	 * @throws DoesNotExistException
	 */
	public function getByPollToken(string $pollToken): LoginFlowV2 {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('poll_token', $qb->createNamedParameter($pollToken))
			);

		$entity = $this->findEntity($qb);
		return $this->validateTimestamp($entity);
	}

	/**
	 * @param string $loginToken
	 * @return LoginFlowV2
	 * @throws DoesNotExistException
	 */
	public function getByLoginToken(string $loginToken): LoginFlowV2 {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('login_token', $qb->createNamedParameter($loginToken))
			);

		$entity = $this->findEntity($qb);
		return $this->validateTimestamp($entity);
	}

	public function cleanup(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->lt('timestamp', $qb->createNamedParameter($this->timeFactory->getTime() - self::lifetime))
			);

		$qb->executeStatement();
	}

	public function deletePending(string $loginToken): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('login_token', $qb->createNamedParameter($loginToken)))
			->andWhere($qb->expr()->isNull('app_password'));

		return $qb->executeStatement() === 1;
	}

	/** Only a pending, unexpired request can receive credentials. */
	public function complete(LoginFlowV2 $flow): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('login_name', $qb->createNamedParameter($flow->getLoginName()))
			->set('server', $qb->createNamedParameter($flow->getServer()))
			->set('app_password', $qb->createNamedParameter($flow->getAppPassword()))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($flow->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->isNull('app_password'))
			->andWhere($qb->expr()->gte('timestamp', $qb->createNamedParameter($this->timeFactory->getTime() - self::lifetime, IQueryBuilder::PARAM_INT)));

		return $qb->executeStatement() === 1;
	}

	/**
	 * @param LoginFlowV2 $flowV2
	 * @return LoginFlowV2
	 * @throws DoesNotExistException
	 */
	private function validateTimestamp(LoginFlowV2 $flowV2): LoginFlowV2 {
		if ($flowV2->getTimestamp() < ($this->timeFactory->getTime() - self::lifetime)) {
			$this->delete($flowV2);
			throw new DoesNotExistException('Token expired');
		}

		return $flowV2;
	}
}
