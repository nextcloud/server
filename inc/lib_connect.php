<?php

/**
* ownCloud
*
* @author Frank Karlitschek 
* @copyright 2010 Frank Karlitschek karlitschek@kde.org 
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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

/**
 * Class for connecting multiply ownCloud installations
 *
 */
class OC_CONNECT{
	static private $clouds=array();
	
	static function connect($path,$user,$password){
		$cloud=new OC_REMOTE_CLOUD($path,$user,$password);
		if($cloud->connected){
			self::$clouds[$path]=$cloud;
			return $cloud;
		}else{
			return false;
		}
	}
}


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
			$this->cookiefile=sys_get_temp_dir().'/remoteCloudCookie'.uniqid();
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
		$result=trim(curl_exec($ch));
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
			$this->cookiefile=sys_get_temp_dir().'/remoteCloudCookie'.uniqid();
		}
		$tmpfile=tempnam(sys_get_temp_dir(),'remoteCloudFile');
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
		global $WEBROOT;
		$source=$sourceDir.'/'.$sourceFile;
		$tmp=OC_FILESYSTEM::toTmpFile($source);
		$token=sha1(uniqid().$source);
		$file=sys_get_temp_dir().'/'.'remoteCloudFile'.$token;
		rename($tmp,$file);
		if((isset($CONFIG_HTTPFORCESSL) and $CONFIG_HTTPFORCESSL) or isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') { 
			$url = "https://". $_SERVER['SERVER_NAME'] . $WEBROOT;
		}else{
			$url = "http://". $_SERVER['SERVER_NAME'] . $WEBROOT;
		}
		return $this->apiCall('pull',array('dir'=>$targetDir,'file'=>$targetFile,'token'=>$token,'source'=>$url),true);
	}
}

function OC_CONNECT_TEST($path,$user,$password){
	echo 'connecting...';
	$remote=OC_CONNECT::connect($path,$user,$password);
	if($remote->connected){
		echo 'done<br/>';
		if($remote->isLoggedIn()){
			echo 'logged in, session working<br/>';
			echo 'trying to get remote files...';
			$files=$remote->getFiles('');
			if($files){
				echo count($files).' files found:<br/>';
				foreach($files as $file){
					echo "{$file['type']} {$file['name']}: {$file['size']} bytes<br/>";
				}
				echo 'getting file "'.$file['name'].'"...';
				$size=$file['size'];
				$file=$remote->getFile('',$file['name']);
				if(file_exists($file)){
					$newSize=filesize($file);
					if($size!=$newSize){
						echo "fail<br/>Error: $newSize bytes received, $size expected.";
						echo '<br/><br/>Recieved file:<br/>';
						readfile($file);
						unlink($file);
						return;
					}
					OC_FILESYSTEM::fromTmpFile($file,'/remoteFile');
					echo 'done<br/>';
					echo 'sending file "burning_avatar.png"...';
					$res=$remote->sendFile('','burning_avatar.png','','burning_avatar.png');
					if($res){
						echo 'done<br/>';
					}else{
						echo 'fail<br/>';
					}
				}else{
					echo 'fail<br/>';
				}
			}else{
				echo 'fail<br/>';
			}
		}else{
			echo 'no longer logged in, session fail<br/>';
		}
	}else{
		echo 'fail<br/>';
	}
	$remote->disconnect();
	die();
}


?>
