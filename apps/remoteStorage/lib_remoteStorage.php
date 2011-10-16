<?php

class OC_remoteStorage {
	public static function getValidTokens($ownCloudUser, $userAddress, $dataScope) {
		$query=OC_DB::prepare("SELECT token,appUrl FROM *PREFIX*authtoken WHERE user=? AND userAddress=? AND dataScope=? LIMIT 100");
		$result=$query->execute(array($ownCloudUser,$userAddress,$dataScope));
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
			OC_Log::write('removeStorage',$entry,OC_Log::ERROR);
			die( $entry );
		}
		$ret = array();
		while($row=$result->fetchRow()){
			$ret[$row['token']]=$userAddress;
		}
		return $ret;
	}

	public static function getAllTokens() {
		$user=OC_User::getUser();
		$query=OC_DB::prepare("SELECT token,appUrl,userAddress,dataScope FROM *PREFIX*authtoken WHERE user=? LIMIT 100");
		$result=$query->execute(array($user));
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
			OC_Log::write('removeStorage',$entry,OC_Log::ERROR);
			die( $entry );
		}
		$ret = array();
		while($row=$result->fetchRow()){
			$ret[$row['token']] = array(
				'appUrl' => $row['appurl'],
				'userAddress' => $row['useraddress'],
				'dataScope' => $row['datascope'],
			);
		}
		return $ret;
	}

	public static function deleteToken($token) {
		$user=OC_User::getUser();
		$query=OC_DB::prepare("DELETE FROM *PREFIX*authtoken WHERE token=? AND user=?");
		$result=$query->execute(array($token,$user));
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
			OC_Log::write('removeStorage',$entry,OC_Log::ERROR);
			die( $entry );
		}
	}
	private static function addToken($token, $appUrl, $userAddress, $dataScope){
		$user=OC_User::getUser();
		$query=OC_DB::prepare("INSERT INTO *PREFIX*authtoken (`token`,`appUrl`,`user`,`userAddress`,`dataScope`) VALUES(?,?,?,?,?)");
		$result=$query->execute(array($token,$appUrl,$user,$userAddress,$dataScope));
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
			OC_Log::write('removeStorage',$entry,OC_Log::ERROR);
			die( $entry );
		}
	}
	public static function createDataScope($appUrl, $userAddress, $dataScope){
		$token=uniqid();
		self::addToken($token, $appUrl, $userAddress, $dataScope);
		//TODO: input checking on $userAddress and $dataScope
		list($userName, $userHost) = explode('@', $userAddress);
		OC_Util::setupFS(OC_User::getUser());
		$scopePathParts = array('remoteStorage', 'webdav', $userHost, $userName, $dataScope);
		for($i=0;$i<=count($scopePathParts);$i++){
			$thisPath = '/'.implode('/', array_slice($scopePathParts, 0, $i));
			if(!OC_Filesystem::file_exists($thisPath)) {
				OC_Filesystem::mkdir($thisPath);
			}
		}
		return $token;
	}
}
