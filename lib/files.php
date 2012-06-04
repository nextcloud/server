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
 * Class for fileserver access
 *
 */
class OC_Files {
	static $tmpFiles=array();

	/**
	* get the content of a directory
	* @param dir $directory
	*/
  public static function getDirectoryContent($directory, $mimetype_filter = ''){
		if(strpos($directory,OC::$CONFIG_DATADIRECTORY)===0){
			$directory=substr($directory,strlen(OC::$CONFIG_DATADIRECTORY));
		}
    $files=OC_FileCache::getFolderContent($directory, '', $mimetype_filter);
		foreach($files as &$file){
			$file['directory']=$directory;
			$file['type']=($file['mimetype']=='httpd/unix-directory')?'dir':'file';
		}
		usort($files, "fileCmp");//TODO: remove this once ajax is merged
		return $files;
	}



	/**
	* return the content of a file or return a zip file containning multiply files
	*
	* @param dir  $dir
	* @param file $file ; seperated list of files to download
	* @param boolean $only_header ; boolean to only send header of the request
	*/
	public static function get($dir,$files, $only_header = false){
		if(strpos($files,';')){
			$files=explode(';',$files);
		}

		if(is_array($files)){
			self::validateZipDownload($dir,$files);
			$executionTime = intval(ini_get('max_execution_time'));
			set_time_limit(0);
			$zip = new ZipArchive();
			$filename = OC_Helper::tmpFile('.zip');
			if ($zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)!==TRUE) {
				exit("cannot open <$filename>\n");
			}
			foreach($files as $file){
				$file=$dir.'/'.$file;
				if(OC_Filesystem::is_file($file)){
					$tmpFile=OC_Filesystem::toTmpFile($file);
					self::$tmpFiles[]=$tmpFile;
					$zip->addFile($tmpFile,basename($file));
				}elseif(OC_Filesystem::is_dir($file)){
					self::zipAddDir($file,$zip);
				}
			}
			$zip->close();
			set_time_limit($executionTime);
		}elseif(OC_Filesystem::is_dir($dir.'/'.$files)){
			self::validateZipDownload($dir,$files);
			$executionTime = intval(ini_get('max_execution_time'));
			set_time_limit(0);
			$zip = new ZipArchive();
			$filename = OC_Helper::tmpFile('.zip');
			if ($zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)!==TRUE) {
				exit("cannot open <$filename>\n");
			}
			$file=$dir.'/'.$files;
			self::zipAddDir($file,$zip);
			$zip->close();
			set_time_limit($executionTime);
		}else{
			$zip=false;
			$filename=$dir.'/'.$files;
		}
		@ob_end_clean();
		if($zip or OC_Filesystem::is_readable($filename)){
			header('Content-Disposition: attachment; filename="'.basename($filename).'"');
			header('Content-Transfer-Encoding: binary');
			OC_Response::disableCaching();
			if($zip){
				ini_set('zlib.output_compression', 'off');
				header('Content-Type: application/zip');
				header('Content-Length: ' . filesize($filename));
			}else{
				$fileData=OC_FileCache::get($filename);
				header('Content-Type: ' . $fileData['mimetype']);
			}
		}elseif($zip or !OC_Filesystem::file_exists($filename)){
			header("HTTP/1.0 404 Not Found");
			$tmpl = new OC_Template( '', '404', 'guest' );
			$tmpl->assign('file',$filename);
			$tmpl->printPage();
		}else{
			header("HTTP/1.0 403 Forbidden");
			die('403 Forbidden');
		}
		if($only_header){
			if(!$zip)
				header("Content-Length: ".OC_Filesystem::filesize($filename));
			return ;
		}
		if($zip){
			$handle=fopen($filename,'r');
			if ($handle) {
				$chunkSize = 8*1024;// 1 MB chunks
				while (!feof($handle)) {
					echo fread($handle, $chunkSize);
					flush();
				}
			}
			unlink($filename);
		}else{
			OC_Filesystem::readfile($filename);
		}
		foreach(self::$tmpFiles as $tmpFile){
			if(file_exists($tmpFile) and is_file($tmpFile)){
				unlink($tmpFile);
			}
		}
	}

	public static function zipAddDir($dir,$zip,$internalDir=''){
		$dirname=basename($dir);
		$zip->addEmptyDir($internalDir.$dirname);
		$internalDir.=$dirname.='/';
		$files=OC_Files::getdirectorycontent($dir);
		foreach($files as $file){
			$filename=$file['name'];
			$file=$dir.'/'.$filename;
			if(OC_Filesystem::is_file($file)){
				$tmpFile=OC_Filesystem::toTmpFile($file);
				OC_Files::$tmpFiles[]=$tmpFile;
				$zip->addFile($tmpFile,$internalDir.$filename);
			}elseif(OC_Filesystem::is_dir($file)){
				self::zipAddDir($file,$zip,$internalDir);
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
		if(OC_User::isLoggedIn() && ($sourceDir != '' || $source != 'Shared')){
			$targetFile=self::normalizePath($targetDir.'/'.$target);
			$sourceFile=self::normalizePath($sourceDir.'/'.$source);
			return OC_Filesystem::rename($sourceFile,$targetFile);
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
		if(OC_User::isLoggedIn()){
			$targetFile=$targetDir.'/'.$target;
			$sourceFile=$sourceDir.'/'.$source;
			return OC_Filesystem::copy($sourceFile,$targetFile);
		}
	}

	/**
	* create a new file or folder
	*
	* @param dir  $dir
	* @param file $name
	* @param type $type
	*/
	public static function newFile($dir,$name,$type){
		if(OC_User::isLoggedIn()){
			$file=$dir.'/'.$name;
			if($type=='dir'){
				return OC_Filesystem::mkdir($file);
			}elseif($type=='file'){
				$fileHandle=OC_Filesystem::fopen($file, 'w');
				if($fileHandle){
					fclose($fileHandle);
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
		if(OC_User::isLoggedIn() && ($dir!= '' || $file != 'Shared')) {
			$file=$dir.'/'.$file;
			return OC_Filesystem::unlink($file);
		}
	}

	/**
	* checks if the selected files are within the size constraint. If not, outputs an error page.
	*
	* @param dir   $dir
	* @param files $files
	*/
	static function validateZipDownload($dir, $files) {
		if(!OC_Config::getValue('allowZipDownload', true)) {
			$l = OC_L10N::get('files');
			header("HTTP/1.0 409 Conflict");
			$tmpl = new OC_Template( '', 'error', 'user' );
			$errors = array(
				array(
					'error' => $l->t('ZIP download is turned off.'),
					'hint' => $l->t('Files need to be downloaded one by one.') . '<br/><a href="javascript:history.back()">' . $l->t('Back to Files') . '</a>',
				)
			);
			$tmpl->assign('errors', $errors);
			$tmpl->printPage();
			exit;
		}

		$zipLimit = OC_Config::getValue('maxZipInputSize', OC_Helper::computerFileSize('800 MB'));
		if($zipLimit > 0) {
			$totalsize = 0;
			if(is_array($files)){
				foreach($files as $file){
					$totalsize += OC_Filesystem::filesize($dir.'/'.$file);
				}
			}else{
				$totalsize += OC_Filesystem::filesize($dir.'/'.$files);
			}
			if($totalsize > $zipLimit) {
				$l = OC_L10N::get('files');
				header("HTTP/1.0 409 Conflict");
				$tmpl = new OC_Template( '', 'error', 'user' );
				$errors = array(
					array(
						'error' => $l->t('Selected files too large to generate zip file.'),
						'hint' => 'Download the files in smaller chunks, seperately or kindly ask your administrator.<br/><a href="javascript:history.back()">' . $l->t('Back to Files') . '</a>',
					)
				);
				$tmpl->assign('errors', $errors);
				$tmpl->printPage();
				exit;
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
		return OC_Filesystem::getMimeType($path);
	}

	/**
	* get a file tree
	*
	* @param  string  path
	* @return array
	*/
	static function getTree($path){
		return OC_Filesystem::getTree($path);
	}

	/**
	* pull a file from a remote server
	* @param  string  source
	* @param  string  token
	* @param  string  dir
	* @param  string  file
	* @return string  guessed mime type
	*/
	static function pull($source,$token,$dir,$file){
		$tmpfile=tempnam(get_temp_dir(),'remoteCloudFile');
		$fp=fopen($tmpfile,'w+');
		$url=$source.="/files/pull.php?token=$token";
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		fclose($fp);
		$info=curl_getinfo($ch);
		$httpCode=$info['http_code'];
		curl_close($ch);
		if($httpCode==200 or $httpCode==0){
			OC_Filesystem::fromTmpFile($tmpfile,$dir.'/'.$file);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * set the maximum upload size limit for apache hosts using .htaccess
	 * @param int size filesisze in bytes
	 * @return false on failure, size on success
	 */
	static function setUploadLimit($size){
		//don't allow user to break his config -- upper boundary
		if($size > PHP_INT_MAX) {
			//max size is always 1 byte lower than computerFileSize returns
			if($size > PHP_INT_MAX+1)
				return false;
			$size -=1;
		} else {
			$size=OC_Helper::humanFileSize($size);
			$size=substr($size,0,-1);//strip the B
			$size=str_replace(' ','',$size); //remove the space between the size and the postfix
		}

		//don't allow user to break his config -- broken or malicious size input
		if(intval($size) == 0) {
			return false;
		}

		$htaccess = @file_get_contents(OC::$SERVERROOT.'/.htaccess'); //supress errors in case we don't have permissions for
		if(!$htaccess) {
			return false;
		}

		$phpValueKeys = array(
			'upload_max_filesize',
			'post_max_size'
		);

		foreach($phpValueKeys as $key) {
		    $pattern = '/php_value '.$key.' (\S)*/';
		    $setting = 'php_value '.$key.' '.$size;
		    $hasReplaced = 0;
		    $content = preg_replace($pattern, $setting, $htaccess, 1, $hasReplaced);
		    if($content !== NULL) {
				$htaccess = $content;
			}
			if($hasReplaced == 0) {
				$htaccess .= "\n" . $setting;
			}
		}

		//supress errors in case we don't have permissions for it
		if(@file_put_contents(OC::$SERVERROOT.'/.htaccess', $htaccess)) {
			return OC_Helper::computerFileSize($size);
		}
		return false;
	}

	/**
	 * normalize a path, removing any double, add leading /, etc
	 * @param string $path
	 * @return string
	 */
	static public function normalizePath($path){
		$path='/'.$path;
		$old='';
		while($old!=$path){//replace any multiplicity of slashes with a single one
			$old=$path;
			$path=str_replace('//','/',$path);
		}
		return $path;
	}
}

function fileCmp($a,$b){
	if($a['type']=='dir' and $b['type']!='dir'){
		return -1;
	}elseif($a['type']!='dir' and $b['type']=='dir'){
		return 1;
	}else{
		return strnatcasecmp($a['name'],$b['name']);
	}
}
