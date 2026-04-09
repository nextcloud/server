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
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<LoginFlowV2>
 */
class LoginFlowV2Mapper extends QBMapper {
	private const lifetime = 1200;

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
