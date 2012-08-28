<?php

class OC_remoteStorage {
	public static function getValidTokens($ownCloudUser, $category) {
		$stmt=OCP\DB::prepare("SELECT `token`,`appUrl`,`category` FROM `*PREFIX*authtoken` WHERE `user`=?",100);
		$result=$stmt->execute(array($ownCloudUser));
		$ret = array();
		while($row=$result->fetchRow()){
			if(in_array($category, explode(',', $row['category']))) {
				$ret[$row['token']]=true;
			}
		}
		return $ret;
	}

  public static function getTokenFor($appUrl, $categories) {
		$user=OCP\USER::getUser();
		$stmt=OCP\DB::prepare("SELECT `token` FROM `*PREFIX*authtoken` WHERE `user`=? AND `appUrl`=? AND `category`=?",1);
		$result=$stmt->execute(array($user, $appUrl, $categories));
		if($row=$result->fetchRow()) {
			return base64_encode('remoteStorage:'.$row['token']);
		} else {
			return false;
		}
	}

	public static function getAllTokens() {
		$user=OCP\USER::getUser();
		$stmt=OCP\DB::prepare("SELECT `token`,`appUrl`,`category` FROM `*PREFIX*authtoken` WHERE `user`=?",100);
		$result=$stmt->execute(array($user));
		$ret = array();
		while($row=$result->fetchRow()){
			$ret[$row['token']] = array(
				'appUrl' => $row['appUrl'],
				'categories' => $row['category'],
			);
		}
		return $ret;
	}

	public static function deleteToken($token) {
		$user=OCP\USER::getUser();
		$stmt=OCP\DB::prepare("DELETE FROM `*PREFIX*authtoken` WHERE `token`=? AND `user`=?");
		$stmt->execute(array($token,$user));
		return 'unknown';//how can we see if any rows were affected?
	}
	private static function addToken($token, $appUrl, $categories){
		$user=OCP\USER::getUser();
		$stmt=OCP\DB::prepare("INSERT INTO `*PREFIX*authtoken` (`token`,`appUrl`,`user`,`category`) VALUES(?,?,?,?)");
		$stmt->execute(array($token,$appUrl,$user,$categories));
	}
	public static function createCategories($appUrl, $categories) {
		$token=uniqid();
		OC_Util::setupFS(OCP\USER::getUser());
		self::addToken($token, $appUrl, $categories);
		foreach(explode(',', $categories) as $category) {
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
