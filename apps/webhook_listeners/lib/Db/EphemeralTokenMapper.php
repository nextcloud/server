<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Db;

use OC\Authentication\Token\PublicKeyTokenMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<EphemeralToken>
 */

class EphemeralTokenMapper extends QBMapper {
	public const TABLE_NAME = 'webhook_tokens';
	public const TOKEN_LIFETIME = 1 * 1 * 60; // one hour in seconds

	public function __construct(
		IDBConnection $db,
		private LoggerInterface $logger,
		private ITimeFactory $time,
		private PublicKeyTokenMapper $tokenMapper,
	) {
		parent::__construct($db, self::TABLE_NAME, EphemeralToken::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function getById(int $id): EphemeralToken {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @throws Exception
	 * @return EphemeralToken[]
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName());

		return $this->findEntities($qb);
	}


	/**
	 * @param int $olderThan
	 * @return EphemeralToken[]
	 * @throws Exception
	 */
	public function getOlderThan($olderThan): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->lt('created_at', $qb->createNamedParameter($olderThan, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}

	/**
	 * @throws Exception
	 */
	public function addEphemeralToken(
		int $tokenId,
		?string $userId,
		int $createdAt,
	): EphemeralToken {
		$tempToken = EphemeralToken::fromParams(
			[
				'tokenId' => $tokenId,
				'userId' => $userId,
				'createdAt' => $createdAt,
			]
		);
		return $this->insert($tempToken);
	}
	public function invalidateOldTokens(int $token_lifetime = self::TOKEN_LIFETIME) {
		$olderThan = $this->time->getTime() - $token_lifetime;
		try {
			$tokensToDelete = $this->getOlderThan($olderThan);
		} catch (Exception $e) {
			$this->logger->error('Webhook token deletion failed: ' . $e->getMessage(), ['exception' => $e]);
			return;
		}


		$this->logger->debug('Invalidating ephemeral webhook tokens older than ' . date('c', $olderThan), ['app' => 'webhook_listeners']);
		foreach ($tokensToDelete as $token) {
			try {
				$this->tokenMapper->delete($this->tokenMapper->getTokenById($token->getTokenId())); // delete token itself
				$this->delete($token); // delete db row in webhook_tokens
			} catch (Exception $e) {
				$this->logger->error('Webhook token deletion failed: ' . $e->getMessage(), ['exception' => $e]);
			}

		}
	}
}
