<?php

/**
 * ownCloud – LDAP User
 *
 * @author Arthur Schiwon
 * @copyright 2014 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\user_ldap\lib\user;

use OCA\User_LDAP\Mapping\UserMapping;

class OfflineUser {
	/**
	 * @var string $ocName
	 */
	protected $ocName;
	/**
	 * @var string $dn
	 */
	protected $dn;
	/**
	 * @var string $uid the UID as provided by LDAP
	 */
	protected $uid;
	/**
	 * @var string $displayName
	 */
	protected $displayName;
	/**
	 * @var string $homePath
	 */
	protected $homePath;
	/**
	 * @var string $lastLogin the timestamp of the last login
	 */
	protected $lastLogin;
	/**
	 * @var string $email
	 */
	protected $email;
	/**
	 * @var bool $hasActiveShares
	 */
	protected $hasActiveShares;
	/**
	 * @var \OCP\IConfig $config
	 */
	protected $config;
	/**
	 * @var \OCP\IDBConnection $db
	 */
	protected $db;
	/**
	 * @var \OCA\User_LDAP\Mapping\UserMapping
	 */
	protected $mapping;

	/**
	 * @param string $ocName
	 * @param OCP\IConfig $config
	 * @param OCP\IDBConnection $db
	 * @param OCA\User_LDAP\Mapping\UserMapping $mapping
	 */
	public function __construct($ocName, \OCP\IConfig $config, \OCP\IDBConnection $db, UserMapping $mapping) {
		$this->ocName = $ocName;
		$this->config = $config;
		$this->db = $db;
		$this->mapping = $mapping;
		$this->fetchDetails();
	}

	/**
	 * exports the user details in an assoc array
	 * @return array
	 */
	public function export() {
		$data = array();
		$data['ocName'] = $this->getOCName();
		$data['dn'] = $this->getDN();
		$data['uid'] = $this->getUID();
		$data['displayName'] = $this->getDisplayName();
		$data['homePath'] = $this->getHomePath();
		$data['lastLogin'] = $this->getLastLogin();
		$data['email'] = $this->getEmail();
		$data['hasActiveShares'] = $this->getHasActiveShares();

		return $data;
	}

	/**
	 * getter for ownCloud internal name
	 * @return string
	 */
	public function getOCName() {
		return $this->ocName;
	}

	/**
	 * getter for LDAP uid
	 * @return string
	 */
	public function getUID() {
		return $this->uid;
	}

	/**
	 * getter for LDAP DN
	 * @return string
	 */
	public function getDN() {
		return $this->dn;
	}

	/**
	 * getter for display name
	 * @return string
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * getter for email
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * getter for home directory path
	 * @return string
	 */
	public function getHomePath() {
		return $this->homePath;
	}

	/**
	 * getter for the last login timestamp
	 * @return int
	 */
	public function getLastLogin() {
		return intval($this->lastLogin);
	}

	/**
	 * getter for having active shares
	 * @return bool
	 */
	public function getHasActiveShares() {
		return $this->hasActiveShares;
	}

	/**
	 * reads the user details
	 */
	protected function fetchDetails() {
		$properties = array (
			'displayName' => 'user_ldap',
			'uid'         => 'user_ldap',
			'homePath'    => 'user_ldap',
			'email'       => 'settings',
			'lastLogin'   => 'login'
		);
		foreach($properties as $property => $app) {
			$this->$property = $this->config->getUserValue($this->ocName, $app, $property, '');
		}

		$dn = $this->mapping->getDNByName($this->ocName);
		$this->dn = ($dn !== false) ? $dn : '';

		$this->determineShares();
	}


	/**
	 * finds out whether the user has active shares. The result is stored in
	 * $this->hasActiveShares
	 */
	protected function determineShares() {
		$query = $this->db->prepare('
			SELECT COUNT(`uid_owner`)
			FROM `*PREFIX*share`
			WHERE `uid_owner` = ?
		', 1);
		$query->execute(array($this->ocName));
		$sResult = $query->fetchColumn(0);
		if(intval($sResult) === 1) {
			$this->hasActiveShares = true;
			return;
		}

		$query = $this->db->prepare('
			SELECT COUNT(`owner`)
			FROM `*PREFIX*share_external`
			WHERE `owner` = ?
		', 1);
		$query->execute(array($this->ocName));
		$sResult = $query->fetchColumn(0);
		if(intval($sResult) === 1) {
			$this->hasActiveShares = true;
			return;
		}

		$this->hasActiveShares = false;
	}
}
