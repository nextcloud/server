<?php

/**
 * ownCloud
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack kde@jakobsack.de
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

class OC_Connector_Sabre_Locks extends Sabre_DAV_Locks_Backend_Abstract {

	/**
	 * Returns a list of Sabre_DAV_Locks_LockInfo objects
	 *
	 * This method should return all the locks for a particular uri, including
	 * locks that might be set on a parent uri.
	 *
	 * If returnChildLocks is set to true, this method should also look for
	 * any locks in the subtree of the uri for locks.
	 *
	 * @param string $uri
	 * @param bool $returnChildLocks
	 * @return array
	 */
	public function getLocks($uri, $returnChildLocks) {

		// NOTE: the following 10 lines or so could be easily replaced by
		// pure sql. MySQL's non-standard string concatination prevents us
		// from doing this though.
		// Fix: sqlite does not insert time() as a number but as text, making
		// the equation returning false all the time
		$query = 'SELECT * FROM *PREFIX*locks WHERE userid = ? AND (created + timeout) > '.time().' AND ((uri = ?)';
		$params = array(OC_User::getUser(),$uri);

		// We need to check locks for every part in the uri.
		$uriParts = explode('/',$uri);

		// We already covered the last part of the uri
		array_pop($uriParts);

		$currentPath='';

		foreach($uriParts as $part) {

			if ($currentPath) $currentPath.='/';
			$currentPath.=$part;

			$query.=' OR (depth!=0 AND uri = ?)';
			$params[] = $currentPath;

		}

		if ($returnChildLocks) {

			$query.=' OR (uri LIKE ?)';
			$params[] = $uri . '/%';

		}
		$query.=')';

		$stmt = OC_DB::prepare($query);
		$result = $stmt->execute($params);
		
		$lockList = array();
		while( $row = $result->fetchRow()){

			$lockInfo = new Sabre_DAV_Locks_LockInfo();
			$lockInfo->owner = $row['owner'];
			$lockInfo->token = $row['token'];
			$lockInfo->timeout = $row['timeout'];
			$lockInfo->created = $row['created'];
			$lockInfo->scope = $row['scope'];
			$lockInfo->depth = $row['depth'];
			$lockInfo->uri   = $row['uri'];
			$lockList[] = $lockInfo;

		}

		return $lockList;

	}

	/**
	 * Locks a uri
	 *
	 * @param string $uri
	 * @param Sabre_DAV_Locks_LockInfo $lockInfo
	 * @return bool
	 */
	public function lock($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

		// We're making the lock timeout 5 minutes
		$lockInfo->timeout = 300;
		$lockInfo->created = time();
		$lockInfo->uri = $uri;

		$locks = $this->getLocks($uri,false);
		$exists = false;
		foreach($locks as $k=>$lock) {
			if ($lock->token == $lockInfo->token) $exists = true;
		}
	
		if ($exists) {
			$query = OC_DB::prepare( 'UPDATE *PREFIX*locks SET owner = ?, timeout = ?, scope = ?, depth = ?, uri = ?, created = ? WHERE userid = ? AND token = ?' );
			$result = $query->execute( array($lockInfo->owner,$lockInfo->timeout,$lockInfo->scope,$lockInfo->depth,$uri,$lockInfo->created,OC_User::getUser(),$lockInfo->token));
		} else {
			$query = OC_DB::prepare( 'INSERT INTO *PREFIX*locks (userid,owner,timeout,scope,depth,uri,created,token) VALUES (?,?,?,?,?,?,?,?)' );
			$result = $query->execute( array(OC_User::getUser(),$lockInfo->owner,$lockInfo->timeout,$lockInfo->scope,$lockInfo->depth,$uri,$lockInfo->created,$lockInfo->token));
		}

		return true;

	}

	/**
	 * Removes a lock from a uri
	 *
	 * @param string $uri
	 * @param Sabre_DAV_Locks_LockInfo $lockInfo
	 * @return bool
	 */
	public function unlock($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

		$query = OC_DB::prepare( 'DELETE FROM *PREFIX*locks WHERE userid = ? AND uri=? AND token=?' );
		$result = $query->execute( array(OC_User::getUser(),$uri,$lockInfo->token));

		return $result->numRows() === 1;

	}

}
