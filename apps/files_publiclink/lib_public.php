<?php
class OC_PublicLink{
	/**
	 * create a new public link
	 * @param string path
	 * @param int (optional) expiretime time the link expires, as timestamp
	 */
	public function __construct($path,$expiretime=0){
		if($path and  OC_Filesystem::file_exists($path) and OC_Filesystem::is_readable($path)){
			$user=OC_User::getUser();
			$token=sha1("$user-$path-$expiretime");
			$query=OC_DB::prepare("INSERT INTO *PREFIX*publiclink VALUES(?,?,?,?)");
			$result=$query->execute(array($token,$path,$user,$expiretime));
			if( PEAR::isError($result)) {
				$entry = 'DB Error: "'.$result->getMessage().'"<br />';
				$entry .= 'Offending command was: '.$result->getDebugInfo().'<br />';
				error_log( $entry );
				die( $entry );
			}
			$this->token=$token;
		}
	}
	
	/**
	 * get the path of that shared file
	 */
	public static function getPath($token) {
		//get the path and the user
		$query=OC_DB::prepare("SELECT user,path FROM *PREFIX*publiclink WHERE token=?");
		$result=$query->execute(array($token));
		$data=$result->fetchAll();
		if(count($data)>0){
			$path=$data[0]['path'];
			$user=$data[0]['user'];
			
			//prepare the filesystem
			OC_Util::setupFS($user);
			
			return $path;
		}else{
			return false;
		}
	}
	
	/**
	 * get the token for the public link
	 * @return string
	 */
	public function getToken(){
		return $this->token;
	}

	public static function getLink($path) {
		$query=OC_DB::prepare("SELECT token FROM *PREFIX*publiclink WHERE user=? AND path=? LIMIT 1");
		$result=$query->execute(array(OC_User::getUser(),$path))->fetchAll();
		if(count($result)>0){
			return $result[0]['token'];
		}
	}

	/**
	 * gets all public links
	 * @return array
	 */
	static public function getLinks(){
		$query=OC_DB::prepare("SELECT * FROM *PREFIX*publiclink WHERE user=?");
		return $query->execute(array(OC_User::getUser()))->fetchAll();
	}

	/**
	 * delete a public link
	 */
	static public function delete($token){
		$query=OC_DB::prepare("SELECT user,path FROM *PREFIX*publiclink WHERE token=?");
		$result=$query->execute(array($token))->fetchAll();
		if(count($result)>0 and $result[0]['user']==OC_User::getUser()){
			$query=OC_DB::prepare("DELETE FROM *PREFIX*publiclink WHERE token=?");
			$query->execute(array($token));
		}
	}
	
	private $token;
}
?>
