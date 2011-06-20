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
class OC_HELPER {
	/**
	 * @brief Creates an url
	 * @param $app app
	 * @param $file file
	 * @returns the url
	 *
	 * Returns a url to the given app and file.
	 */
	public static function linkTo( $app, $file ){
		global $WEBROOT;
		global $SERVERROOT;
		
		if( $app != '' ){
			$app .= '/';
			// Check if the app is in the app folder
			if( file_exists( $SERVERROOT . '/apps/'. $app.$file )){
				return $WEBROOT . '/apps/' . $app . $file;
			}
			else{
				return $WEBROOT . '/' . $app . $file;
			}
		}
		else{
			if( file_exists( $SERVERROOT . '/core/'. $file )){
				return $WEBROOT . '/core/'.$file;
			}
			else{
				return $WEBROOT . '/'.$file;
			}
		}
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
		global $SERVERROOT;
		global $WEBROOT;
		
		// Check if the app is in the app folder
		if( file_exists( "$SERVERROOT/apps/$app/img/$image" )){
			return "$WEBROOT/apps/$app/img/$image";
		}
		elseif( !empty( $app )){
			return "$WEBROOT/$app/img/$image";
		}
		else{
			return "$WEBROOT/core/img/$image";
		}
	}

	/**
	 * @brief get path to icon of mime type
	 * @param $mimetype mimetype
	 * @returns the url
	 *
	 * Returns the path to the image of this mime type.
	 */
	public static function mimetypeIcon( $mimetype ){
		global $SERVERROOT;
		global $WEBROOT;
		// Replace slash with a minus
		$mimetype = str_replace( "/", "-", $mimetype );

		// Is it a dir?
		if( $mimetype == "dir" ){
			return "$WEBROOT/core/img/places/folder.png";
		}

		// Icon exists?
		if( file_exists( "$SERVERROOT/core/img/mimetypes/$mimetype.png" )){
			return "$WEBROOT/core/img/mimetypes/$mimetype.png";
		}
		else{
			return "$WEBROOT/core/img/mimetypes/file.png";
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

		$bytes_array = array(
			'B' => 1,
			'K' => 1024,
			'KB' => 1024,
			'MB' => 1024 * 1024,
			'M'  => 1024 * 1024,
			'GB' => 1024 * 1024 * 1024,
			'G'  => 1024 * 1024 * 1024,
			'TB' => 1024 * 1024 * 1024 * 1024,
			'T'  => 1024 * 1024 * 1024 * 1024,
			'PB' => 1024 * 1024 * 1024 * 1024 * 1024,
			'P'  => 1024 * 1024 * 1024 * 1024 * 1024,
		);

		$bytes = floatval($str);

		if (preg_match('#([KMGTP]?B?)$#si', $str, $matches) && !empty($bytes_array[$matches[1]])) {
			$bytes *= $bytes_array[$matches[1]];
		}

		$bytes = intval(round($bytes, 2));

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
		if(chmod($path, $filemode))
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
	
	public static function init_radio($s, $v, $d) {
		if((isset($_REQUEST[$s]) && $_REQUEST[$s]==$v) || $v == $d)
			print "checked=\"checked\" ";
	}
}

?>
