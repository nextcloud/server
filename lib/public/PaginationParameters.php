<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP;

use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Since 34.0.0
 * @see https://docs.joinmastodon.org/api/guidelines/#pagination
 */
#[Consumable(since: '34.0.0')]
class PaginationParameters {
	/**
	 * Pagination parameters.
	 *
	 * You can use $minId with $maxId and $maxId with $sinceId.
	 *
	 * @param ?int $limit The maximum number of results to return. Set to null to
	 *                    return all results.
	 * @param ?non-empty-string $maxId All results returned will be lesser than this ID. In effect, sets an upper bound on results.
	 * @param ?non-empty-string $minId Returns results immediately greater than this ID. In effect, sets a cursor at this ID and paginates forward.
	 * @param ?non-empty-string $sinceId All results returned will be greater than this ID. In effect, sets a lower bound on results.
	 */
	public function __construct(
		public ?int $limit = 100,
		public ?string $maxId = null,
		public ?string $minId = null,
		public ?string $sinceId = null,
	) {
		if ($minId !== null && $sinceId !== null) {
			throw new \InvalidArgumentException("minId and sinceId can't be defined togeter");
		}
	}

	/**
	 * Add pagination condition to the query builder based on the configured pagination settings.
	 */
	public function fillQuery(IQueryBuilder $qb, string $idColumn): IQueryBuilder {
		// This logic is inspired by
		// https://github.com/mastodon/mastodon/blob/main/app/models/concerns/paginable.rb#L7

		if ($this->minId !== null) {
			// paginate by min id (last entries added in the table last)
			$qb->andWhere($qb->expr()->gt($idColumn, $this->minId));
			if ($this->maxId !== null) {
				$qb->andWhere($qb->expr()->lt($idColumn, $this->maxId));
			}
			$qb->orderBy($idColumn, 'ASC');
			$qb->setMaxResults($this->limit);
			return $qb;
		} else {
			// paginate by max id (last entries added in the table first)
			if ($this->maxId !== null) {
				$qb->andWhere($qb->expr()->lt($idColumn, $this->maxId));
			}
			if ($this->sinceId !== null) {
				$qb->andWhere($qb->expr()->gt($idColumn, $this->sinceId));
			}
			$qb->orderBy($idColumn, 'DESC');
			$qb->setMaxResults($this->limit);
			return $qb;
		}
	}
}
