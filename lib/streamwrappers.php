<?php
global $FAKEDIRS;
$FAKEDIRS=array();

class OC_FakeDirStream{
	public static $dirs=array();
	private $name;
	private $index;

	public function dir_opendir($path,$options){
		global $FAKEDIRS;
		$url=parse_url($path);
		$this->name=substr($path,strlen('fakedir://'));
		$this->index=0;
		if(!isset(self::$dirs[$this->name])){
			self::$dirs[$this->name]=array();
		}
		return true;
	}

	public function dir_readdir(){
		if($this->index>=count(self::$dirs[$this->name])){
			return false;
		}
		$filename=self::$dirs[$this->name][$this->index];
		$this->index++;
		return $filename;
	}

	public function dir_closedir() {
		$this->name='';
		return true;
	}

	public function dir_rewinddir() {
		$this->index=0;
		return true;
	}
}

class OC_StaticStreamWrapper {
	public $context;
	protected static $data = array();

	protected $path    = '';
	protected $pointer = 0;
	protected $writable = false;

	public function stream_close() {}

	public function stream_eof() {
		return $this->pointer >= strlen(self::$data[$this->path]);
	}

	public function stream_flush() {}

	public function stream_open($path, $mode, $options, &$opened_path) {
		switch ($mode[0]) {
			case 'r':
				if (!isset(self::$data[$path])) return false;
				$this->path = $path;
				$this->writable = isset($mode[1]) && $mode[1] == '+';
				break;
			case 'w':
				self::$data[$path] = '';
				$this->path = $path;
				$this->writable = true;
				break;
			case 'a':
				if (!isset(self::$data[$path])) self::$data[$path] = '';
				$this->path = $path;
				$this->writable = true;
				$this->pointer = strlen(self::$data[$path]);
				break;
			case 'x':
				if (isset(self::$data[$path])) return false;
				$this->path = $path;
				$this->writable = true;
				break;
			case 'c':
				if (!isset(self::$data[$path])) self::$data[$path] = '';
				$this->path = $path;
				$this->writable = true;
				break;
			default:
				return false;
		}
		$opened_path = $this->path;
		return true;
	}

	public function stream_read($count) {
		$bytes = min(strlen(self::$data[$this->path]) - $this->pointer, $count);
		$data = substr(self::$data[$this->path], $this->pointer, $bytes);
		$this->pointer += $bytes;
		return $data;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		$len = strlen(self::$data[$this->path]);
		switch ($whence) {
			case SEEK_SET:
				if ($offset <= $len) {
					$this->pointer = $offset;
					return true;
				}
				break;
			case SEEK_CUR:
				if ($this->pointer + $offset <= $len) {
					$this->pointer += $offset;
					return true;
				}
				break;
			case SEEK_END:
				if ($len + $offset <= $len) {
					$this->pointer = $len + $offset;
					return true;
				}
				break;
		}
		return false;
	}

	public function stream_stat() {
		$size = strlen(self::$data[$this->path]);
		$time = time();
		return array(
			0 => 0,
			'dev' => 0,
			1 => 0,
			'ino' => 0,
			2 => 0777,
			'mode' => 0777,
			3 => 1,
			'nlink' => 1,
			4 => 0,
			'uid' => 0,
			5 => 0,
			'gid' => 0,
			6 => '',
			'rdev' => '',
			7 => $size,
			'size' => $size,
			8 => $time,
			'atime' => $time,
			9 => $time,
			'mtime' => $time,
			10 => $time,
			'ctime' => $time,
			11 => -1,
			'blksize' => -1,
			12 => -1,
			'blocks' => -1,
		);
	}

	public function stream_tell() {
		return $this->pointer;
	}

	public function stream_write($data) {
		if (!$this->writable) return 0;
		$size = strlen($data);
		$len = strlen(self::$data[$this->path]);
		if ($this->stream_eof()) {
			self::$data[$this->path] .= $data;
		} else {
			self::$data[$this->path] = substr_replace(
				self::$data[$this->path],
				$data,
				$this->pointer
			);
		}
		$this->pointer += $size;
		return $size;
	}

	public function unlink($path) {
		if (isset(self::$data[$path])) {
			unset(self::$data[$path]);
		}
		return true;
	}

	public function url_stat($path) {
		if (isset(self::$data[$path])) {
			$size = strlen(self::$data[$path]);
			$time = time();
			return array(
				0 => 0,
				'dev' => 0,
				1 => 0,
				'ino' => 0,
				2 => 0777,
				'mode' => 0777,
				3 => 1,
				'nlink' => 1,
				4 => 0,
				'uid' => 0,
				5 => 0,
				'gid' => 0,
				6 => '',
				'rdev' => '',
				7 => $size,
				'size' => $size,
				8 => $time,
				'atime' => $time,
				9 => $time,
				'mtime' => $time,
				10 => $time,
				'ctime' => $time,
				11 => -1,
				'blksize' => -1,
				12 => -1,
				'blocks' => -1,
			);
		}
		return false;
	}
}

/**
 * stream wrapper that provides a callback on stream close
 */
class OC_CloseStreamWrapper{
	public static $callBacks=array();
	private $path='';
	private $source;
	private static $open=array();
	public function stream_open($path, $mode, $options, &$opened_path){
		$path=substr($path,strlen('close://'));
		$this->path=$path;
		$this->source=fopen($path,$mode);
		if(is_resource($this->source)){
			$this->meta=stream_get_meta_data($this->source);
		}
		self::$open[]=$path;
		return is_resource($this->source);
	}

	public function stream_seek($offset, $whence=SEEK_SET){
		fseek($this->source,$offset,$whence);
	}

	public function stream_tell(){
		return ftell($this->source);
	}

	public function stream_read($count){
		return fread($this->source,$count);
	}

	public function stream_write($data){
		return fwrite($this->source,$data);
	}

	public function stream_set_option($option,$arg1,$arg2){
		switch($option){
			case STREAM_OPTION_BLOCKING:
				stream_set_blocking($this->source,$arg1);
				break;
			case STREAM_OPTION_READ_TIMEOUT:
				stream_set_timeout($this->source,$arg1,$arg2);
				break;
			case STREAM_OPTION_WRITE_BUFFER:
				stream_set_write_buffer($this->source,$arg1,$arg2);
		}
	}

	public function stream_stat(){
		return fstat($this->source);
	}

	public function stream_lock($mode){
		flock($this->source,$mode);
	}

	public function stream_flush(){
		return fflush($this->source);
	}

	public function stream_eof(){
		return feof($this->source);
	}

	public function url_stat($path) {
		$path=substr($path,strlen('close://'));
		if(file_exists($path)){
			return stat($path);
		}else{
			return false;
		}
	}

	public function stream_close(){
		fclose($this->source);
		if(isset(self::$callBacks[$this->path])){
			call_user_func(self::$callBacks[$this->path],$this->path);
		}
	}

	public function unlink($path){
		$path=substr($path,strlen('close://'));
		return unlink($path);
	}
}
