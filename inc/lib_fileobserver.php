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
*/

define('OC_FILEACTION_WRITE',2);
define('OC_FILEACTION_READ',4);
define('OC_FILEACTION_DELETE',8);
define('OC_FILEACTION_CREATE',16);
define('OC_FILEACTION_RENAME',32);

/**
 * base class for file observers
 */
class OC_FILEOBSERVER{
	private $mask;
	
	public function __construct($arguments){}
	
	public function __get($name){
		switch($name){
			case 'mask':
				return $this->mask;
		}
	}
	
	public function notify($path,$action){}
}

/**
 * observer that makes automatic backups
 */
class OC_FILEOBSERVER_BACKUP extends OC_FILEOBSERVER{
	private $storage;
	
	public function __construct($arguments){
		$this->mask=OC_FILEACTION_WRITE+OC_FILEACTION_DELETE+OC_FILEACTION_CREATE+OC_FILEACTION_RENAME;
		$this->storage=$arguments['storage'];
	}
	
	public function notify($path,$action,$storage){
		switch($action){
			case OC_FILEACTION_DELETE:
				if($storage->is_dir($path)){
					$this->storage->delTree($path);
				}else{
					$this->storage->unlink($path);
				}
				break;
			case OC_FILEACTION_CREATE:
				if($storage->is_dir($path)){
					$this->storage->mkdir($path);
					break;
				}
			case OC_FILEACTION_WRITE:
				$tmpFile=$storage->toTmpFile($path);
				$this->storage->fromTmpFile($tmpFile,$path);
				break;
			case OC_FILEACTION_RENAME:
				list($source,$target)=explode('->',$path);
				$this->storage->rename($source,$target);
		}
	}
}
?>