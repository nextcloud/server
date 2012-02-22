<?php

class OC_remoteStorage {
	public static function getValidTokens($ownCloudUser, $category) {
		$query=OC_DB::prepare("SELECT token,appUrl,category FROM *PREFIX*authtoken WHERE user=? LIMIT 100");
		$result=$query->execute(array($ownCloudUser));
		$ret = array();
		while($row=$result->fetchRow()){
			if(in_array($category, explode(',', $row['category']))) {
				$ret[$row['token']]=true;
			}
		}
		return $ret;
	}

	public static function getAllTokens() {
		$user=OC_User::getUser();
		$query=OC_DB::prepare("SELECT token,appUrl,category FROM *PREFIX*authtoken WHERE user=? LIMIT 100");
		$result=$query->execute(array($user));
		$ret = array();
		while($row=$result->fetchRow()){
			$ret[$row['token']] = array(
				'appUrl' => $row['appurl'],
				'categories' => $row['category'],
			);
		}
		return $ret;
	}

	public static function deleteToken($token) {
		$user=OC_User::getUser();
		$query=OC_DB::prepare("DELETE FROM *PREFIX*authtoken WHERE token=? AND user=?");
		$result=$query->execute(array($token,$user));
	}
	private static function addToken($token, $appUrl, $categories){
		$user=OC_User::getUser();
		$query=OC_DB::prepare("INSERT INTO *PREFIX*authtoken (`token`,`appUrl`,`user`,`category`) VALUES(?,?,?,?)");
		$result=$query->execute(array($token,$appUrl,$user,$categories));
	}
	public static function createCategories($appUrl, $categories) {
		$token=uniqid();
		OC_Util::setupFS(OC_User::getUser());
		self::addToken($token, $appUrl, $categories);
		foreach($categories as $category) {
			//TODO: input checking on $category
			$scopePathParts = array('remoteStorage', $category);
			for($i=0;$i<=count($scopePathParts);$i++){
				$thisPath = '/'.implode('/', array_slice($scopePathParts, 0, $i));
				if(!OC_Filesystem::file_exists($thisPath)) {
					OC_Filesystem::mkdir($thisPath);
				}
			}
		}
		return base64_encode('remoteStorage:'.$token);
	}
}
