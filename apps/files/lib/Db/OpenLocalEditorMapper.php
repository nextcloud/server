<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<OpenLocalEditor>
 */
class OpenLocalEditorMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'open_local_editor', OpenLocalEditor::class);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function verifyToken(string $userId, string $pathHash, string $token): OpenLocalEditor {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('path_hash', $qb->createNamedParameter($pathHash)))
			->andWhere($qb->expr()->eq('token', $qb->createNamedParameter($token)));

		return $this->findEntity($qb);
	}

	public function deleteExpiredTokens(int $time): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->lt('expiration_time', $qb->createNamedParameter($time)));

		$qb->executeStatement();
	}
}
