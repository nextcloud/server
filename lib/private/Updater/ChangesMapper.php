<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Updater;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Changes>
 */
class ChangesMapper extends QBMapper {
	public const TABLE_NAME = 'whats_new';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function getChanges(string $version): Changes {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq('version', $qb->createNamedParameter($version)))
			->executeQuery();

		$data = $result->fetch();
		$result->closeCursor();
		if ($data === false) {
			throw new DoesNotExistException('Changes info is not present');
		}
		return Changes::fromRow($data);
	}
}
