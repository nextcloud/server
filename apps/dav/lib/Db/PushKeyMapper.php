<?php

declare(strict_types=1);

/**
 * @copyright 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<PushKey>
 */
class PushKeyMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'push_transports', PushKey::class);
	}

	/**
	 * @return PushKey[]
	 * @throws DoesNotExistException
	 */
	public function getForPrincipal(string $principalUri): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('principalUri', $qb->createNamedParameter($principalUri))
			)
			->andWhere($qb->expr()->eq('uri', $qb->createNamedParameter(null)))
		;

		return parent::findEntities($qb);
	}

	/**
	 * @return PushKey[]
	 * @throws DoesNotExistException
	 */
	public function getForUri(string $principalUri, string $uri): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('principalUri', $qb->createNamedParameter($principalUri))
			)
			->andWhere($qb->expr()->eq('uri', $qb->createNamedParameter($uri)))
		;

		return parent::findEntities($qb);
	}
}
