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
 * Class for fileserver access
 *
 */
class OC_FILES {

	/**
	* show a web GUI filebrowser
	*
	* @param basedir $basedir
	* @param dir $dir
	*/
	public static function showbrowser($basedir,$dir){
	echo '<div id="content"></div>';
	}

	/**
	* get the content of a directory
	* @param dir $directory
	*/
	public static function getdirectorycontent($directory){
		$filesfound=true;
		$content=array();
		$dirs=array();
		$file=array();
		$files=array();
		if (is_dir($directory)) {
			if ($dh = opendir($directory)) {
			while (($filename = readdir($dh)) !== false) {
				if($filename<>'.' and $filename<>'..'){
				$file=array();
				$filesfound=true;
				$file['name']=$filename;
				$file['directory']=$directory;
				$stat=stat($directory.'/'.$filename);
				$file=array_merge($file,$stat);
				$file['type']=filetype($directory .'/'. $filename);
				if($file['type']=='dir'){
					$dirs[$file['name']]=$file;
				}else{
					$files[$file['name']]=$file;
				}
				}
			}
			closedir($dh);
			}
		}
		ksort($dirs);
		ksort($files);
		$content=array_merge($dirs,$files);
		if($filesfound){
			return $content;
		}else{
			return false;
		}
	}



	/**
	* return the content of a file or return a zip file containning multiply files
	*
	* @param dir  $dir
	* @param file $file
	*/
	public static function get($dir,$files){
		global $CONFIG_DATADIRECTORY;
		if(strstr($files,'..') or strstr($dir,'..')){
			die();
		}
		if(is_array($files)){
			$zip = new ZipArchive();
			$filename = sys_get_temp_dir()."/ownCloud.zip";
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
				exit("cannot open <$filename>\n");
			}
			foreach($files as $file){
				$file=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$file;
				if(is_file($file)){
					$zip->addFile($file,basename($file));
				}elseif(is_dir($file)){
					zipAddDir($file,$zip);
				}
			}
			$zip->close();
		}elseif(is_dir($CONFIG_DATADIRECTORY.'/'.$dir.'/'.$files)){
			$zip = new ZipArchive();
			$filename = sys_get_temp_dir()."/ownCloud.zip";
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
				exit("cannot open <$filename>\n");
			}
			$file=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$files;
			zipAddDir($file,$zip);
			$zip->close();
		}else{
			$zip=false;
			$filename=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$files;
		}
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($filename));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filename));
		ob_end_clean();
		readfile($filename);
		if($zip){
			unlink($filename);
		}
	}
	
	/**
	* move a file or folder
	*
	* @param dir  $sourceDir
	* @param file $source
	* @param dir  $targetDir
	* @param file $target
	*/
	public static function move($sourceDir,$source,$targetDir,$target){
		global $CONFIG_DATADIRECTORY;
		if(OC_USER::isLoggedIn() and strpos($sourceDir,'..')===false and strpos($source,'..')===false and strpos($targetDir,'..')===false and strpos($target,'..')===false){
			$targetFile=$CONFIG_DATADIRECTORY.'/'.$targetDir.'/'.$target;
			$sourceFile=$CONFIG_DATADIRECTORY.'/'.$sourceDir.'/'.$source;
			rename($sourceFile,$targetFile);
		}
	}
	
	/**
	* create a new file or folder
	*
	* @param dir  $dir
	* @param file $name
	* @param type $type
	*/
	public static function newfile($dir,$name,$type){
		global $CONFIG_DATADIRECTORY;
		if(OC_USER::isLoggedIn() and strpos($dir,'..')===false and strpos($name,'..')===false){
			$file=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$name;
			if($type=='dir'){
				mkdir($file);
			}elseif($type=='file'){
				$fileHandle=fopen($file, 'w') or die("can't open file");
				fclose($fileHandle);
			}
		}
	}
	
	/**
	* deletes a file or folder
	*
	* @param dir  $dir
	* @param file $name
	*/
	public static function delete($dir,$file){
		global $CONFIG_DATADIRECTORY;
		if(OC_USER::isLoggedIn() and strpos($dir,'..')===false){
			$file=$CONFIG_DATADIRECTORY.'/'.$dir.'/'.$file;
			if(is_file($file)){
				unlink($file);
			}elseif(is_dir($file)){
				rmdir($file);
			}
		}
	}
}

function zipAddDir($dir,$zip,$internalDir=''){
    $dirname=basename($dir);
    $zip->addEmptyDir($internalDir.$dirname);
    $internalDir.=$dirname.='/';
    $files=OC_FILES::getdirectorycontent($dir);
    foreach($files as $file){
        $filename=$file['name'];
        $file=$dir.'/'.$filename;
        if(is_file($file)){
            $zip->addFile($file,$internalDir.$filename);
        }elseif(is_dir($file)){
            zipAddDir($file,$zip,$internalDir);
        }
    }
}

if(!function_exists('sys_get_temp_dir')) {
    function sys_get_temp_dir() {
        if( $temp=getenv('TMP') )        return $temp;
        if( $temp=getenv('TEMP') )        return $temp;
        if( $temp=getenv('TMPDIR') )    return $temp;
        $temp=tempnam(__FILE__,'');
        if (file_exists($temp)) {
          unlink($temp);
          return dirname($temp);
        }
        return null;
    }
}

?>