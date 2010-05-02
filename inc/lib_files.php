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
				$file['mime']=OC_FILES::getMimeType($directory .'/'. $filename);
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
	
	/**
	* try to detect the mime type of a file
	*
	* @param  string  file path
	* @return string  guessed mime type
	*/
	function getMimeType($fspath){
		if (@is_dir($fspath)) {
			// directories are easy
			return "httpd/unix-directory"; 
		} else if (function_exists("mime_content_type")) {
			// use mime magic extension if available
			$mime_type = mime_content_type($fspath);
		} else if (OC_FILES::canExecute("file")) {
			// it looks like we have a 'file' command, 
			// lets see it it does have mime support
			$fp = popen("file -i '$fspath' 2>/dev/null", "r");
			$reply = fgets($fp);
			pclose($fp);
			
			// popen will not return an error if the binary was not found
			// and find may not have mime support using "-i"
			// so we test the format of the returned string 
			
			// the reply begins with the requested filename
			if (!strncmp($reply, "$fspath: ", strlen($fspath)+2)) {                     
				$reply = substr($reply, strlen($fspath)+2);
				// followed by the mime type (maybe including options)
				if (preg_match('/^[[:alnum:]_-]+/[[:alnum:]_-]+;?.*/', $reply, $matches)) {
					$mime_type = $matches[0];
				}
			}
		} 
		if (empty($mime_type)) {
			// Fallback solution: try to guess the type by the file extension
			// TODO: add more ...
			switch (strtolower(strrchr(basename($fspath), "."))) {
			case ".html":
				$mime_type = "text/html";
				break;
			case ".txt":
				$mime_type = "text/plain";
				break;
			case ".css":
				$mime_type = "text/css";
				break;
			case ".gif":
				$mime_type = "image/gif";
				break;
			case ".jpg":
				$mime_type = "image/jpeg";
				break;
			case ".jpg":
				$mime_type = "png/jpeg";
				break;
			default: 
				$mime_type = "application/octet-stream";
				break;
			}
		}
		
		return $mime_type;
	}
	
	/**
	* detect if a given program is found in the search PATH
	*
	* helper function used by _mimetype() to detect if the 
	* external 'file' utility is available
	*
	* @param  string  program name
	* @param  string  optional search path, defaults to $PATH
	* @return bool    true if executable program found in path
	*/
	function canExecute($name, $path = false) 
	{
		// path defaults to PATH from environment if not set
		if ($path === false) {
			$path = getenv("PATH");
		}
		
		// check method depends on operating system
		if (!strncmp(PHP_OS, "WIN", 3)) {
			// on Windows an appropriate COM or EXE file needs to exist
			$exts = array(".exe", ".com");
			$check_fn = "file_exists";
		} else { 
			// anywhere else we look for an executable file of that name
			$exts = array("");
			$check_fn = "is_executable";
		}
		
		// now check the directories in the path for the program
		foreach (explode(PATH_SEPARATOR, $path) as $dir) {
			// skip invalid path entries
			if (!file_exists($dir)) continue;
			if (!is_dir($dir)) continue;

			// and now look for the file
			foreach ($exts as $ext) {
				if ($check_fn("$dir/$name".$ext)) return true;
			}
		}

		return false;
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