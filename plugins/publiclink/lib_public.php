<?php
class OC_PublicLink{
	/**
	 * create a new public link
	 * @param string path
	 * @param int (optional) expiretime time the link expires, as timestamp
	 */
	public function __construct($path,$expiretime=0){
		if($path && OC_FILESYSTEM::file_exists($path)){
			$token=sha1("$path-$expiretime");
			$user=$_SESSION['user_id'];
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
	 * download a file shared by a public link
	 * @param string token
	 */
	public static function downloadFile($token){
		//remove expired links
		$query=OC_DB::prepare("DELETE FROM *PREFIX*publiclink WHERE expire_time < NOW() AND expire_time!=0");
		$query->execute();
		
		//get the path and the user
		$query=OC_DB::prepare("SELECT user,path FROM *PREFIX*publiclink WHERE token=?");
		$result=$query->execute(array($token));
		$data=$result->fetchAll();
		if(count($data)>0){
			$path=$data[0]['path'];
			$user=$data[0]['user'];
			
			//prepare the filesystem
			OC_UTIL::setupFS($user);
			
			//get time mimetype and set the headers
			$mimetype=OC_FILESYSTEM::getMimeType($path);
	// 		header('Content-Disposition: attachment; filename="'.basename($path).'"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Type: ' . $mimetype);
			header('Content-Length: ' . OC_FILESYSTEM::filesize($path));
			
			//download the file
			ob_clean();
			OC_FILESYSTEM::readfile($path);
		}else{
			header("HTTP/1.0 404 Not Found");
			echo '404 Not Found';
			die();
		}
	}
	
	/**
	 * get the token for the public link
	 * @return string
	 */
	public function getToken(){
		return $this->token;
	}
	
	private $token;
}
?>