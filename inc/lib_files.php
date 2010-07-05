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

oc_require_once("lib_log.php");


/**
 * Class for fileserver access
 *
 */
class OC_FILES {
	static $tmpFiles=array();
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
		global $CONFIG_DATADIRECTORY;
		if(strpos($directory,$CONFIG_DATADIRECTORY)===0){
			$directory=substr($directory,strlen($CONFIG_DATADIRECTORY));
		}
		$filesfound=true;
		$content=array();
		$dirs=array();
		$file=array();
		$files=array();
		if (OC_FILESYSTEM::is_dir($directory)) {
			if ($dh = OC_FILESYSTEM::opendir($directory)) {
			while (($filename = readdir($dh)) !== false) {
				if($filename<>'.' and $filename<>'..' and substr($filename,0,1)!='.'){
					$file=array();
					$filesfound=true;
					$file['name']=$filename;
					$file['directory']=$directory;
					$stat=OC_FILESYSTEM::stat($directory.'/'.$filename);
					$file=array_merge($file,$stat);
					$file['mime']=OC_FILES::getMimeType($directory .'/'. $filename);
					$file['readable']=OC_FILESYSTEM::is_readable($directory .'/'. $filename);
					$file['writeable']=OC_FILESYSTEM::is_writeable($directory .'/'. $filename);
					$file['type']=OC_FILESYSTEM::filetype($directory .'/'. $filename);
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
		if(strpos($files,';')){
			$files=explode(';',$files);
		}
		if(is_array($files)){
			$zip = new ZipArchive();
			$filename = sys_get_temp_dir()."/ownCloud.zip";
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
				exit("cannot open <$filename>\n");
			}
			foreach($files as $file){
				$file=$dir.'/'.$file;
				if(OC_FILESYSTEM::is_file($file)){
					$tmpFile=OC_FILESYSTEM::toTmpFile($file);
					self::$tmpFiles[]=$tmpFile;
					$zip->addFile($tmpFile,basename($file));
				}elseif(OC_FILESYSTEM::is_dir($file)){
					zipAddDir($file,$zip);
				}
			}
			$zip->close();
		}elseif(OC_FILESYSTEM::is_dir($dir.'/'.$files)){
			$zip = new ZipArchive();
			$filename = sys_get_temp_dir()."/ownCloud.zip";
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
				exit("cannot open <$filename>\n");
			}
			$file=$dir.'/'.$files;
			zipAddDir($file,$zip);
			$zip->close();
		}else{
			$zip=false;
			$filename=$dir.'/'.$files;
		}
		if($zip or OC_FILESYSTEM::is_readable($filename)){
			header('Content-Disposition: attachment; filename='.basename($filename));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filename));
		}elseif($zip or !OC_FILESYSTEM::file_exists($filename)){
			header("HTTP/1.0 404 Not Found");
			die('404 Not Found');
		}else{
			header("HTTP/1.0 403 Forbidden");
			die('403 Forbidden');
		}
		ob_end_clean();
		OC_LOG::event($_SESSION['username'],3,"$dir/$files");
		if($zip){
			readfile($filename);
			unlink($filename);
		}else{
			OC_FILESYSTEM::readfile($filename);
		}
		foreach(self::$tmpFiles as $tmpFile){
			if(file_exists($tmpFile) and is_file($tmpFile)){
				unlink($tmpFile);
			}
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
		if(OC_USER::isLoggedIn()){
			$targetFile=$targetDir.'/'.$target;
			$sourceFile=$sourceDir.'/'.$source;
			return OC_FILESYSTEM::rename($sourceFile,$targetFile);
		}
	}

	/**
	* copy a file or folder
	*
	* @param dir  $sourceDir
	* @param file $source
	* @param dir  $targetDir
	* @param file $target
	*/
	public static function copy($sourceDir,$source,$targetDir,$target){
		if(OC_USER::isLoggedIn()){
			$targetFile=$targetDir.'/'.$target;
			$sourceFile=$sourceDir.'/'.$source;
			return OC_FILESYSTEM::copy($sourceFile,$targetFile);
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
		if(OC_USER::isLoggedIn()){
			$file=$dir.'/'.$name;
			if($type=='dir'){
				return OC_FILESYSTEM::mkdir($file);
			}elseif($type=='file'){
				$fileHandle=OC_FILESYSTEM::fopen($file, 'w');
				if($fileHandle){
					fclose($fileHandle);
					OC_LOG::event($_SESSION['username'],4,"$dir/$name");
					return true;
				}else{
					return false;
				}
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
		if(OC_USER::isLoggedIn()){
			$file=$dir.'/'.$file;
			if(OC_FILESYSTEM::is_file($file)){
				return OC_FILESYSTEM::unlink($file);
			}elseif(OC_FILESYSTEM::is_dir($file)){
				return OC_FILESYSTEM::delTree($file);
			}
		}
	}

	/**
	* try to detect the mime type of a file
	*
	* @param  string  path
	* @return string  guessed mime type
	*/
	static function getMimeType($path){
		return OC_FILESYSTEM::getMimeType($path);
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
        if(OC_FILESYSTEM::is_file($file)){
			$tmpFile=OC_FILESYSTEM::toTmpFile($file);
			OC_FILES::$tmpFiles[]=$tmpFile;
            $zip->addFile($tmpFile,$internalDir.$filename);
        }elseif(OC_FILESYSTEM::is_dir($file)){
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