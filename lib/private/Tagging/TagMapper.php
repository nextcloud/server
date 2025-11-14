<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Tagging;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\Repository;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Mapper for Tag entity
 *
 * @template-extends Repository<Tag>
 */
class TagMapper extends Repository {
	/**
	 * Constructor.
	 *
	 * @param IDBConnection $db Instance of the Db abstraction layer.
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, Tag::class);
	}

	/**
	 * Load tags from the database.
	 *
	 * @param array $owners The user(s) whose tags we are going to load.
	 * @param string $type The type of item for which we are loading tags.
	 * @return list<Tag> An array of Tag objects.
	 */
	public function loadTags(array $owners, string $type): array {
		return iterator_to_array($this->findBy([
			'owner' => $owners,
			'type' => $type,
		], [
			'name' => 'ASC',
		]));
	}
}
