<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Db;

use OCA\OAuth2\Controller\OauthApiController;
use OCA\OAuth2\Exceptions\AccessTokenNotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\ORM\Repository;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * @template-extends Repository<AccessToken>
 * @psalm-suppress ClassMustBeFinal For unit tests
 */
class AccessTokenMapper extends Repository {
	public const string entityClass = AccessToken::class;

	/**
	 * @throws AccessTokenNotFoundException
	 */
	public function getByCode(string $code): AccessToken {
		try {
			return $this->findOneBy([
				'hashedCode' => hash('sha512', $code),
			]);
		} catch (DoesNotExistException $doesNotExistException) {
			throw new AccessTokenNotFoundException('Could not find access token', 0, $doesNotExistException);
		}
	}

	/**
	 * Delete all access token from a given client
	 */
	public function deleteByClientId(int $id): void {
		$this->deleteBy([
			'clientId' => $id,
		]);
	}

	/**
	 * Delete access tokens that have an expired authorization code
	 * -> those that are old enough
	 * and which never delivered any oauth token (still in authorization state)
	 *
	 * @throws Exception
	 */
	public function cleanupExpiredAuthorizationCode(ITimeFactory $timeFactory): void {
		$now = $timeFactory->now()->getTimestamp();
		$maxTokenCreationTs = $now - OauthApiController::AUTHORIZATION_CODE_EXPIRES_AFTER;

		$qb = $this->getDatabaseConnection()->getQueryBuilder();
		$qb
			->delete($this->getTableName())
			->where($qb->expr()->eq('token_count', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->lt('code_created_at', $qb->createNamedParameter($maxTokenCreationTs, IQueryBuilder::PARAM_INT)));
		$qb->executeStatement();
	}

	/**
	 * Rotate an access token only if it still matches the caller's previously-read state.
	 *
	 * @param bool $expectAuthorizationCodeState Require the token to still be unused
	 * @return int Number of updated rows
	 */
	public function rotateToken(int $id, string $oldCode, string $newCode, string $encryptedToken, bool $expectAuthorizationCodeState): int {
		$qb = $this->getDatabaseConnection()->getQueryBuilder();
		$qb
			->update($this->getTableName())
			->set('hashed_code', $qb->createNamedParameter(hash('sha512', $newCode)))
			->set('encrypted_token', $qb->createNamedParameter($encryptedToken))
			->set('token_count', $qb->createFunction('token_count + 1'))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('hashed_code', $qb->createNamedParameter(hash('sha512', $oldCode))));

		if ($expectAuthorizationCodeState) {
			$qb->andWhere($qb->expr()->eq('token_count', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		}

		return $qb->executeStatement();
	}
}
