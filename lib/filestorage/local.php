<?php
/**
 * for local filestore, we only have to map the paths
 */
class OC_Filestorage_Local extends OC_Filestorage{
	private $datadir;
	private static $mimetypes=null;
	public function __construct($arguments){
		$this->datadir=$arguments['datadir'];
		if(substr($this->datadir,-1)!=='/'){
			$this->datadir.='/';
		}
	}
	public function mkdir($path){
		if($return=mkdir($this->datadir.$path)){
			$this->clearFolderSizeCache($path);
		}
		return $return;
	}
	public function rmdir($path){
		if($return=rmdir($this->datadir.$path)){
			$this->clearFolderSizeCache($path);
		}
		return $return;
	}
	public function opendir($path){
		return opendir($this->datadir.$path);
	}
	public function is_dir($path){
		return (is_dir($this->datadir.$path) or substr($path,-1)=='/');
	}
	public function is_file($path){
		return is_file($this->datadir.$path);
	}
	public function stat($path){
		return stat($this->datadir.$path);
	}
	public function filetype($path){
		$filetype=filetype($this->datadir.$path);
		if($filetype=='link'){
			$filetype=filetype(realpath($this->datadir.$path));
		}
		return $filetype;
	}
	public function filesize($path){
		if($this->is_dir($path)){
			return $this->getFolderSize($path);
		}else{
			return filesize($this->datadir.$path);
		}
	}
	public function is_readable($path){
		return is_readable($this->datadir.$path);
	}
	public function is_writeable($path){
		return is_writable($this->datadir.$path);
	}
	public function file_exists($path){
		return file_exists($this->datadir.$path);
	}
	public function readfile($path){
		return readfile($this->datadir.$path);
	}
	public function filectime($path){
		return filectime($this->datadir.$path);
	}
	public function filemtime($path){
		return filemtime($this->datadir.$path);
	}
	public function fileatime($path){
		return fileatime($this->datadir.$path);
	}
	public function touch($path, $mtime=null){
		// sets the modification time of the file to the given value. 
		// If mtime is nil the current time is set.
		// note that the access time of the file always changes to the current time.
		if(!is_null($mtime)){
			$result=touch( $this->datadir.$path, $mtime );
		}else{
			$result=touch( $this->datadir.$path);
		}
		if( $result ) {
			clearstatcache( true, $this->datadir.$path );
		}
		
		return $result;
	}
	public function file_get_contents($path){
		return file_get_contents($this->datadir.$path);
	}
	public function file_put_contents($path,$data){
		if($return=file_put_contents($this->datadir.$path,$data)){
			$this->clearFolderSizeCache($path);
		}
	}
	public function unlink($path){
		$return=$this->delTree($path);
		$this->clearFolderSizeCache($path);
		return $return;
	}
	public function rename($path1,$path2){
		if(! $this->file_exists($path1)){
			OC_Log::write('core','unable to rename, file does not exists : '.$path1,OC_Log::ERROR);
			return false;
		}

		if($return=rename($this->datadir.$path1,$this->datadir.$path2)){
			$this->clearFolderSizeCache($path1);
			$this->clearFolderSizeCache($path2);
		}
		return $return;
	}
	public function copy($path1,$path2){
		if($this->is_dir($path2)){
			if(!$this->file_exists($path2)){
				$this->mkdir($path2);
			}
			$source=substr($path1,strrpos($path1,'/')+1);
			$path2.=$source;
		}
		if($return=copy($this->datadir.$path1,$this->datadir.$path2)){
			$this->clearFolderSizeCache($path2);
		}
		return $return;
	}
	public function fopen($path,$mode){
		if($return=fopen($this->datadir.$path,$mode)){
			switch($mode){
				case 'r':
					break;
				case 'r+':
				case 'w+':
				case 'x+':
				case 'a+':
					$this->clearFolderSizeCache($path);
					break;
				case 'w':
				case 'x':
				case 'a':
					$this->clearFolderSizeCache($path);
					break;
			}
		}
		return $return;
	}

	public function getMimeType($fspath){
		if($this->is_readable($fspath)){
			$mimeType='application/octet-stream';
			if ($mimeType=='application/octet-stream') {
				self::$mimetypes = include('mimetypes.fixlist.php');
				$extention=strtolower(strrchr(basename($fspath), "."));
				$extention=substr($extention,1);//remove leading .
				$mimeType=(isset(self::$mimetypes[$extention]))?self::$mimetypes[$extention]:'application/octet-stream';
				
			}
			if (@is_dir($this->datadir.$fspath)) {
				// directories are easy
				return "httpd/unix-directory";
			}
			if($mimeType=='application/octet-stream' and function_exists('finfo_open') and function_exists('finfo_file') and $finfo=finfo_open(FILEINFO_MIME)){
				$mimeType =strtolower(finfo_file($finfo,$this->datadir.$fspath));
				$mimeType=substr($mimeType,0,strpos($mimeType,';'));
				finfo_close($finfo);
			}
			if ($mimeType=='application/octet-stream' && function_exists("mime_content_type")) {
				// use mime magic extension if available
				$mimeType = mime_content_type($this->datadir.$fspath);
			}
			if ($mimeType=='application/octet-stream' && OC_Helper::canExecute("file")) {
				// it looks like we have a 'file' command,
				// lets see it it does have mime support
				$fspath=str_replace("'","\'",$fspath);
				$fp = popen("file -i -b '{$this->datadir}$fspath' 2>/dev/null", "r");
				$reply = fgets($fp);
				pclose($fp);

				//trim the character set from the end of the response
				$mimeType=substr($reply,0,strrpos($reply,' '));
			}
			if ($mimeType=='application/octet-stream') {
				// Fallback solution: (try to guess the type by the file extension
				if(!self::$mimetypes || self::$mimetypes != include('mimetypes.list.php')){
					self::$mimetypes=include('mimetypes.list.php');
				}
				$extention=strtolower(strrchr(basename($fspath), "."));
				$extention=substr($extention,1);//remove leading .
				$mimeType=(isset(self::$mimetypes[$extention]))?self::$mimetypes[$extention]:'application/octet-stream';
			}
			return $mimeType;
		}
	}

