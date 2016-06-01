<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing;

use Doctrine\DBAL\Connection;
use OCP\IDBConnection;
use OC\Cache\CappedMemoryCache;

/**
 * Class Migration
 *
 * @package OCA\Files_Sharing
 * @group DB
 */
class Migration {

	/** @var IDBConnection */
	private $connection;

	/** @var  array with all shares we already saw */
	private $shareCache;

	/** @var string */
	private $table = 'share';

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;

		// We cache up to 10k share items (~20MB)
		$this->shareCache = new CappedMemoryCache(10000);
	}

	/**
	 * move all re-shares to the owner in order to have a flat list of shares
	 * upgrade from oC 8.2 to 9.0 with the new sharing
	 */
	public function removeReShares() {

		$stmt = $this->getReShares();

		$owners = [];
		while($share = $stmt->fetch()) {

			$this->shareCache[$share['id']] = $share;

			$owners[$share['id']] = [
					'owner' => $this->findOwner($share),
					'initiator' => $share['uid_owner'],
					'type' => $share['share_type'],
			];

			if (count($owners) === 1000) {
				$this->updateOwners($owners);
				$owners = [];
			}
		}

		$stmt->closeCursor();

		if (count($owners)) {
			$this->updateOwners($owners);
		}
	}

	/**
	 * update all owner information so that all shares have an owner
	 * and an initiator for the upgrade from oC 8.2 to 9.0 with the new sharing
	 */
	public function updateInitiatorInfo() {
		while (true) {
			$shares = $this->getMissingInitiator(1000);

			if (empty($shares)) {
				break;
			}

			$owners = [];
			foreach ($shares as $share) {
				$owners[$share['id']] = [
					'owner' => $share['uid_owner'],
					'initiator' => $share['uid_owner'],
					'type' => $share['share_type'],
				];
			}
			$this->updateOwners($owners);
		}
	}

	/**
	 * find the owner of a re-shared file/folder
	 *
	 * @param array $share
	 * @return array
	 */
	private function findOwner($share) {
		$currentShare = $share;
		while(!is_null($currentShare['parent'])) {
			if (isset($this->shareCache[$currentShare['parent']])) {
				$currentShare = $this->shareCache[$currentShare['parent']];
			} else {
				$currentShare = $this->getShare((int)$currentShare['parent']);
				$this->shareCache[$currentShare['id']] = $currentShare;
			}
		}

		return $currentShare['uid_owner'];
	}

	/**
	 * Get $n re-shares from the database
	 *
	 * @param int $n The max number of shares to fetch
	 * @return \Doctrine\DBAL\Driver\Statement
	 */
	private function getReShares() {
		$query = $this->connection->getQueryBuilder();
		$query->select(['id', 'parent', 'uid_owner', 'share_type'])
			->from($this->table)
			->where($query->expr()->in(
				'share_type',
				$query->createNamedParameter(
					[
						\OCP\Share::SHARE_TYPE_USER,
						\OCP\Share::SHARE_TYPE_GROUP,
						\OCP\Share::SHARE_TYPE_LINK,
						\OCP\Share::SHARE_TYPE_REMOTE,
					],
					Connection::PARAM_INT_ARRAY
				)
			))
			->andWhere($query->expr()->in(
				'item_type',
				$query->createNamedParameter(
					['file', 'folder'],
					Connection::PARAM_STR_ARRAY
				)
			))
			->andWhere($query->expr()->isNotNull('parent'))
			->orderBy('id', 'asc');
		return $query->execute();


		$shares = $result->fetchAll();
		$result->closeCursor();

		$ordered = [];
		foreach ($shares as $share) {
			$ordered[(int)$share['id']] = $share;
		}

		return $ordered;
	}

	/**
	 * Get $n re-shares from the database
	 *
	 * @param int $n The max number of shares to fetch
	 * @return array
	 */
	private function getMissingInitiator($n = 1000) {
		$query = $this->connection->getQueryBuilder();
		$query->select(['id', 'uid_owner', 'share_type'])
			->from($this->table)
			->where($query->expr()->in(
				'share_type',
				$query->createNamedParameter(
					[
						\OCP\Share::SHARE_TYPE_USER,
						\OCP\Share::SHARE_TYPE_GROUP,
						\OCP\Share::SHARE_TYPE_LINK,
						\OCP\Share::SHARE_TYPE_REMOTE,
					],
					Connection::PARAM_INT_ARRAY
				)
			))
			->andWhere($query->expr()->in(
				'item_type',
				$query->createNamedParameter(
					['file', 'folder'],
					Connection::PARAM_STR_ARRAY
				)
			))
			->andWhere($query->expr()->isNull('uid_initiator'))
			->orderBy('id', 'asc')
			->setMaxResults($n);
		$result = $query->execute();
		$shares = $result->fetchAll();
		$result->closeCursor();

		$ordered = [];
		foreach ($shares as $share) {
			$ordered[(int)$share['id']] = $share;
		}

		return $ordered;
	}

	/**
	 * get a specific share
	 *
	 * @param int $id
	 * @return array
	 */
	private function getShare($id) {
		$query = $this->connection->getQueryBuilder();
		$query->select(['id', 'parent', 'uid_owner'])
			->from($this->table)
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$result = $query->execute();
		$share = $result->fetchAll();
		$result->closeCursor();

		return $share[0];
	}

	/**
	 * update database with the new owners
	 *
	 * @param array $owners
	 * @throws \Exception
	 */
	private function updateOwners($owners) {

		$this->connection->beginTransaction();

		try {

			foreach ($owners as $id => $owner) {
				$query = $this->connection->getQueryBuilder();
				$query->update($this->table)
					->set('uid_owner', $query->createNamedParameter($owner['owner']))
					->set('uid_initiator', $query->createNamedParameter($owner['initiator']));


				if ((int)$owner['type'] !== \OCP\Share::SHARE_TYPE_LINK) {
					$query->set('parent', $query->createNamedParameter(null));
				}

				$query->where($query->expr()->eq('id', $query->createNamedParameter($id)));

				$query->execute();
			}

			$this->connection->commit();

		} catch (\Exception $e) {
			$this->connection->rollBack();
			throw $e;
		}

	}

}
