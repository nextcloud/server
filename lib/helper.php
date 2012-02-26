<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
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
 * Collection of useful functions
 */
class OC_Helper {
	/**
	 * @brief Creates an url
	 * @param $app app
	 * @param $file file
	 * @param $redirect_url redirect_url variable is appended to the URL
	 * @returns the url
	 *
	 * Returns a url to the given app and file.
	 */
	public static function linkTo( $app, $file, $redirect_url=NULL, $absolute=false ){
		if( $app != '' ){
			$app .= '/';
			// Check if the app is in the app folder
			if( file_exists( OC::$SERVERROOT . '/apps/'. $app.$file )){
				$urlLinkTo =  OC::$WEBROOT . '/apps/' . $app . $file;
			}
			else{
				$urlLinkTo =  OC::$WEBROOT . '/' . $app . $file;
			}
		}
		else{
			if( file_exists( OC::$SERVERROOT . '/core/'. $file )){
				$urlLinkTo =  OC::$WEBROOT . '/core/'.$file;
			}
			else{
				$urlLinkTo =  OC::$WEBROOT . '/'.$file;
			}
		}

		if($absolute){
			// Checking if the request was made through HTTPS. The last in line is for IIS
			$protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS']!='off');
			$urlLinkTo = ($protocol?'https':'http') . '://'  . $_SERVER['HTTP_HOST'] . $urlLinkTo;
		}

		if($redirect_url)
			return $urlLinkTo.'?redirect_url='.urlencode($_SERVER["REQUEST_URI"]);
		else
			return $urlLinkTo;

	}

	/**
	 * @brief Creates path to an image
	 * @param $app app
	 * @param $image image name
	 * @returns the url
	 *
	 * Returns the path to the image.
	 */
	public static function imagePath( $app, $image ){
		// Check if the app is in the app folder
		if( file_exists( OC::$SERVERROOT."/apps/$app/img/$image" )){
			return OC::$WEBROOT."/apps/$app/img/$image";
		}
		elseif( !empty( $app )){
			return OC::$WEBROOT."/$app/img/$image";
		}
		else{
			return OC::$WEBROOT."/core/img/$image";
		}
	}

	/**
	 * @brief get path to icon of file type
	 * @param $mimetype mimetype
	 * @returns the url
	 *
	 * Returns the path to the image of this file type.
	 */
	public static function mimetypeIcon( $mimetype ){
		$alias=array('application/xml'=>'code/xml');
// 		echo $mimetype;
		if(isset($alias[$mimetype])){
			$mimetype=$alias[$mimetype];
// 			echo $mimetype;
		}
		// Replace slash with a minus
		$mimetype = str_replace( "/", "-", $mimetype );

		// Is it a dir?
		if( $mimetype == "dir" ){
			return OC::$WEBROOT."/core/img/filetypes/folder.png";
		}

		// Icon exists?
		if( file_exists( OC::$SERVERROOT."/core/img/filetypes/$mimetype.png" )){
			return OC::$WEBROOT."/core/img/filetypes/$mimetype.png";
		}
		//try only the first part of the filetype
		$mimetype=substr($mimetype,0,strpos($mimetype,'-'));
		if( file_exists( OC::$SERVERROOT."/core/img/filetypes/$mimetype.png" )){
			return OC::$WEBROOT."/core/img/filetypes/$mimetype.png";
		}
		else{
			return OC::$WEBROOT."/core/img/filetypes/file.png";
		}
	}

	/**
	 * @brief Make a human file size
	 * @param $bytes file size in bytes
	 * @returns a human readable file size
	 *
	 * Makes 2048 to 2 kB.
	 */
	public static function humanFileSize( $bytes ){
		if( $bytes < 1024 ){
			return "$bytes B";
		}
		$bytes = round( $bytes / 1024, 1 );
		if( $bytes < 1024 ){
			return "$bytes kB";
		}
		$bytes = round( $bytes / 1024, 1 );
		if( $bytes < 1024 ){
			return "$bytes MB";
		}

		// Wow, heavy duty for owncloud
		$bytes = round( $bytes / 1024, 1 );
		return "$bytes GB";
	}
	
	/**
	 * @brief Make a computer file size
	 * @param $str file size in a fancy format
	 * @returns a file size in bytes
	 *
	 * Makes 2kB to 2048.
	 *
	 * Inspired by: http://www.php.net/manual/en/function.filesize.php#92418
	 */
	public static function computerFileSize( $str ){
		$bytes = 0;
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
	 * @brief Recusive editing of file permissions
	 * @param $path path to file or folder
	 * @param $filemode unix style file permissions as integer
	 *
	 */
	static function chmodr($path, $filemode) {
		if (!is_dir($path))
			return chmod($path, $filemode);
		$dh = opendir($path);
		while (($file = readdir($dh)) !== false) {
			if($file != '.' && $file != '..') {
				$fullpath = $path.'/'.$file;
				if(is_link($fullpath))
					return FALSE;
				elseif(!is_dir($fullpath) && !chmod($fullpath, $filemode))
						return FALSE;
				elseif(!self::chmodr($fullpath, $filemode))
					return FALSE;
			}
		}
		closedir($dh);
		if(@chmod($path, $filemode))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * @brief Recusive copying of folders
	 * @param string $src source folder
	 * @param string $dest target folder
	 *
	 */
	static function copyr($src, $dest) {
		if(is_dir($src)){
			if(!is_dir($dest)){
				mkdir($dest);
			}
			$files = scandir($src);
			foreach ($files as $file){
				if ($file != "." && $file != ".."){
					self::copyr("$src/$file", "$dest/$file");
				}
			}
		}elseif(file_exists($src)){
			copy($src, $dest);
		}
	}
	
	/**
	 * @brief Recusive deletion of folders
	 * @param string $dir path to the folder
	 *
	 */
	static function rmdirr($dir) {
		if(is_dir($dir)) {
			$files=scandir($dir);
			foreach($files as $file){
				if ($file != "." && $file != ".."){
					self::rmdirr("$dir/$file");
				}
			}
			rmdir($dir);
		}elseif(file_exists($dir)){
			unlink($dir);
		}
	}
	
	/**
	 * @brief Checks $_REQUEST contains a var for the $s key. If so, returns the html-escaped value of this var; otherwise returns the default value provided by $d.
	 * @param $s name of the var to escape, if set.
	 * @param $d default value.
	 * @returns the print-safe value.
	 *
	 */
	 
	//FIXME: should also check for value validation (i.e. the email is an email).
	public static function init_var($s, $d="") {
		$r = $d;
		if(isset($_REQUEST[$s]) && !empty($_REQUEST[$s]))
			$r = stripslashes(htmlspecialchars($_REQUEST[$s]));
		
		return $r;
	}
	
	/**
	 * returns "checked"-attribut if request contains selected radio element OR if radio element is the default one -- maybe?
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
	* @param  string  program name
	* @param  string  optional search path, defaults to $PATH
	* @return bool    true if executable program found in path
	*/
	public static function canExecute($name, $path = false){
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
		if($obd != "none")
			$obd_values = explode(PATH_SEPARATOR, $obd);
		if(count($obd_values) > 0 and $obd_values[0])
		{
			// open_basedir is in effect !
			// We need to check if the program is in one of these dirs :
			$dirs = $obd_values;
		}
		foreach($dirs as $dir)
		{
			foreach($exts as $ext)
			{
				if($check_fn("$dir/$name".$ext))
					return true;
			}
		}
		return false;
	}
}
