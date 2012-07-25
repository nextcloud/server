<?php
/**
 * for local filestore, we only have to map the paths
 */
class OC_Filestorage_Local extends OC_Filestorage{
	protected $datadir;
	private static $mimetypes=null;
	public function __construct($arguments){
		$this->datadir=$arguments['datadir'];
		if(substr($this->datadir,-1)!=='/'){
			$this->datadir.='/';
		}
	}
	public function mkdir($path){
		return @mkdir($this->datadir.$path);
	}
	public function rmdir($path){
		return @rmdir($this->datadir.$path);
	}
	public function opendir($path){
		return opendir($this->datadir.$path);
	}
	public function is_dir($path){
		if(substr($path,-1)=='/'){
			$path=substr($path,0,-1);
		}
		return is_dir($this->datadir.$path);
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
	public function is_writable($path){
		return is_writable($this->datadir.$path);
	}
	public function file_exists($path){
		return file_exists($this->datadir.$path);
	}
	public function filectime($path){
		return filectime($this->datadir.$path);
	}
	public function filemtime($path){
		return filemtime($this->datadir.$path);
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
		return file_put_contents($this->datadir.$path,$data);
	}
	public function unlink($path){
		return $this->delTree($path);
	}
	public function rename($path1,$path2){
		if (!$this->is_writable($path1)) {
			OC_Log::write('core','unable to rename, file is not writable : '.$path1,OC_Log::ERROR);
			return false;
		}
		if(! $this->file_exists($path1)){
			OC_Log::write('core','unable to rename, file does not exists : '.$path1,OC_Log::ERROR);
			return false;
		}

		if($return=rename($this->datadir.$path1,$this->datadir.$path2)){
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
		return copy($this->datadir.$path1,$this->datadir.$path2);
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
					break;
				case 'w':
				case 'x':
				case 'a':
					break;
			}
		}
		return $return;
	}

	public function getMimeType($path){
		if($this->is_readable($path)){
			return OC_Helper::getMimeType($this->datadir.$path);
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
				}
			}elseif(is_dir($dir.'/'.$item)){
				if (!$this->delTree($dirRelative. "/" . $item)){
					return false;
				};
			}
		}
		if($return=rmdir($dir)){
		}
		return $return;
	}

	public function hash($type,$path,$raw = false){
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
		return 0;//depricated, use OC_FileCach instead
	}
}
