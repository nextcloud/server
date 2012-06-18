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
	private static $mimetypes=array();
	private static $tmpFiles=array();

	/**
	 * @brief Creates an url
	 * @param $app app
	 * @param $file file
	 * @returns the url
	 *
	 * Returns a url to the given app and file.
	 */
	public static function linkTo( $app, $file ){
		if( $app != '' ){
			$app .= '/';
			// Check if the app is in the app folder
			if( file_exists( OC::$APPSROOT . '/apps/'. $app.$file )){
				if(substr($file, -3) == 'php' || substr($file, -3) == 'css'){	
					if(substr($app, -1, 1) == '/'){
						$app = substr($app, 0, strlen($app) - 1);
					}
					$urlLinkTo =  OC::$WEBROOT . '/?app=' . $app;
					$urlLinkTo .= ($file!='index.php')?'&getfile=' . urlencode($file):'';
				}else{
					$urlLinkTo =  OC::$APPSWEBROOT . '/apps/' . $app . $file;
				}
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

		return $urlLinkTo;
	}

	/**
	 * @brief Returns the server host
	 * @returns the server host
	 *
	 * Returns the server host, even if the website uses one or more
	 * reverse proxies
	 */
	public static function serverHost() {
		if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			if (strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ",") !== false) {
				$host = trim(array_pop(explode(",", $_SERVER['HTTP_X_FORWARDED_HOST'])));
			}
			else{
				$host=$_SERVER['HTTP_X_FORWARDED_HOST'];
			}
		}
		else{
			$host = $_SERVER['HTTP_HOST'];
		}
		return $host;
	}

        /**
         * @brief Returns the server protocol
         * @returns the server protocol
         *
         * Returns the server protocol. It respects reverse proxy servers and load balancers
         */
	public static function serverProtocol() {
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			$proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
		}else{
			if(isset($_SERVER['HTTPS']) and !empty($_SERVER['HTTPS']) and ($_SERVER['HTTPS']!='off')) {
				$proto = 'https';
			}else{
				$proto = 'http';
			}
		}
		return($proto);
	}
	

	/**
	 * @brief Creates an absolute url
	 * @param $app app
	 * @param $file file
	 * @returns the url
	 *
	 * Returns a absolute url to the given app and file.
	 */
	public static function linkToAbsolute( $app, $file ) {
		$urlLinkTo = self::linkTo( $app, $file );
		$urlLinkTo = OC_Helper::serverProtocol(). '://'  . self::serverHost() . $urlLinkTo;
		return $urlLinkTo;
	}

	/**
	 * @brief Creates an absolute url for remote use
	 * @param $service id
	 * @returns the url
	 *
	 * Returns a absolute url to the given service.
	 */
	public static function linkToRemote( $service ) {
		return self::linkToAbsolute( '', 'remote.php') . '/' . $service . '/';
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
		// Read the selected theme from the config file
		$theme=OC_Config::getValue( "theme" );

		// Check if the app is in the app folder
		if( file_exists( OC::$SERVERROOT."/themes/$theme/apps/$app/img/$image" )){
			return OC::$WEBROOT."/themes/$theme/apps/$app/img/$image";
		}elseif( file_exists( OC::$APPSROOT."/apps/$app/img/$image" )){
			return OC::$APPSWEBROOT."/apps/$app/img/$image";
		}elseif( !empty( $app ) and file_exists( OC::$SERVERROOT."/themes/$theme/$app/img/$image" )){
			return OC::$WEBROOT."/themes/$theme/$app/img/$image";
		}elseif( !empty( $app ) and file_exists( OC::$SERVERROOT."/$app/img/$image" )){
			return OC::$WEBROOT."/$app/img/$image";
		}elseif( file_exists( OC::$SERVERROOT."/themes/$theme/core/img/$image" )){
			return OC::$WEBROOT."/themes/$theme/core/img/$image";
		}elseif( file_exists( OC::$SERVERROOT."/core/img/$image" )){
			return OC::$WEBROOT."/core/img/$image";
		}else{
			echo('image not found: image:'.$image.' webroot:'.OC::$WEBROOT.' serverroot:'.OC::$SERVERROOT);
			die();
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
				elseif(!is_dir($fullpath) && !@chmod($fullpath, $filemode))
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
		if(file_exists($dir)) {
			return false;
		}
	}

	/**
	 * get the mimetype form a local file
	 * @param string path
	 * @return string
	 * does NOT work for ownClouds filesystem, use OC_FileSystem::getMimeType instead
	 */
	static function getMimeType($path){
		$isWrapped=(strpos($path,'://')!==false) and (substr($path,0,7)=='file://');
		$mimeType='application/octet-stream';
		if ($mimeType=='application/octet-stream') {
			self::$mimetypes = include('mimetypes.fixlist.php');
			$extension=strtolower(strrchr(basename($path), "."));
			$extension=substr($extension,1);//remove leading .
			$mimeType=(isset(self::$mimetypes[$extension]))?self::$mimetypes[$extension]:'application/octet-stream';

		}
		if (@is_dir($path)) {
			// directories are easy
			return "httpd/unix-directory";
		}
		if($mimeType=='application/octet-stream' and function_exists('finfo_open') and function_exists('finfo_file') and $finfo=finfo_open(FILEINFO_MIME)){
			$info = @strtolower(finfo_file($finfo,$path));
			if($info){
				$mimeType=substr($info,0,strpos($info,';'));
			}
			finfo_close($finfo);
		}
		if (!$isWrapped and $mimeType=='application/octet-stream' && function_exists("mime_content_type")) {
			// use mime magic extension if available
			$mimeType = mime_content_type($path);
		}
		if (!$isWrapped and $mimeType=='application/octet-stream' && OC_Helper::canExecute("file")) {
			// it looks like we have a 'file' command,
			// lets see it it does have mime support
			$path=escapeshellarg($path);
			$fp = popen("file -i -b $path 2>/dev/null", "r");
			$reply = fgets($fp);
			pclose($fp);

			//trim the character set from the end of the response
			$mimeType=substr($reply,0,strrpos($reply,' '));

			//trim ; 
			if (strpos($mimeType, ';') !== false) {
				$mimeType = strstr($mimeType, ';', true);
			}

		}
		if ($mimeType=='application/octet-stream') {
			// Fallback solution: (try to guess the type by the file extension
			if(!self::$mimetypes || self::$mimetypes != include('mimetypes.list.php')){
				self::$mimetypes=include('mimetypes.list.php');
			}
			$extension=strtolower(strrchr(basename($path), "."));
			$extension=substr($extension,1);//remove leading .
			$mimeType=(isset(self::$mimetypes[$extension]))?self::$mimetypes[$extension]:'application/octet-stream';
		}
		return $mimeType;
	}

	/**
	 * get the mimetype form a data string
	 * @param string data
	 * @return string
	 */
	static function getStringMimeType($data){
		if(function_exists('finfo_open') and function_exists('finfo_file')){
			$finfo=finfo_open(FILEINFO_MIME);
			return finfo_buffer($finfo, $data);
		}else{
			$tmpFile=OC_Helper::tmpFile();
			$fh=fopen($tmpFile,'wb');
			fwrite($fh,$data,8024);
			fclose($fh);
			$mime=self::getMimeType($tmpFile);
			unset($tmpFile);
			return $mime;
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

	/**
	 * copy the contents of one stream to another
	 * @param resource source
	 * @param resource target
	 * @return int the number of bytes copied
	 */
	public static function streamCopy($source,$target){
		if(!$source or !$target){
			return false;
		}
		$count=0;
		while(!feof($source)){
			$count+=fwrite($target,fread($source,8192));
		}
		return $count;
	}

	/**
	 * create a temporary file with an unique filename
	 * @param string postfix
	 * @return string
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFile($postfix=''){
		$file=get_temp_dir().'/'.md5(time().rand()).$postfix;
		$fh=fopen($file,'w');
		fclose($fh);
		self::$tmpFiles[]=$file;
		return $file;
	}

	/**
	 * create a temporary folder with an unique filename
	 * @return string
	 *
	 * temporary files are automatically cleaned up after the script is finished
	 */
	public static function tmpFolder(){
		$path=get_temp_dir().'/'.md5(time().rand());
		mkdir($path);
		self::$tmpFiles[]=$path;
		return $path.'/';
	}

	/**
	 * remove all files created by self::tmpFile
	 */
	public static function cleanTmp(){
		$leftoversFile=get_temp_dir().'/oc-not-deleted';
		if(file_exists($leftoversFile)){
			$leftovers=file($leftoversFile);
			foreach($leftovers as $file) {
				self::rmdirr($file);
			}
			unlink($leftoversFile);
		}

		foreach(self::$tmpFiles as $file){
			if(file_exists($file)){
				if(!self::rmdirr($file)) {
					file_put_contents($leftoversFile, $file."\n", FILE_APPEND);
				}
			}
		}
	}

    /**
     * Adds a suffix to the name in case the file exists
     *
     * @param $path
     * @param $filename
     * @return string
     */
    public static function buildNotExistingFileName($path, $filename){
	    if($path==='/'){
		    $path='';
	    }
        if ($pos = strrpos($filename, '.')) {
            $name = substr($filename, 0, $pos);
            $ext = substr($filename, $pos);
        } else {
            $name = $filename;
        }

        $newpath = $path . '/' . $filename;
        $newname = $filename;
        $counter = 2;
        while (OC_Filesystem::file_exists($newpath)) {
            $newname = $name . ' (' . $counter . ')' . $ext;
            $newpath = $path . '/' . $newname;
            $counter++;
        }

        return $newpath;
    }
	
	/*
	 * checks if $sub is a subdirectory of $parent
	 * 
	 * @param $sub 
	 * @param $parent
	 * @return bool
	 */
	public static function issubdirectory($sub, $parent){
		if($sub == null || $sub == '' || $parent == null || $parent == ''){
			return false;
		}
		$realpath_sub = realpath($sub);
		$realpath_parent = realpath($parent);
		if(($realpath_sub == false && substr_count($realpath_sub, './') != 0) || ($realpath_parent == false && substr_count($realpath_parent, './') != 0)){ //it checks for  both ./ and ../
			return false;
		}
		if($realpath_sub && $realpath_sub != '' && $realpath_parent && $realpath_parent != ''){
			if(substr($realpath_sub, 0, strlen($realpath_parent)) == $realpath_parent){
				return true;
			}
		}else{
			if(substr($sub, 0, strlen($parent)) == $parent){
				return true;
			}
		}
		/*echo 'SUB: ' . $sub . "\n";
		echo 'PAR: ' . $parent . "\n";
		echo 'REALSUB: ' . $realpath_sub . "\n";
		echo 'REALPAR: ' . $realpath_parent . "\n";
		echo substr($realpath_sub, 0, strlen($realpath_parent));
		exit;*/
		return false;
	}
}
