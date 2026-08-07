<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Tagging;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\ORM\Repository;

/**
 * Mapper for Tag entity
 *
 * @template-extends Repository<Tag>
 */
class TagMapper extends Repository {
	public const string entityClass = Tag::class;

	/**
	 * Load tags from the database.
	 *
	 * @param array $owners The user(s) whose tags we are going to load.
	 * @param string $type The type of item for which we are loading tags.
	 * @return array<array-key, Tag> An array of Tag objects.
	 */
	public function loadTags(array $owners, string $type): array {
		return iterator_to_array($this->findBy([
			'owner' => $owners,
			'type' => $type,
		], [
			'name' => 'ASC',
		]));
	}

	public function tagExists(Tag $tag): bool {
		try {
			$this->findOneBy([
				'owner' => $tag->owner,
				'type' => $tag->type,
				'name' => $tag->name,
			]);
			return true;
		} catch (DoesNotExistException) {
			return false;
		}
	}
}
