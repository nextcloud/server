<?php

/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2011 Robin Appelman icewind1991@gmail.com
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
 * transparent encryption
 */

class OC_FileProxy_Encryption extends OC_FileProxy{
	private static $blackList=null; //mimetypes blacklisted from encryption
	private static $metaData=array(); //metadata cache
	
	/**
	 * check if a file should be encrypted during write
	 * @param string $path
	 * @return bool
	 */
	private static function shouldEncrypt($path){
		if(is_null(self::$blackList)){
			self::$blackList=explode(',',OC_Appconfig::getValue('files_encryption','type_blacklist','jpg,png,jpeg,avi,mpg,mpeg,mkv,mp3,oga,ogv,ogg'));
		}
		if(self::isEncrypted($path)){
			return true;
		}
		$extension=substr($path,strrpos($path,'.')+1);
		if(array_search($extension,self::$blackList)===false){
			return true;
		}
	}

	/**
	 * check if a file is encrypted
	 * @param string $path
	 * @return bool
	 */
	private static function isEncrypted($path){
		if(isset(self::$metaData[$path])){
			$metadata=self::$metaData[$path];
		}else{
			$metadata=OC_FileCache::getCached($path);
			self::$metaData[$path]=$metadata;
		}
		return (bool)$metadata['encrypted'];
	}
	
	public function preFile_put_contents($path,&$data){
		if(self::shouldEncrypt($path)){
			if (!is_resource($data)) {//stream put contents should have been converter to fopen
				$data=OC_Crypt::blockEncrypt($data);
				OC_FileCache::put($path,array('encrypted'=>true));
			}
		}
	}
	
	public function postFile_get_contents($path,$data){
		if(self::isEncrypted($path)){
			$data=OC_Crypt::blockDecrypt($data);
		}
		return $data;
	}
	
	public function postFopen($path,&$result){
		if(!$result){
			return $result;
		}
		$meta=stream_get_meta_data($result);
		if(self::isEncrypted($path)){
			fclose($result);
			$result=fopen('crypt://'.$path,$meta['mode']);
		}elseif(self::shouldEncrypt($path) and $meta['mode']!='r' and $meta['mode']!='rb'){
			if(OC_Filesystem::file_exists($path) and OC_Filesystem::filesize($path)>0){
				//first encrypt the target file so we don't end up with a half encrypted file
				OC_Log::write('files_encryption','Decrypting '.$path.' before writing',OC_Log::DEBUG);
				$tmp=fopen('php://temp');
				while(!feof($result)){
					$chunk=fread($result,8192);
					if($chunk){
						fwrite($tmp,$chunk);
					}
				}
				fclose($result);
				OC_Filesystem::file_put_contents($path,$tmp);
				fclose($tmp);
			}
			$result=fopen('crypt://'.$path,$meta['mode']);
		}
		return $result;
	}

	public function postGetMimeType($path,$mime){
		if(self::isEncrypted($path)){
			$mime=OC_Helper::getMimeType('crypt://'.$path,'w');
		}
		return $mime;
	}
}
