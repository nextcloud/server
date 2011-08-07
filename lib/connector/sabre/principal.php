<?php

class OC_Connector_Sabre_Principal implements Sabre_DAVACL_IPrincipalBackend {
	/**
	 * TODO: write doc
	 */
	public static function addPrincipal($params){
		// Add the user
		$uri = 'principals/'.$params['uid'];
		$displayname = $params['uid'];
		$query = OC_DB::prepare('INSERT INTO *PREFIX*principals (uri,displayname) VALUES(?,?)');
		$query->execute(array($uri,$displayname));
		
		// Add calendar and addressbook read and write support (sharing calendars)
		$uri = 'principals/'.$params['uid'].'/calendar-proxy-read';
		$displayname = null;
		$query->execute(array($uri,$displayname));
		$uri = 'principals/'.$params['uid'].'/calendar-proxy-write';
		$query->execute(array($uri,$displayname));
		$uri = 'principals/'.$params['uid'].'/addressbook-proxy-read';
		$query->execute(array($uri,$displayname));
		$uri = 'principals/'.$params['uid'].'/addressbook-proxy-write';
		$query->execute(array($uri,$displayname));

		return true;
	}
	
	/**
	 * TODO: write doc
	 */
	public static function deletePrincipal($params){
		$query = OC_DB::prepare('SELECT * FROM *PREFIX*principals');
		$result = $query->execute();

		$deleteprincipal = OC_DB::prepare('DELETE FROM *PREFIX*principals WHERE id = ?');
		$deletegroup = OC_DB::prepare('DELETE FROM *PREFIX*principalgroups WHERE principal_id = ? OR member_id = ?');
		// We have to delete the principals and relations! Principals include 
		while($row = $result->fetchRow()){
			// Checking if the principal is in the prefix
			$array = explode('/',$row['uri']);
			if ($array[1] != $params['uid']) continue;
			$deleteprincipal->execute(array($row['id']));
			$deletegroup->execute(array($row['id'],$row['id']));
		}
		return true;
	}
	/**
	 * Returns a list of principals based on a prefix.
	 *
	 * This prefix will often contain something like 'principals'. You are only
	 * expected to return principals that are in this base path.
	 *
	 * You are expected to return at least a 'uri' for every user, you can
	 * return any additional properties if you wish so. Common properties are:
	 *   {DAV:}displayname
	 *
	 * @param string $prefixPath
	 * @return array
	 */
	public function getPrincipalsByPrefix( $prefixPath ){
		$query = OC_DB::prepare('SELECT * FROM *PREFIX*principals');
		$result = $query->execute();

		$principals = array();

		while($row = $result->fetchRow()){
			// Checking if the principal is in the prefix
			list($rowPrefix) = Sabre_DAV_URLUtil::splitPath($row['uri']);
			if ($rowPrefix !== $prefixPath) continue;

			$principals[] = array(
				'uri' => $row['uri'],
				'{DAV:}displayname' => $row['displayname']?$row['displayname']:basename($row['uri'])
			);

		}

		return $principals;
	}

	/**
	 * Returns a specific principal, specified by it's path.
	 * The returned structure should be the exact same as from
	 * getPrincipalsByPrefix.
	 *
	 * @param string $path
	 * @return array
	 */
	public function getPrincipalByPath($path) {
		$query = OC_DB::prepare('SELECT * FROM *PREFIX*principals WHERE uri=?');
		$result = $query->execute(array($path));

		$users = array();

		$row = $result->fetchRow();
		if (!$row) return;

		return array(
			'id'  => $row['id'],
			'uri' => $row['uri'],
			'{DAV:}displayname' => $row['displayname']?$row['displayname']:basename($row['uri'])
		);

	}

	/**
	 * Returns the list of members for a group-principal
	 *
	 * @param string $principal
	 * @return array
	 */
	public function getGroupMemberSet($principal) {
		$principal = $this->getPrincipalByPath($principal);
		if (!$principal) throw new Sabre_DAV_Exception('Principal not found');

		$query = OC_DB::prepare('SELECT principals.uri as uri FROM *PREFIX*principalgroups AS groupmembers LEFT JOIN *PREFIX*principals AS principals ON groupmembers.member_id = principals.id WHERE groupmembers.principal_id = ?');
		$result = $query->execute(array($principal['id']));
	
		$return = array();
		while ($row = $result->fetchRow()){
			$return[] = $row['uri'];
		}
		return $return;
	}

	/**
	 * Returns the list of groups a principal is a member of
	 *
	 * @param string $principal
	 * @return array
	 */
	public function getGroupMembership($principal) {
		$principal = $this->getPrincipalByPath($principal);
		if (!$principal) throw new Sabre_DAV_Exception('Principal not found');

		$query = OC_DB::prepare('SELECT principals.uri as uri FROM *PREFIX*principalgroups AS groupmembers LEFT JOIN *PREFIX*principals AS principals ON groupmembers.member_id = principals.id WHERE groupmembers.member_id = ?');
		$result = $query->execute(array($principal['id']));

		$return = array();
		while ($row = $result->fetchRow()){
			$return[] = $row['uri'];
		}
		return $return;
	}

	/**
	 * Updates the list of group members for a group principal.
	 *
	 * The principals should be passed as a list of uri's.
	 *
	 * @param string $principal
	 * @param array $members
	 * @return void
	 */
	public function setGroupMemberSet($principal, array $members) {
		$query = OC_DB::prepare('SELECT id, uri FROM *PREFIX*principals WHERE uri IN (? '.str_repeat(', ?', count($members)).')');
		$result = $query->execute(array_merge(array($principal), $members));

		$memberIds = array();
		$principalId = null;

		while($row = $$result->fetchRow()) {
			if ($row['uri'] == $principal) {
				$principalId = $row['id'];
			}
			else{
				$memberIds[] = $row['id'];
			}
		}
		if (!$principalId) throw new Sabre_DAV_Exception('Principal not found');

		// Wiping out old members
		$query = OC_DB::prepare('DELETE FROM *PREFIX*principalgroups WHERE principal_id = ?');
		$query->execute(array($principalID));

		$query = OC_DB::prepare('INSERT INTO *PREFIX*principalgroups (principal_id, member_id) VALUES (?, ?);');
		foreach($memberIds as $memberId) {
			$query->execute(array($principalId, $memberId));
		}
	}
}
