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
* You should have received a copy of the GNU Affero General Public
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
