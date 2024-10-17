<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Paginate;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;

class PaginateCache {
	public const TTL = 3600;

	public function __construct(
		private IDBConnection $database,
		private ISecureRandom $random,
		private ITimeFactory $timeFactory,
	) {
	}

	/**
	 * @param string $uri
	 * @param \Iterator $items
	 * @return array{'token': int, 'count': int}
	 */
	public function store(string $uri, \Iterator $items): array {
		$token = $this->random->generate(32);
		$now = $this->timeFactory->getTime();

		$query = $this->database->getQueryBuilder();
		$query->insert('dav_page_cache')
			->values([
				'url_hash' => $query->createNamedParameter(md5($uri), IQueryBuilder::PARAM_STR),
				'token' => $query->createNamedParameter($token, IQueryBuilder::PARAM_STR),
				'insert_time' => $query->createNamedParameter($now, IQueryBuilder::PARAM_INT),
				'result_index' => $query->createParameter('index'),
				'result_value' => $query->createParameter('value'),
			]);

		$count = 0;
		foreach ($items as $item) {
			$value = json_encode($item);
			$query->setParameter('index', $count, IQueryBuilder::PARAM_INT);
			$query->setParameter('value', $value);
			$query->executeStatement();
			$count++;
		}

		return [$token, $count];
	}

	/**
	 * @param string $url
	 * @param string $token
	 * @param int $offset
	 * @param int $count
	 * @return array|\Traversable
	 */
	public function get(string $url, string $token, int $offset, int $count) {
		$query = $this->database->getQueryBuilder();
		$query->select(['result_value'])
			->from('dav_page_cache')
			->where($query->expr()->eq('token', $query->createNamedParameter($token)))
			->andWhere($query->expr()->eq('url_hash', $query->createNamedParameter(md5($url))))
			->andWhere($query->expr()->gte('result_index', $query->createNamedParameter($offset, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->lt('result_index', $query->createNamedParameter($offset + $count, IQueryBuilder::PARAM_INT)));

		$result = $query->executeQuery();
		return array_map(function (string $entry) {
			return json_decode($entry, true);
		}, $result->fetchAll(\PDO::FETCH_COLUMN));
	}

	public function cleanup(): void {
		$now = $this->timeFactory->getTime();

		$query = $this->database->getQueryBuilder();
		$query->delete('dav_page_cache')
			->where($query->expr()->lt('insert_time', $query->createNamedParameter($now - self::TTL)));
		$query->executeStatement();
	}

	public function clear(): void {
		$query = $this->database->getQueryBuilder();
		$query->delete('dav_page_cache');
		$query->executeStatement();
	}
}
