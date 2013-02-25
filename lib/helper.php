<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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
 * Collection of useful functions
 */
class OC_Helper {
	private static $mimetypes=array();
	private static $tmpFiles=array();

	/**
	 * @brief Creates an url using a defined route
	 * @param $route
	 * @param array $parameters
	 * @return
	 * @internal param array $args with param=>value, will be appended to the returned url
	 * @returns the url
	 *
	 * Returns a url to the given app and file.
	 */
	public static function linkToRoute( $route, $parameters = array() ) {
		$urlLinkTo = OC::getRouter()->generate($route, $parameters);
		return $urlLinkTo;
	}

	/**
	 * @brief Creates an url
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 * 	The value of $args will be urlencoded
	 * @return string the url
	 *
	 * Returns a url to the given app and file.
	 */
	public static function linkTo( $app, $file, $args = array() ) {
		if( $app != '' ) {
			$app_path = OC_App::getAppPath($app);
			// Check if the app is in the app folder
			if( $app_path && file_exists( $app_path.'/'.$file )) {
				if(substr($file, -3) == 'php' || substr($file, -3) == 'css') {
					$urlLinkTo =  OC::$WEBROOT . '/index.php/apps/' . $app;
					$urlLinkTo .= ($file!='index.php') ? '/' . $file : '';
				}else{
					$urlLinkTo =  OC_App::getAppWebPath($app) . '/' . $file;
				}
			}
			else{
				$urlLinkTo =  OC::$WEBROOT . '/' . $app . '/' . $file;
			}
		}
		else{
			if( file_exists( OC::$SERVERROOT . '/core/'. $file )) {
				$urlLinkTo =  OC::$WEBROOT . '/core/'.$file;
			}
			else{
				$urlLinkTo =  OC::$WEBROOT . '/'.$file;
			}
		}

		if ($args && $query = http_build_query($args, '', '&')) {
			$urlLinkTo .= '?'.$query;
		}

		return $urlLinkTo;
	}

	/**
	 * @brief Creates an absolute url
	 * @param string $app app
	 * @param string $file file
	 * @param array $args array with param=>value, will be appended to the returned url
	 * 	The value of $args will be urlencoded
	 * @return string the url
	 *
	 * Returns a absolute url to the given app and file.
	 */
	public static function linkToAbsolute( $app, $file, $args = array() ) {
		$urlLinkTo = self::linkTo( $app, $file, $args );
		return self::makeURLAbsolute($urlLinkTo);
	}

	/**
	 * @brief Makes an $url absolute
	 * @param string $url the url
	 * @return string the absolute url
	 *
	 * Returns a absolute url to the given app and file.
	 */
	public static function makeURLAbsolute( $url )
	{
		return OC_Request::serverProtocol(). '://'  . OC_Request::serverHost() . $url;
	}

	/**
	 * @brief Creates an url for remote use
	 * @param string $service id
	 * @return string the url
	 *
	 * Returns a url to the given service.
	 */
	public static function linkToRemoteBase( $service ) {
		return self::linkTo( '', 'remote.php') . '/' . $service;
	}

	/**
	 * @brief Creates an absolute url for remote use
	 * @param string $service id
	 * @param bool $add_slash
	 * @return string the url
	 *
	 * Returns a absolute url to the given service.
	 */
	public static function linkToRemote( $service, $add_slash = true ) {
		return self::makeURLAbsolute(self::linkToRemoteBase($service))
			. (($add_slash && $service[strlen($service)-1]!='/')?'/':'');
	}

	/**
	 * @brief Creates an absolute url for public use
	 * @param string $service id
	 * @param bool $add_slash
	 * @return string the url
	 *
	 * Returns a absolute url to the given service.
	 */
	public static function linkToPublic($service, $add_slash = false) {
		return self::linkToAbsolute( '', 'public.php') . '?service=' . $service
			. (($add_slash && $service[strlen($service)-1]!='/')?'/':'');
	}

