<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
//          also https://github.com/JamesHeinrich/getID3       //
/////////////////////////////////////////////////////////////////

namespace ID3Parser\getID3;

class getid3 {
	// public: Settings
	private $encoding        = 'UTF-8';        // CASE SENSITIVE! - i.e. (must be supported by iconv()). Examples:  ISO-8859-1  UTF-8  UTF-16  UTF-16BE

	// public: Read buffer size in bytes
	private $option_fread_buffer_size = 32768;

	// Public variables
	public $filename;                         // Filename of file being analysed.
	public $fp;                               // Filepointer to file being analysed.
	public $info;                             // Result array.
	public $memory_limit = 0;

	// Protected variables
	protected $startup_error   = '';
	protected $startup_warning = '';

	const FREAD_BUFFER_SIZE = 32768;

	// public: constructor
	public function __construct() {

		// Check for PHP version
		$required_php_version = '5.3.0';
		if (version_compare(PHP_VERSION, $required_php_version, '<')) {
			$this->startup_error .= 'getID3() requires PHP v'.$required_php_version.' or higher - you are running v'.PHP_VERSION;
			return false;
		}

		// Check memory
		$this->memory_limit = ini_get('memory_limit');
		if (preg_match('#([0-9]+)M#i', $this->memory_limit, $matches)) {
			// could be stored as "16M" rather than 16777216 for example
			$this->memory_limit = $matches[1] * 1048576;
		} elseif (preg_match('#([0-9]+)G#i', $this->memory_limit, $matches)) { // The 'G' modifier is available since PHP 5.1.0
			// could be stored as "2G" rather than 2147483648 for example
			$this->memory_limit = $matches[1] * 1073741824;
		}
		if ($this->memory_limit <= 0) {
			// memory limits probably disabled
		} elseif ($this->memory_limit <= 4194304) {
			$this->startup_error .= 'PHP has less than 4MB available memory and will very likely run out. Increase memory_limit in php.ini';
		} elseif ($this->memory_limit <= 12582912) {
			$this->startup_warning .= 'PHP has less than 12MB available memory and might run out if all modules are loaded. Increase memory_limit in php.ini';
		}

		return true;
	}

	public function fread_buffer_size() {
		return $this->option_fread_buffer_size;
	}


	// public: setOption
	public function setOption($optArray) {
		if (!is_array($optArray) || empty($optArray)) {
			return false;
		}
		foreach ($optArray as $opt => $val) {
			if (isset($this->$opt) === false) {
				continue;
			}
			$this->$opt = $val;
		}
		return true;
	}

