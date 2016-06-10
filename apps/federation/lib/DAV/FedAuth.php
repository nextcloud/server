<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\Federation\DAV;

use OCA\Federation\DbHandler;
use Sabre\DAV\Auth\Backend\AbstractBasic;

class FedAuth extends AbstractBasic {

	/** @var DbHandler */
	private $db;

	/**
	 * FedAuth constructor.
	 *
	 * @param DbHandler $db
	 */
	public function __construct(DbHandler $db) {
		$this->db = $db;
		$this->principalPrefix = 'principals/system/';

		// setup realm
		$defaults = new \OC_Defaults();
		$this->realm = $defaults->getName();
	}

	/**
	 * Validates a username and password
	 *
	 * This method should return true or false depending on if login
	 * succeeded.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	protected function validateUserPass($username, $password) {
		return $this->db->auth($username, $password);
	}
}