	/**
	 * @brief Creates path to an image
	 * @param string $app app
	 * @param string $image image name
	 * @return string the url
	 *
	 * Returns the path to the image.
	 */
	public static function imagePath( $app, $image ) {
		// Read the selected theme from the config file
		$theme=OC_Config::getValue( "theme" );

		// Check if the app is in the app folder
		if( file_exists( OC::$SERVERROOT."/themes/$theme/apps/$app/img/$image" )) {
			return OC::$WEBROOT."/themes/$theme/apps/$app/img/$image";
		}elseif( file_exists(OC_App::getAppPath($app)."/img/$image" )) {
			return OC_App::getAppWebPath($app)."/img/$image";
		}elseif( !empty( $app ) and file_exists( OC::$SERVERROOT."/themes/$theme/$app/img/$image" )) {
			return OC::$WEBROOT."/themes/$theme/$app/img/$image";
		}elseif( !empty( $app ) and file_exists( OC::$SERVERROOT."/$app/img/$image" )) {
			return OC::$WEBROOT."/$app/img/$image";
		}elseif( file_exists( OC::$SERVERROOT."/themes/$theme/core/img/$image" )) {
			return OC::$WEBROOT."/themes/$theme/core/img/$image";
		}elseif( file_exists( OC::$SERVERROOT."/core/img/$image" )) {
			return OC::$WEBROOT."/core/img/$image";
		}else{
			echo('image not found: image:'.$image.' webroot:'.OC::$WEBROOT.' serverroot:'.OC::$SERVERROOT);
			die();
		}
	}

	/**
	 * @brief get path to icon of file type
	 * @param string $mimetype mimetype
	 * @return string the url
	 *
	 * Returns the path to the image of this file type.
	 */
	public static function mimetypeIcon( $mimetype ) {
		$alias=array('application/xml'=>'code/xml');
		if(isset($alias[$mimetype])) {
			$mimetype=$alias[$mimetype];
		}
		// Replace slash and backslash with a minus
		$mimetype = str_replace( "/", "-", $mimetype );
		$mimetype = str_replace( "\\", "-", $mimetype );

		// Is it a dir?
		if( $mimetype == "dir" ) {
			return OC::$WEBROOT."/core/img/filetypes/folder.png";
		}

		// Icon exists?
		if( file_exists( OC::$SERVERROOT."/core/img/filetypes/$mimetype.png" )) {
			return OC::$WEBROOT."/core/img/filetypes/$mimetype.png";
		}
		//try only the first part of the filetype
		$mimetype=substr($mimetype, 0, strpos($mimetype, '-'));
		if( file_exists( OC::$SERVERROOT."/core/img/filetypes/$mimetype.png" )) {
			return OC::$WEBROOT."/core/img/filetypes/$mimetype.png";
		}
		else{
			return OC::$WEBROOT."/core/img/filetypes/file.png";
		}
	}

	/**
	 * @brief Make a human file size
	 * @param int $bytes file size in bytes
	 * @return string a human readable file size
	 *
	 * Makes 2048 to 2 kB.
	 */
	public static function humanFileSize( $bytes ) {
		if( $bytes < 0 ) {
			$l = OC_L10N::get('lib');
			return $l->t("couldn't be determined");
		}
		if( $bytes < 1024 ) {
			return "$bytes B";
		}
		$bytes = round( $bytes / 1024, 1 );
		if( $bytes < 1024 ) {
			return "$bytes kB";
		}
		$bytes = round( $bytes / 1024, 1 );
		if( $bytes < 1024 ) {
			return "$bytes MB";
		}

		// Wow, heavy duty for owncloud
		$bytes = round( $bytes / 1024, 1 );
		return "$bytes GB";
	}

	/**
	 * @brief Make a computer file size
	 * @param string $str file size in a fancy format
	 * @return int a file size in bytes
	 *
	 * Makes 2kB to 2048.
	 *
	 * Inspired by: http://www.php.net/manual/en/function.filesize.php#92418
	 */
	public static function computerFileSize( $str ) {
		$str=strtolower($str);

		$bytes_array = array(
			'b' => 1,
			'k' => 1024,
			'kb' => 1024,
			'mb' => 1024 * 1024,
			'm'  => 1024 * 1024,
			'gb' => 1024 * 1024 * 1024,
			'g'  => 1024 * 1024 * 1024,
			'tb' => 1024 * 1024 * 1024 * 1024,
			't'  => 1024 * 1024 * 1024 * 1024,
			'pb' => 1024 * 1024 * 1024 * 1024 * 1024,
			'p'  => 1024 * 1024 * 1024 * 1024 * 1024,
		);

		$bytes = floatval($str);

		if (preg_match('#([kmgtp]?b?)$#si', $str, $matches) && !empty($bytes_array[$matches[1]])) {
			$bytes *= $bytes_array[$matches[1]];
		}

		$bytes = round($bytes, 2);

		return $bytes;
	}

