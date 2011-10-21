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
	public function preFile_put_contents($path,&$data){
		if(substr($path,-4)=='.enc'){
			OC_Log::write('files_encryption','file put contents',OC_Log::DEBUG);
			if (is_resource($data)) {
				$newData='';
				while(!feof($data)){
					$block=fread($data,8192);
					$newData.=OC_Crypt::encrypt($block);
				}
				$data=$newData;
			}else{
				$data=OC_Crypt::blockEncrypt($data);
			}
		}
	}
	
	public function postFile_get_contents($path,$data){
		if(substr($path,-4)=='.enc'){
			OC_Log::write('files_encryption','file get contents',OC_Log::DEBUG);
			return OC_Crypt::blockDecrypt($data);
		}
	}
	
	public function postFopen($path,&$result){
		if(substr($path,-4)=='.enc'){
			OC_Log::write('files_encryption','fopen',OC_Log::DEBUG);
			fclose($result);
			$result=fopen('crypt://'.substr($path,0,-4));//remove the .enc extention so we don't catch the fopen request made by cryptstream
		}
	}
	
	public function preReadFile($path){
		if(substr($path,-4)=='.enc'){
			OC_Log::write('files_encryption','readline',OC_Log::DEBUG);
			$stream=fopen('crypt://'.substr($path,0,-4));
			while(!feof($stream)){
				print(fread($stream,8192));
			}
			return false;//cancel the original request
		}
	}
}
