<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
//          also https://github.com/JamesHeinrich/getID3       //
/////////////////////////////////////////////////////////////////

namespace ID3Parser\getID3;

abstract class getid3_handler {

	/**
	 * @var getID3
	 */
	protected $getid3;                       // pointer

	protected $data_string_flag     = false; // analyzing filepointer or string
	protected $data_string          = '';    // string to analyze
	protected $data_string_position = 0;     // seek position in string
	protected $data_string_length   = 0;     // string length

	private $dependency_to = null;


	public function __construct(getID3 $getid3, $call_module=null) {
		$this->getid3 = $getid3;

		if ($call_module) {
			$this->dependency_to = str_replace('getid3_', '', $call_module);
		}
	}


	// Analyze from file pointer
	abstract public function Analyze();


	// Analyze from string instead
	public function AnalyzeString($string) {
		// Enter string mode
		$this->setStringMode($string);

		// Save info
		$saved_avdataoffset = $this->getid3->info['avdataoffset'];
		$saved_avdataend    = $this->getid3->info['avdataend'];
		$saved_filesize     = (isset($this->getid3->info['filesize']) ? $this->getid3->info['filesize'] : null); // may be not set if called as dependency without openfile() call

		// Reset some info
		$this->getid3->info['avdataoffset'] = 0;
		$this->getid3->info['avdataend']    = $this->getid3->info['filesize'] = $this->data_string_length;

		// Analyze
		$this->Analyze();

		// Restore some info
		$this->getid3->info['avdataoffset'] = $saved_avdataoffset;
		$this->getid3->info['avdataend']    = $saved_avdataend;
		$this->getid3->info['filesize']     = $saved_filesize;

		// Exit string mode
		$this->data_string_flag = false;
	}

	public function setStringMode($string) {
		$this->data_string_flag   = true;
		$this->data_string        = $string;
		$this->data_string_length = strlen($string);
	}

	protected function ftell() {
		if ($this->data_string_flag) {
			return $this->data_string_position;
		}
		return ftell($this->getid3->fp);
	}

	protected function fread($bytes) {
		if ($this->data_string_flag) {
			$this->data_string_position += $bytes;
			return substr($this->data_string, $this->data_string_position - $bytes, $bytes);
		}
		$pos = $this->ftell() + $bytes;
		if (!getid3_lib::intValueSupported($pos)) {
			throw new getid3_exception('cannot fread('.$bytes.' from '.$this->ftell().') because beyond PHP filesystem limit', 10);
		}

		// System fread function may return less data than requested in case the file is not a regular local file.
		// See http://php.net/manual/en/function.fread.php. Call it repeatedly in a loop if that is the case.
		$contents = '';
		do {
			$part = fread($this->getid3->fp, $bytes);
			$partLength = strlen($part);
			$bytes -= $partLength;
			$contents .= $part;
		} while ($bytes > 0 && $partLength > 0);
		return $contents;
	}

	protected function fseek($bytes, $whence=SEEK_SET) {
		if ($this->data_string_flag) {
			switch ($whence) {
				case SEEK_SET:
					$this->data_string_position = $bytes;
					break;

				case SEEK_CUR:
					$this->data_string_position += $bytes;
					break;

				case SEEK_END:
					$this->data_string_position = $this->data_string_length + $bytes;
					break;
			}
			return 0;
		} else {
			$pos = $bytes;
			if ($whence == SEEK_CUR) {
				$pos = $this->ftell() + $bytes;
			} elseif ($whence == SEEK_END) {
				$pos = $this->getid3->info['filesize'] + $bytes;
			}
			if (!getid3_lib::intValueSupported($pos)) {
				throw new getid3_exception('cannot fseek('.$pos.') because beyond PHP filesystem limit', 10);
			}
		}
		return fseek($this->getid3->fp, $bytes, $whence);
	}

	protected function feof() {
		if ($this->data_string_flag) {
			return $this->data_string_position >= $this->data_string_length;
		}
		return feof($this->getid3->fp);
	}

	final protected function isDependencyFor($module) {
		return $this->dependency_to == $module;
	}

	protected function error($text) {
		$this->getid3->info['error'][] = $text;

		return false;
	}

	protected function warning($text) {
		return $this->getid3->warning($text);
	}

	protected function notice($text) {
		// does nothing for now
	}
}

