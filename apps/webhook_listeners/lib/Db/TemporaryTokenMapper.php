<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OC\Authentication\Token\PublicKeyTokenMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<TemporaryToken>
 */

class TemporaryTokenMapper extends QBMapper {
	public const TABLE_NAME = 'webhook_tokens';
	public const TOKEN_LIFETIME = 1 * 1 * 60; // one hour in seconds
	

	public function __construct(
		IDBConnection $db,
		private LoggerInterface $logger,
		private ITimeFactory $time,
		private PublicKeyTokenMapper $tokenMapper,
	) {
		parent::__construct($db, self::TABLE_NAME, TemporaryToken::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function getById(int $id): TemporaryToken {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @throws Exception
	 * @return WebhookListener[]
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName());

		return $this->findEntities($qb);
	}

	public function getOlderThan($olderThan): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->lt('creation_datetime', $qb->createNamedParameter($olderThan, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}


	/**
	 * @throws Exception
	 */
	public function addTemporaryToken(
		int $tokenId,
		string $token,
		?string $userId,
		int $creationDatetime,
	): TemporaryToken {
		$tempToken = TemporaryToken::fromParams(
			[
				'tokenId' => $tokenId,
				'token' => $token,
				'userId' => $userId,
				'creationDatetime' => $creationDatetime,
			]
		);
		return $this->insert($tempToken);
	}

	/**
	 * @throws Exception
	 */
	public function deleteByTokenId(int $tokenId): bool {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('token_id', $qb->createNamedParameter($tokenId, IQueryBuilder::PARAM_INT)));

		return $qb->executeStatement() > 0;
	}

	public function invalidateOldTokens(int $token_lifetime = self::TOKEN_LIFETIME) {
		$olderThan = $this->time->getTime() - $token_lifetime;
		error_log("OLDER THAN");
		$tokensToDelete = $this->getOlderThan($olderThan);
		error_log(json_encode($tokensToDelete));

		$this->logger->debug('Invalidating temporary webhook tokens older than ' . date('c', $olderThan), ['app' => 'cron']);
		foreach ($tokensToDelete as $token) {
			$this->tokenMapper->invalidate($token->getToken()); // delete token itself
			$this->deleteByTokenId($token->getTokenId()); // delete db row in webhook_temporary_tokens
		}

	}
}