	public function toTmpFile($path){
		$tmpFolder=get_temp_dir();
		$filename=tempnam($tmpFolder,'OC_TEMP_FILE_'.substr($path,strrpos($path,'.')));
		$fileStats = stat($this->datadir.$path);
		if(copy($this->datadir.$path,$filename)){
			touch($filename, $fileStats['mtime'], $fileStats['atime']);
			return $filename;
		}else{
			return false;
		}
	}

	public function fromTmpFile($tmpFile,$path){
		$fileStats = stat($tmpFile);
		if(rename($tmpFile,$this->datadir.$path)){
			touch($this->datadir.$path, $fileStats['mtime'], $fileStats['atime']);
			$this->clearFolderSizeCache($path);
			return true;
		}else{
			return false;
		}
	}

	public function fromUploadedFile($tmpFile,$path){
		$fileStats = stat($tmpFile);
		if(move_uploaded_file($tmpFile,$this->datadir.$path)){
			touch($this->datadir.$path, $fileStats['mtime'], $fileStats['atime']);
			$this->clearFolderSizeCache($path);
			return true;
		}else{
			return false;
		}
	}

	private function delTree($dir) {
		$dirRelative=$dir;
		$dir=$this->datadir.$dir;
		if (!file_exists($dir)) return true;
		if (!is_dir($dir) || is_link($dir)) return unlink($dir);
		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') continue;
			if(is_file($dir.'/'.$item)){
				if(unlink($dir.'/'.$item)){
					$this->clearFolderSizeCache($dir);
				}
			}elseif(is_dir($dir.'/'.$item)){
				if (!$this->delTree($dirRelative. "/" . $item)){
					return false;
				};
			}
		}
		if($return=rmdir($dir)){
			$this->clearFolderSizeCache($dir);
		}
		return $return;
	}

	public function hash($type,$path,$raw){
		return hash_file($type,$this->datadir.$path,$raw);
	}

	public function free_space($path){
		return disk_free_space($this->datadir.$path);
	}

	public function search($query){
		return $this->searchInDir($query);
	}
	public function getLocalFile($path){
			return $this->datadir.$path;
	}

	private function searchInDir($query,$dir=''){
		$files=array();
		foreach (scandir($this->datadir.$dir) as $item) {
			if ($item == '.' || $item == '..') continue;
			if(strstr(strtolower($item),strtolower($query))!==false){
				$files[]=$dir.'/'.$item;
			}
			if(is_dir($this->datadir.$dir.'/'.$item)){
				$files=array_merge($files,$this->searchInDir($query,$dir.'/'.$item));
			}
		}
		return $files;
	}

	/**
	 * @brief get the size of folder and it's content
	 * @param string $path file path
	 * @return int size of folder and it's content
	 */
	public function getFolderSize($path){
		$path=str_replace('//','/',$path);
		if($this->is_dir($path) and substr($path,-1)!='/'){
			$path.='/';
		}
		$query=OC_DB::prepare("SELECT size FROM *PREFIX*foldersize WHERE path=?");
		$size=$query->execute(array($path))->fetchAll();
		if(count($size)>0){// we already the size, just return it
			return $size[0]['size'];
		}else{//the size of the folder isn't know, calulate it
			return $this->calculateFolderSize($path);
		}
	}

	/**
	 * @brief calulate the size of folder and it's content and cache it
	 * @param string $path file path
	 * @return int size of folder and it's content
	 */
	public function calculateFolderSize($path){
		if($this->is_file($path)){
			$path=dirname($path);
		}
		$path=str_replace('//','/',$path);
		if($this->is_dir($path) and substr($path,-1)!='/'){
			$path.='/';
		}
		$size=0;
		if ($dh = $this->opendir($path)) {
			while (($filename = readdir($dh)) !== false) {
				if($filename!='.' and $filename!='..'){
					$subFile=$path.'/'.$filename;
					if($this->is_file($subFile)){
						$size+=$this->filesize($subFile);
					}else{
						$size+=$this->getFolderSize($subFile);
					}
				}
			}
			if($size>0){
				$query=OC_DB::prepare("INSERT INTO *PREFIX*foldersize VALUES(?,?)");
				$result=$query->execute(array($path,$size));
			}
		}
		return $size;
	}

	/**
	 * @brief clear the folder size cache of folders containing a file
	 * @param string $path
	 */
	public function clearFolderSizeCache($path){
		if($this->is_file($path)){
			$path=dirname($path);
		}
		$path=str_replace('//','/',$path);
		if($this->is_dir($path) and substr($path,-1)!='/'){
			$path.='/';
		}
		$query=OC_DB::prepare("DELETE FROM *PREFIX*foldersize WHERE path = ?");
		$result=$query->execute(array($path));
		if($path!='/' and $path!=''){
			$parts=explode('/',$path);
			//pop empty part
			$part=array_pop($parts);
			if(empty($part)){
				array_pop($parts);
			}
			$parent=implode('/',$parts);
			$this->clearFolderSizeCache($parent);
		}
	}
}