	/**
	 * @brief Recursive editing of file permissions
	 * @param string $path path to file or folder
	 * @param int $filemode unix style file permissions
	 * @return bool
	 */
	static function chmodr($path, $filemode) {
		if (!is_dir($path))
			return chmod($path, $filemode);
		$dh = opendir($path);
		while (($file = readdir($dh)) !== false) {
			if($file != '.' && $file != '..') {
				$fullpath = $path.'/'.$file;
				if(is_link($fullpath))
					return false;
				elseif(!is_dir($fullpath) && !@chmod($fullpath, $filemode))
						return false;
				elseif(!self::chmodr($fullpath, $filemode))
					return false;
			}
		}
		closedir($dh);
		if(@chmod($path, $filemode))
			return true;
		else
			return false;
	}

	/**
	 * @brief Recursive copying of folders
	 * @param string $src source folder
	 * @param string $dest target folder
	 *
	 */
	static function copyr($src, $dest) {
		if(is_dir($src)) {
			if(!is_dir($dest)) {
				mkdir($dest);
			}
			$files = scandir($src);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					self::copyr("$src/$file", "$dest/$file");
				}
			}
		}elseif(file_exists($src) && !\OC\Files\Filesystem::isFileBlacklisted($src)) {
			copy($src, $dest);
		}
	}

	/**
	 * @brief Recursive deletion of folders
	 * @param string $dir path to the folder
	 * @return bool
	 */
	static function rmdirr($dir) {
		if(is_dir($dir)) {
			$files=scandir($dir);
			foreach($files as $file) {
				if ($file != "." && $file != "..") {
					self::rmdirr("$dir/$file");
				}
			}
			rmdir($dir);
		}elseif(file_exists($dir)) {
			unlink($dir);
		}
		if(file_exists($dir)) {
			return false;
		}else{
			return true;
		}
	}

	/**
	 * get the mimetype form a local file
	 * @param string $path
	 * @return string
	 * does NOT work for ownClouds filesystem, use OC_FileSystem::getMimeType instead
	 */
	static function getMimeType($path) {
		$isWrapped=(strpos($path, '://')!==false) and (substr($path, 0, 7)=='file://');

		if (@is_dir($path)) {
			// directories are easy
			return "httpd/unix-directory";
		}

		if(strpos($path, '.')) {
			//try to guess the type by the file extension
			if(!self::$mimetypes || self::$mimetypes != include 'mimetypes.list.php') {
				self::$mimetypes=include 'mimetypes.list.php';
			}
			$extension=strtolower(strrchr(basename($path), "."));
			$extension=substr($extension, 1);//remove leading .
			$mimeType=(isset(self::$mimetypes[$extension]))?self::$mimetypes[$extension]:'application/octet-stream';
		}else{
			$mimeType='application/octet-stream';
		}

		if($mimeType=='application/octet-stream' and function_exists('finfo_open')
			and function_exists('finfo_file') and $finfo=finfo_open(FILEINFO_MIME)) {
			$info = @strtolower(finfo_file($finfo, $path));
			if($info) {
				$mimeType=substr($info, 0, strpos($info, ';'));
			}
			finfo_close($finfo);
		}
		if (!$isWrapped and $mimeType=='application/octet-stream' && function_exists("mime_content_type")) {
			// use mime magic extension if available
			$mimeType = mime_content_type($path);
		}
		if (!$isWrapped and $mimeType=='application/octet-stream' && OC_Helper::canExecute("file")) {
			// it looks like we have a 'file' command,
			// lets see if it does have mime support
			$path=escapeshellarg($path);
			$fp = popen("file -b --mime-type $path 2>/dev/null", "r");
			$reply = fgets($fp);
			pclose($fp);

			//trim the newline
			$mimeType = trim($reply);

		}
		return $mimeType;
	}

	/**
	 * get the mimetype form a data string
	 * @param string $data
	 * @return string
	 */
	static function getStringMimeType($data) {
		if(function_exists('finfo_open') and function_exists('finfo_file')) {
			$finfo=finfo_open(FILEINFO_MIME);
			return finfo_buffer($finfo, $data);
		}else{
			$tmpFile=OC_Helper::tmpFile();
			$fh=fopen($tmpFile, 'wb');
			fwrite($fh, $data, 8024);
			fclose($fh);
			$mime=self::getMimeType($tmpFile);
			unset($tmpFile);
			return $mime;
		}
	}

	/**
	 * @brief Checks $_REQUEST contains a var for the $s key. If so, returns the html-escaped value of this var; otherwise returns the default value provided by $d.
	 * @param string $s name of the var to escape, if set.
	 * @param string $d default value.
	 * @return string the print-safe value.
	 *
	 */

	//FIXME: should also check for value validation (i.e. the email is an email).
	public static function init_var($s, $d="") {
		$r = $d;
		if(isset($_REQUEST[$s]) && !empty($_REQUEST[$s])) {
			$r = OC_Util::sanitizeHTML($_REQUEST[$s]);
		}

		return $r;
	}

	/**
	 * returns "checked"-attribute if request contains selected radio element
	 * OR if radio element is the default one -- maybe?
	 * @param string $s Name of radio-button element name
	 * @param string $v Value of current radio-button element
	 * @param string $d Value of default radio-button element
	 */
	public static function init_radio($s, $v, $d) {
		if((isset($_REQUEST[$s]) && $_REQUEST[$s]==$v) || (!isset($_REQUEST[$s]) && $v == $d))
			print "checked=\"checked\" ";
	}

	/**
	 * detect if a given program is found in the search PATH
	 *
	 * @param $name
	 * @param bool $path
	 * @internal param string $program name
	 * @internal param string $optional search path, defaults to $PATH
	 * @return bool    true if executable program found in path
	 */
	public static function canExecute($name, $path = false) {
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
		// Default check will be done with $path directories :
		$dirs = explode(PATH_SEPARATOR, $path);
		// WARNING : We have to check if open_basedir is enabled :
		$obd = ini_get('open_basedir');
		if($obd != "none") {
			$obd_values = explode(PATH_SEPARATOR, $obd);
			if(count($obd_values) > 0 and $obd_values[0]) {
				// open_basedir is in effect !
				// We need to check if the program is in one of these dirs :
				$dirs = $obd_values;
			}
		}
		foreach($dirs as $dir) {
			foreach($exts as $ext) {
				if($check_fn("$dir/$name".$ext))
					return true;
			}
		}
		return false;
	}

	/**
	 * copy the contents of one stream to another
	 * @param resource $source
	 * @param resource $target
	 * @return int the number of bytes copied
	 */
	public static function streamCopy($source, $target) {
		if(!$source or !$target) {
			return false;
		}
		$result = true;
		$count = 0;
		while(!feof($source)) {
			if ( ( $c = fwrite($target, fread($source, 8192)) ) === false) {
				$result = false;
			} else {
				$count += $c;
			}
		}
		return array($count, $result);
	}

	/**
	 * create a temporary file with an unique filename
	 * @param string $postfix
	 * @return string
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFile($postfix='') {
		$file=get_temp_dir().'/'.md5(time().rand()).$postfix;
		$fh=fopen($file, 'w');
		fclose($fh);
		self::$tmpFiles[]=$file;
		return $file;
	}

	/**
	 * create a temporary file with an unique filename. It will not be deleted
	 * automatically
	 * @param string $postfix
	 * @return string
	 *
	 */
	public static function tmpFileNoClean($postfix='') {
		$tmpDirNoClean=get_temp_dir().'/oc-noclean/';
		if (!file_exists($tmpDirNoClean) || !is_dir($tmpDirNoClean)) {
			if (file_exists($tmpDirNoClean)) {
				unlink($tmpDirNoClean);
			}
			mkdir($tmpDirNoClean);
		}
		$file=$tmpDirNoClean.md5(time().rand()).$postfix;
		$fh=fopen($file, 'w');
		fclose($fh);
		return $file;
	}

	/**
	 * create a temporary folder with an unique filename
	 * @return string
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFolder() {
		$path=get_temp_dir().'/'.md5(time().rand());
		mkdir($path);
		self::$tmpFiles[]=$path;
		return $path.'/';
	}

	/**
	 * remove all files created by self::tmpFile
	 */
	public static function cleanTmp() {
		$leftoversFile=get_temp_dir().'/oc-not-deleted';
		if(file_exists($leftoversFile)) {
			$leftovers=file($leftoversFile);
			foreach($leftovers as $file) {
				self::rmdirr($file);
			}
			unlink($leftoversFile);
		}

		foreach(self::$tmpFiles as $file) {
			if(file_exists($file)) {
				if(!self::rmdirr($file)) {
					file_put_contents($leftoversFile, $file."\n", FILE_APPEND);
				}
			}
		}
	}

	/**
	 * remove all files created by self::tmpFileNoClean
	 */
	public static function cleanTmpNoClean() {
		$tmpDirNoCleanFile=get_temp_dir().'/oc-noclean/';
		if(file_exists($tmpDirNoCleanFile)) {
			self::rmdirr($tmpDirNoCleanFile);
		}
	}

	/**
	* Adds a suffix to the name in case the file exists
	*
	* @param $path
	* @param $filename
	* @return string
	*/
	public static function buildNotExistingFileName($path, $filename) {
		if($path==='/') {
			$path='';
		}
		if ($pos = strrpos($filename, '.')) {
			$name = substr($filename, 0, $pos);
			$ext = substr($filename, $pos);
		} else {
			$name = $filename;
			$ext = '';
		}

		$newpath = $path . '/' . $filename;
		$counter = 2;
		while (\OC\Files\Filesystem::file_exists($newpath)) {
			$newname = $name . ' (' . $counter . ')' . $ext;
			$newpath = $path . '/' . $newname;
			$counter++;
		}

		return $newpath;
	}

	/**
	 * @brief Checks if $sub is a subdirectory of $parent
	 *
	 * @param string $sub
	 * @param string $parent
	 * @return bool
	 */
	public static function issubdirectory($sub, $parent) {
		if (strpos(realpath($sub), realpath($parent)) === 0) {
			return true;
		}
		return false;
	}

	/**
	* @brief Returns an array with all keys from input lowercased or uppercased. Numbered indices are left as is.
	*
	* @param array $input The array to work on
	* @param int $case Either MB_CASE_UPPER or MB_CASE_LOWER (default)
	* @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	* @return array
	*
	* Returns an array with all keys from input lowercased or uppercased. Numbered indices are left as is.
	* based on http://www.php.net/manual/en/function.array-change-key-case.php#107715
	*
	*/
	public static function mb_array_change_key_case($input, $case = MB_CASE_LOWER, $encoding = 'UTF-8') {
		$case = ($case != MB_CASE_UPPER) ? MB_CASE_LOWER : MB_CASE_UPPER;
		$ret = array();
		foreach ($input as $k => $v) {
			$ret[mb_convert_case($k, $case, $encoding)] = $v;
		}
		return $ret;
	}

	/**
	 * @brief replaces a copy of string delimited by the start and (optionally) length parameters with the string given in replacement.
	 *
	 * @param $string
	 * @param string $replacement The replacement string.
	 * @param int $start If start is positive, the replacing will begin at the start'th offset into string. If start is negative, the replacing will begin at the start'th character from the end of string.
	 * @param int $length Length of the part to be replaced
	 * @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	 * @internal param string $input The input string. .Opposite to the PHP build-in function does not accept an array.
	 * @return string
	 */
	public static function mb_substr_replace($string, $replacement, $start, $length = null, $encoding = 'UTF-8') {
		$start = intval($start);
		$length = intval($length);
		$string = mb_substr($string, 0, $start, $encoding) .
			$replacement .
			mb_substr($string, $start+$length, mb_strlen($string, 'UTF-8')-$start, $encoding);

		return $string;
	}

	/**
	* @brief Replace all occurrences of the search string with the replacement string
	*
	* @param string $search The value being searched for, otherwise known as the needle.
	* @param string $replace The replacement
	* @param string $subject The string or array being searched and replaced on, otherwise known as the haystack.
	* @param string $encoding The encoding parameter is the character encoding. Defaults to UTF-8
	* @param int $count If passed, this will be set to the number of replacements performed.
	* @return string
	*
	*/
	public static function mb_str_replace($search, $replace, $subject, $encoding = 'UTF-8', &$count = null) {
		$offset = -1;
		$length = mb_strlen($search, $encoding);
		while(($i = mb_strrpos($subject, $search, $offset, $encoding)) !== false ) {
			$subject = OC_Helper::mb_substr_replace($subject, $replace, $i, $length);
			$offset = $i - mb_strlen($subject, $encoding);
			$count++;
		}
		return $subject;
	}

	/**
	* @brief performs a search in a nested array
	* @param array $haystack the array to be searched
	* @param string $needle the search string
	* @param string $index optional, only search this key name
	* @return mixed the key of the matching field, otherwise false
	*
	* performs a search in a nested array
	*
	* taken from http://www.php.net/manual/en/function.array-search.php#97645
	*/
	public static function recursiveArraySearch($haystack, $needle, $index = null) {
		$aIt = new RecursiveArrayIterator($haystack);
		$it = new RecursiveIteratorIterator($aIt);

		while($it->valid()) {
			if (((isset($index) AND ($it->key() == $index)) OR (!isset($index))) AND ($it->current() == $needle)) {
				return $aIt->key();
			}

			$it->next();
		}

		return false;
	}

	/**
	 * Shortens str to maxlen by replacing characters in the middle with '...', eg.
	 * ellipsis('a very long string with lots of useless info to make a better example', 14) becomes 'a very ...example'
	 * @param string $str the string
	 * @param string $maxlen the maximum length of the result
	 * @return string with at most maxlen characters
	 */
	public static function ellipsis($str, $maxlen) {
		if (strlen($str) > $maxlen) {
			$characters = floor($maxlen / 2);
			return substr($str, 0, $characters) . '...' . substr($str, -1 * $characters);
		}
		return $str;
	}

	/**
	 * @brief calculates the maximum upload size respecting system settings, free space and user quota
	 *
	 * @param $dir the current folder where the user currently operates
	 * @return number of bytes representing
	 */
	public static function maxUploadFilesize($dir) {
		$upload_max_filesize = OCP\Util::computerFileSize(ini_get('upload_max_filesize'));
		$post_max_size = OCP\Util::computerFileSize(ini_get('post_max_size'));
		$maxUploadFilesize = min($upload_max_filesize, $post_max_size);

		$freeSpace = \OC\Files\Filesystem::free_space($dir);
		if($freeSpace !== \OC\Files\FREE_SPACE_UNKNOWN){
			$freeSpace = max($freeSpace, 0);

			return min($maxUploadFilesize, $freeSpace);
		} else {
			return $maxUploadFilesize;
		}
	}

	/**
	 * Checks if a function is available
	 * @param string $function_name
	 * @return bool
	 */
	public static function is_function_enabled($function_name) {
		if (!function_exists($function_name)) {
			return false;
		}
		$disabled = explode(', ', ini_get('disable_functions'));
		if (in_array($function_name, $disabled)) {
			return false;
		}
		$disabled = explode(', ', ini_get('suhosin.executor.func.blacklist'));
		if (in_array($function_name, $disabled)) {
			return false;
		}
		return true;
	}

	/**
	 * Calculate the disc space
	 */
	public static function getStorageInfo() {
		$rootInfo = \OC\Files\Filesystem::getFileInfo('/');
		$used = $rootInfo['size'];
		if ($used < 0) {
			$used = 0;
		}
		$free = \OC\Files\Filesystem::free_space();
		$total = $free + $used;
		if ($total == 0) {
			$total = 1; // prevent division by zero
		}
		$relative = round(($used / $total) * 10000) / 100;

		return array('free' => $free, 'used' => $used, 'total' => $total, 'relative' => $relative);
	}
}
