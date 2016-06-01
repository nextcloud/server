<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
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

namespace OC\OCS;

class PrivateData {

	/**
	 * read keys
	 * test: curl http://login:passwd@oc/core/ocs/v1.php/privatedata/getattribute/testy/123
	 * test: curl http://login:passwd@oc/core/ocs/v1.php/privatedata/getattribute/testy
	 * @param array $parameters The OCS parameter
	 * @return \OC_OCS_Result
	 */
	public static function get($parameters) {
		$user = \OC_User::getUser();
		$app = addslashes(strip_tags($parameters['app']));
		$key = isset($parameters['key']) ? addslashes(strip_tags($parameters['key'])) : null;
		
		if(empty($key)) {
			$query = \OCP\DB::prepare('SELECT `key`, `app`, `value`  FROM `*PREFIX*privatedata` WHERE `user` = ? AND `app` = ? ');
			$result = $query->execute(array($user, $app));
		} else {
			$query = \OCP\DB::prepare('SELECT `key`, `app`, `value`  FROM `*PREFIX*privatedata` WHERE `user` = ? AND `app` = ? AND `key` = ? ');
			$result = $query->execute(array($user, $app, $key));
		}
		
		$xml = array();
		while ($row = $result->fetchRow()) {
			$data=array();
			$data['key']=$row['key'];
			$data['app']=$row['app'];
			$data['value']=$row['value'];
		 	$xml[] = $data;
		}

		return new Result($xml);
	}

	/**
	 * set a key
	 * test: curl http://login:passwd@oc/core/ocs/v1.php/privatedata/setattribute/testy/123  --data "value=foobar"
	 * @param array $parameters The OCS parameter
	 * @return \OC_OCS_Result
	 */
	public static function set($parameters) {
		$user = \OC_User::getUser();
		$app = addslashes(strip_tags($parameters['app']));
		$key = addslashes(strip_tags($parameters['key']));
		$value = (string)$_POST['value'];

		// update in DB
		$query = \OCP\DB::prepare('UPDATE `*PREFIX*privatedata` SET `value` = ?  WHERE `user` = ? AND `app` = ? AND `key` = ?');
		$numRows = $query->execute(array($value, $user, $app, $key));
                
		if ($numRows === false || $numRows === 0) {
			// store in DB
			$query = \OCP\DB::prepare('INSERT INTO `*PREFIX*privatedata` (`user`, `app`, `key`, `value`)' . ' VALUES(?, ?, ?, ?)');
			$query->execute(array($user, $app, $key, $value));
		}

		return new Result(null, 100);
	}

	/**
	 * delete a key
	 * test: curl http://login:passwd@oc/core/ocs/v1.php/privatedata/deleteattribute/testy/123 --data "post=1"
	 * @param array $parameters The OCS parameter
	 * @return \OC_OCS_Result
	 */
	public static function delete($parameters) {
		$user = \OC_User::getUser();
		if (!isset($parameters['app']) or !isset($parameters['key'])) {
			//key and app are NOT optional here
			return new Result(null, 101);
		}

		$app = addslashes(strip_tags($parameters['app']));
		$key = addslashes(strip_tags($parameters['key']));

		// delete in DB
		$query = \OCP\DB::prepare('DELETE FROM `*PREFIX*privatedata`  WHERE `user` = ? AND `app` = ? AND `key` = ? ');
		$query->execute(array($user, $app, $key ));

		return new Result(null, 100);
	}
}

