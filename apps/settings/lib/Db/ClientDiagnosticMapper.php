<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Settings\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<ClientDiagnostic>
 */
class ClientDiagnosticMapper extends QBMapper {
	public const TABLE_NAME = 'client_diagnostics';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME, ClientDiagnostic::class);
	}

	/**
	 * @return ClientDiagnostic[]
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName());

		return $this->findEntities($select);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findByAuthtokenid(int $id): ClientDiagnostic {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('authtokenid', $qb->createNamedParameter($id, $qb::PARAM_INT)));

		return $this->findEntity($select);
	}
}
