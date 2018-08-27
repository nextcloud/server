<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Paginate;


use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;

class PaginateCache {
	const TTL = 3600;

	/** @var IDBConnection */
	private $database;
	/** @var ISecureRandom */
	private $random;
	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(
		IDBConnection $database,
		ISecureRandom $random,
		ITimeFactory $timeFactory
	) {
		$this->database = $database;
		$this->random = $random;
		$this->timeFactory = $timeFactory;
	}

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
			$query->execute();
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

		$result = $query->execute();
		return array_map(function (string $entry) {
			return json_decode($entry, true);
		}, $result->fetchAll(\PDO::FETCH_COLUMN));
	}

	public function cleanup() {
		$now = $this->timeFactory->getTime();

		$query = $this->database->getQueryBuilder();
		$query->delete('dav_page_cache')
			->where($query->expr()->lt('insert_time', $query->createNamedParameter($now - self::TTL)));
		$query->execute();
	}

	public function clear() {
		$query = $this->database->getQueryBuilder();
		$query->delete('dav_page_cache');
		$query->execute();
	}
}