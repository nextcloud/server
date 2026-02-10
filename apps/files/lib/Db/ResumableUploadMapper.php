<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<ResumableUpload>
 */
class ResumableUploadMapper extends QBMapper {
	public const TABLE_NAME = 'resumable_upload';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME, ResumableUpload::class);
	}

	public function findByToken(string $userId, string $token): ?ResumableUpload {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('id', 'user_id', 'token', 'path', 'size', 'complete')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('token', $qb->createNamedParameter($token)));

		$result = $qb->executeQuery();
		/** @var array|false $row */
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		return ResumableUpload::fromRow($row);
	}
}
