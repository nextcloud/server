<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\ORM\Repository;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * @template-extends Repository<OpenLocalEditor>
 */
class OpenLocalEditorMapper extends Repository {
	public const string entityClass = OpenLocalEditor::class;

	/**
	 * @throws DoesNotExistException
	 */
	public function verifyToken(string $userId, string $pathHash, string $token): OpenLocalEditor {
		return $this->findOneBy([
			'userId' => $userId,
			'pathHash' => $pathHash,
			'token' => $token,
		]);
	}

	public function deleteExpiredTokens(int $time): void {
		$qb = $this->connection->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->lt('expiration_time', $qb->createNamedParameter($time, IQueryBuilder::PARAM_INT)));

		$qb->executeStatement();
	}
}