	public function openfile($filename) {
		try {
			if (!empty($this->startup_error)) {
				throw new getid3_exception($this->startup_error);
			}
			if (!empty($this->startup_warning)) {
				$this->warning($this->startup_warning);
			}

			// init result array and set parameters
			$this->filename = $filename;
			$this->info = array();

			// remote files not supported
			if (preg_match('/^(ht|f)tp:\/\//', $filename)) {
				throw new getid3_exception('Remote files are not supported - please copy the file locally first');
			}

			$filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
			$filename = preg_replace('#(.+)'.preg_quote(DIRECTORY_SEPARATOR).'{2,}#U', '\1'.DIRECTORY_SEPARATOR, $filename);

			// open local file
			//if (is_readable($filename) && is_file($filename) && ($this->fp = fopen($filename, 'rb'))) { // see http://www.getid3.org/phpBB3/viewtopic.php?t=1720
			if ((is_readable($filename) || file_exists($filename)) && is_file($filename) && ($this->fp = fopen($filename, 'rb'))) {
				// great
			} else {
				$errormessagelist = array();
				if (!is_readable($filename)) {
					$errormessagelist[] = '!is_readable';
				}
				if (!is_file($filename)) {
					$errormessagelist[] = '!is_file';
				}
				if (!file_exists($filename)) {
					$errormessagelist[] = '!file_exists';
				}
				if (empty($errormessagelist)) {
					$errormessagelist[] = 'fopen failed';
				}
				throw new getid3_exception('Could not open "'.$filename.'" ('.implode('; ', $errormessagelist).')');
			}

			$this->info['filesize'] = filesize($filename);
			// set redundant parameters - might be needed in some include file
			// filenames / filepaths in getID3 are always expressed with forward slashes (unix-style) for both Windows and other to try and minimize confusion
			$filename = str_replace('\\', '/', $filename);
			$this->info['filepath']     = str_replace('\\', '/', realpath(dirname($filename)));
			$this->info['filename']     = getid3_lib::mb_basename($filename);
			$this->info['filenamepath'] = $this->info['filepath'].'/'.$this->info['filename'];

			// set more parameters
			$this->info['avdataoffset']        = 0;
			$this->info['avdataend']           = $this->info['filesize'];
			$this->info['fileformat']          = '';                // filled in later
			$this->info['audio']['dataformat'] = '';                // filled in later, unset if not used
			$this->info['video']['dataformat'] = '';                // filled in later, unset if not used
			$this->info['tags']                = array();           // filled in later, unset if not used
			$this->info['error']               = array();           // filled in later, unset if not used
			$this->info['warning']             = array();           // filled in later, unset if not used
			$this->info['comments']            = array();           // filled in later, unset if not used
			$this->info['encoding']            = $this->encoding;   // required by id3v2 and iso modules - can be unset at the end if desired

			return true;

		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		return false;
	}

	// public: analyze file
	public function analyze($filename) {
		try {
			if (!$this->openfile($filename)) {
				return $this->info;
			}

			// Handle tags
			foreach (array('id3v2'=>'id3v2', 'id3v1'=>'id3v1') as $tag_name => $tag_key) {
				$option_tag = 'option_tag_'.$tag_name;
				if ($option_tag) {
					try {
						$tag_class = '\ID3Parser\getID3\Tags\getid3_'.$tag_name;
						/** @var Tags\getid3_id3v2|Tags\getid3_id3v2 $tag */
						$tag = new $tag_class($this);
						$tag->Analyze();
					}
					catch (getid3_exception $e) {
						throw $e;
					}
				}
			}
			if (isset($this->info['id3v2']['tag_offset_start'])) {
				$this->info['avdataoffset'] = max($this->info['avdataoffset'], $this->info['id3v2']['tag_offset_end']);
			}
			foreach (array('id3v1'=>'id3v1', 'apetag'=>'ape', 'lyrics3'=>'lyrics3') as $tag_name => $tag_key) {
				if (isset($this->info[$tag_key]['tag_offset_start'])) {
					$this->info['avdataend'] = min($this->info['avdataend'], $this->info[$tag_key]['tag_offset_start']);
				}
			}
		} catch (\Exception $e) {
			$this->error('Caught exception: '.$e->getMessage());
		}

		// return info array
		return $this->info;
	}


	// private: error handling
	public function error($message) {
		$this->CleanUp();
		if (!isset($this->info['error'])) {
			$this->info['error'] = array();
		}
		$this->info['error'][] = $message;
		return $this->info;
	}


	// private: warning handling
	public function warning($message) {
		$this->info['warning'][] = $message;
		return true;
	}


	// private: CleanUp
	private function CleanUp() {

		// remove possible empty keys
		$AVpossibleEmptyKeys = array('dataformat', 'bits_per_sample', 'encoder_options', 'streams', 'bitrate');
		foreach ($AVpossibleEmptyKeys as $dummy => $key) {
			if (empty($this->info['audio'][$key]) && isset($this->info['audio'][$key])) {
				unset($this->info['audio'][$key]);
			}
			if (empty($this->info['video'][$key]) && isset($this->info['video'][$key])) {
				unset($this->info['video'][$key]);
			}
		}

		// remove empty root keys
		if (!empty($this->info)) {
			foreach ($this->info as $key => $value) {
				if (empty($this->info[$key]) && ($this->info[$key] !== 0) && ($this->info[$key] !== '0')) {
					unset($this->info[$key]);
				}
			}
		}

		// remove meaningless entries from unknown-format files
		if (empty($this->info['fileformat'])) {
			if (isset($this->info['avdataoffset'])) {
				unset($this->info['avdataoffset']);
			}
			if (isset($this->info['avdataend'])) {
				unset($this->info['avdataend']);
			}
		}

		// remove possible duplicated identical entries
		if (!empty($this->info['error'])) {
			$this->info['error'] = array_values(array_unique($this->info['error']));
		}
		if (!empty($this->info['warning'])) {
			$this->info['warning'] = array_values(array_unique($this->info['warning']));
		}

		// remove "global variable" type keys
		unset($this->info['php_memory_limit']);

		return true;
	}
}
