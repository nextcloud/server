<?php
/**
 * Class for connection to a remote owncloud installation
 *
 */
class OC_REMOTE_CLOUD{
	private $path;
	private $connected=false;
	private $cookiefile=false;

	/**
	* make an api call to the remote cloud
	* @param string $action
	* @param array parameters
	* @param bool assoc   when set to true, the result will be parsed as associative array
	*
	*/
	private function apiCall($action,$parameters=false,$assoc=false){
		if(!$this->cookiefile){
			$this->cookiefile=get_temp_dir().'/remoteCloudCookie'.uniqid();
		}
		$url=$this->path.='/files/api.php';
		$fields_string="action=$action&";
		if(is_array($parameters)){
			foreach($parameters as $key=>$value){
				$fields_string.=$key.'='.$value.'&';
			}
			rtrim($fields_string,'&');
		}
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,count($parameters));
		curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($ch, CURLOPT_COOKIEFILE,$this->cookiefile);
		curl_setopt($ch, CURLOPT_COOKIEJAR,$this->cookiefile);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$result=curl_exec($ch);
		$result=trim($result);
		$info=curl_getinfo($ch);
		$httpCode=$info['http_code'];
		curl_close($ch);
		if($httpCode==200 or $httpCode==0){
			return json_decode($result,$assoc);
		}else{
			return false;
		}
	}

	public function __construct($path,$user,$password){
		$this->path=$path;
		$this->connected=$this->apiCall('login',array('username'=>$user,'password'=>$password));
	}

	/**
	* check if we are stull logged in on the remote cloud
	*
	*/
	public function isLoggedIn(){
		if(!$this->connected){
			return false;
		}
		return $this->apiCall('checklogin');
	}

	public function __get($name){
		switch($name){
			case 'connected':
				return $this->connected;
		}
	}

	/**
	* disconnect from the remote cloud
	*
	*/
	public function disconnect(){
		$this->connected=false;
		if(is_file($this->cookiefile)){
			unlink($this->cookiefile);
		}
		$this->cookiefile=false;
	}

	/**
	* create a new file or directory
	* @param string $dir
	* @param string $name
	* @param string $type
	*/
	public function newFile($dir,$name,$type){
		if(!$this->connected){
			return false;
		}
		return $this->apiCall('new',array('dir'=>$dir,'name'=>$name,'type'=>$type),true);
	}

	/**
	* deletes a file or directory
	* @param string $dir
	* @param string $file
	*/
	public function delete($dir,$name){
		if(!$this->connected){
			return false;
		}
		return $this->apiCall('delete',array('dir'=>$dir,'file'=>$name),true);
	}

	/**
	* moves a file or directory
	* @param string $sorceDir
	* @param string $sorceFile
	* @param string $targetDir
	* @param string $targetFile
	*/
	public function move($sourceDir,$sourceFile,$targetDir,$targetFile){
		if(!$this->connected){
			return false;
		}
		return $this->apiCall('move',array('sourcedir'=>$sourceDir,'source'=>$sourceFile,'targetdir'=>$targetDir,'target'=>$targetFile),true);
	}

	/**
	* copies a file or directory
	* @param string $sorceDir
	* @param string $sorceFile
	* @param string $targetDir
	* @param string $targetFile
	*/
	public function copy($sourceDir,$sourceFile,$targetDir,$targetFile){
		if(!$this->connected){
			return false;
		}
		return $this->apiCall('copy',array('sourcedir'=>$sourceDir,'source'=>$sourceFile,'targetdir'=>$targetDir,'target'=>$targetFile),true);
	}

	/**
	* get a file tree
	* @param string $dir
	*/
	public function getTree($dir){
		if(!$this->connected){
			return false;
		}
		return $this->apiCall('gettree',array('dir'=>$dir),true);
	}

	/**
	* get the files inside a directory of the remote cloud
	* @param string $dir
	*/
	public function getFiles($dir){
		if(!$this->connected){
			return false;
		}
		return $this->apiCall('getfiles',array('dir'=>$dir),true);
	}

	/**
	* get a remove file and save it in a temporary file and return the path of the temporary file
	* @param string $dir
	* @param string $file
	* @return string
	*/
	public function getFile($dir, $file){
		if(!$this->connected){
			return false;
		}
		$ch=curl_init();
		if(!$this->cookiefile){
			$this->cookiefile=get_temp_dir().'/remoteCloudCookie'.uniqid();
		}
		$tmpfile=tempnam(get_temp_dir(),'remoteCloudFile');
		$fp=fopen($tmpfile,'w+');
		$url=$this->path.="/files/api.php?action=get&dir=$dir&file=$file";
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_COOKIEFILE,$this->cookiefile);
		curl_setopt($ch, CURLOPT_COOKIEJAR,$this->cookiefile);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		fclose($fp);
		curl_close($ch);
		return $tmpfile;
	}

	public function sendFile($sourceDir,$sourceFile,$targetDir,$targetFile){
		$source=$sourceDir.'/'.$sourceFile;
		$tmp=OC_Filesystem::toTmpFile($source);
		return $this->sendTmpFile($tmp,$targetDir,$targetFile);
	}

	public function sendTmpFile($tmp,$targetDir,$targetFile){
		$token=sha1(uniqid().$tmp);
		$file=get_temp_dir().'/'.'remoteCloudFile'.$token;
		rename($tmp,$file);
		if( OC_Config::getValue( "forcessl", false ) or isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
			$url = "https://". $_SERVER['SERVER_NAME'] . OC::$WEBROOT;
		}else{
			$url = "http://". $_SERVER['SERVER_NAME'] . OC::$WEBROOT;
		}
		return $this->apiCall('pull',array('dir'=>$targetDir,'file'=>$targetFile,'token'=>$token,'source'=>$url),true);
	}
}
 
