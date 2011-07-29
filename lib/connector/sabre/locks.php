<?php
/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This Lock Manager stores all its data in a database. You must pass a PDO
 * connection object in the constructor.
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
/*
 *
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE locks (
 *   `id` INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 *   `userid` VARCHAR(200),
 *   `owner` VARCHAR(100),
 *   `timeout` INTEGER UNSIGNED,
 *   `created` INTEGER,
 *   `token` VARCHAR(100),
 *   `scope` TINYINT,
 *   `depth` TINYINT,
 *   `uri` text
 * );
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
		$query = 'SELECT * FROM *PREFIX*locks WHERE userid = ? AND (created + timeout) > ? AND ((uri = ?)';
		$params = array(OC_User::getUser(),time(),$uri);

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
