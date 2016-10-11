<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OCA\User_LDAP\Mapping;

/**
* Class AbstractMapping
* @package OCA\User_LDAP\Mapping
*/
abstract class AbstractMapping {
	/**
	 * @var \OCP\IDBConnection $dbc
	 */
	protected $dbc;

	/**
	 * returns the DB table name which holds the mappings
	 * @return string
	 */
	abstract protected function getTableName();

	/**
	 * @param \OCP\IDBConnection $dbc
	 */
	public function __construct(\OCP\IDBConnection $dbc) {
		$this->dbc = $dbc;
	}

	/**
	 * checks whether a provided string represents an existing table col
	 * @param string $col
	 * @return bool
	 */
	public function isColNameValid($col) {
		switch($col) {
			case 'ldap_dn':
			case 'owncloud_name':
			case 'directory_uuid':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Gets the value of one column based on a provided value of another column
	 * @param string $fetchCol
	 * @param string $compareCol
	 * @param string $search
	 * @throws \Exception
	 * @return string|false
	 */
	protected function getXbyY($fetchCol, $compareCol, $search) {
		if(!$this->isColNameValid($fetchCol)) {
			//this is used internally only, but we don't want to risk
			//having SQL injection at all.
			throw new \Exception('Invalid Column Name');
		}
		$query = $this->dbc->prepare('
			SELECT `' . $fetchCol . '`
			FROM `'. $this->getTableName() .'`
			WHERE `' . $compareCol . '` = ?
		');

		$res = $query->execute(array($search));
		if($res !== false) {
			return $query->fetchColumn();
		}

		return false;
	}

	/**
	 * Performs a DELETE or UPDATE query to the database.
	 * @param \Doctrine\DBAL\Driver\Statement $query
	 * @param array $parameters
	 * @return bool true if at least one row was modified, false otherwise
	 */
	protected function modify($query, $parameters) {
		$result = $query->execute($parameters);
		return ($result === true && $query->rowCount() > 0);
	}

	/**
	 * Gets the LDAP DN based on the provided name.
	 * Replaces Access::ocname2dn
	 * @param string $name
	 * @return string|false
	 */
	public function getDNByName($name) {
		return $this->getXbyY('ldap_dn', 'owncloud_name', $name);
	}

	/**
	 * Updates the DN based on the given UUID
	 * @param string $fdn
	 * @param string $uuid
	 * @return bool
	 */
	public function setDNbyUUID($fdn, $uuid) {
		$query = $this->dbc->prepare('
			UPDATE `' . $this->getTableName() . '`
			SET `ldap_dn` = ?
			WHERE `directory_uuid` = ?
		');

		return $this->modify($query, array($fdn, $uuid));
	}

	/**
	 * Gets the name based on the provided LDAP DN.
	 * @param string $fdn
	 * @return string|false
	 */
	public function getNameByDN($fdn) {
		return $this->getXbyY('owncloud_name', 'ldap_dn', $fdn);
	}

	/**
	 * Searches mapped names by the giving string in the name column
	 * @param string $search
	 * @param string $prefixMatch
	 * @param string $postfixMatch
	 * @return string[]
	 */
	public function getNamesBySearch($search, $prefixMatch = "", $postfixMatch = "") {
		$query = $this->dbc->prepare('
			SELECT `owncloud_name`
			FROM `'. $this->getTableName() .'`
			WHERE `owncloud_name` LIKE ?
		');

		$res = $query->execute(array($prefixMatch.$this->dbc->escapeLikeParameter($search).$postfixMatch));
		$names = array();
		if($res !== false) {
			while($row = $query->fetch()) {
				$names[] = $row['owncloud_name'];
			}
		}
		return $names;
	}

	/**
	 * Gets the name based on the provided LDAP UUID.
	 * @param string $uuid
	 * @return string|false
	 */
	public function getNameByUUID($uuid) {
		return $this->getXbyY('owncloud_name', 'directory_uuid', $uuid);
	}

	/**
	 * Gets the UUID based on the provided LDAP DN
	 * @param string $dn
	 * @return false|string
	 * @throws \Exception
	 */
	public function getUUIDByDN($dn) {
		return $this->getXbyY('directory_uuid', 'ldap_dn', $dn);
	}

	/**
	 * gets a piece of the mapping list
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public function getList($offset = null, $limit = null) {
		$query = $this->dbc->prepare('
			SELECT
				`ldap_dn` AS `dn`,
				`owncloud_name` AS `name`,
				`directory_uuid` AS `uuid`
			FROM `' . $this->getTableName() . '`',
			$limit,
			$offset
		);

		$query->execute();
		return $query->fetchAll();
	}

	/**
	 * attempts to map the given entry
	 * @param string $fdn fully distinguished name (from LDAP)
	 * @param string $name
	 * @param string $uuid a unique identifier as used in LDAP
	 * @return bool
	 */
	public function map($fdn, $name, $uuid) {
		$row = array(
			'ldap_dn'        => $fdn,
			'owncloud_name'  => $name,
			'directory_uuid' => $uuid
		);

		try {
			$result = $this->dbc->insertIfNotExist($this->getTableName(), $row);
			// insertIfNotExist returns values as int
			return (bool)$result;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * removes a mapping based on the owncloud_name of the entry
	 * @param string $name
	 * @return bool
	 */
	public function unmap($name) {
		$query = $this->dbc->prepare('
			DELETE FROM `'. $this->getTableName() .'`
			WHERE `owncloud_name` = ?');

		return $this->modify($query, array($name));
	}

	/**
	 * Truncate's the mapping table
	 * @return bool
	 */
	public function clear() {
		$sql = $this->dbc
			->getDatabasePlatform()
			->getTruncateTableSQL('`' . $this->getTableName() . '`');
		return $this->dbc->prepare($sql)->execute();
	}
}
