<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// Please see readme.txt for more information                  //
//                                                            ///
/////////////////////////////////////////////////////////////////

// Defines
define('GETID3_VERSION', '1.7.9-20090308');
define('GETID3_FREAD_BUFFER_SIZE', 16384); // read buffer size in bytes



class getID3
{
	// public: Settings
	var $encoding        = 'ISO-8859-1';   // CASE SENSITIVE! - i.e. (must be supported by iconv())
	                                       // Examples:  ISO-8859-1  UTF-8  UTF-16  UTF-16BE

	var $encoding_id3v1  = 'ISO-8859-1';   // Should always be 'ISO-8859-1', but some tags may be written in other encodings such as 'EUC-CN'

	var $tempdir         = '*';            // default '*' should use system temp dir

	// public: Optional tag checks - disable for speed.
	var $option_tag_id3v1         = true;  // Read and process ID3v1 tags
	var $option_tag_id3v2         = true;  // Read and process ID3v2 tags
	var $option_tag_lyrics3       = true;  // Read and process Lyrics3 tags
	var $option_tag_apetag        = true;  // Read and process APE tags
	var $option_tags_process      = true;  // Copy tags to root key 'tags' and encode to $this->encoding
	var $option_tags_html         = true;  // Copy tags to root key 'tags_html' properly translated from various encodings to HTML entities

	// public: Optional tag/comment calucations
	var $option_extra_info        = true;  // Calculate additional info such as bitrate, channelmode etc

	// public: Optional calculations
	var $option_md5_data          = false; // Get MD5 sum of data part - slow
	var $option_md5_data_source   = false; // Use MD5 of source file if availble - only FLAC and OptimFROG
	var $option_sha1_data         = false; // Get SHA1 sum of data part - slow
	var $option_max_2gb_check     = true;  // Check whether file is larger than 2 Gb and thus not supported by PHP

	// private
	var $filename;


	// public: constructor
	function getID3()
	{

		$this->startup_error   = '';
		$this->startup_warning = '';

		// Check for PHP version >= 4.2.0
		if (phpversion() < '4.2.0') {
		    $this->startup_error .= 'getID3() requires PHP v4.2.0 or higher - you are running v'.phpversion();
		}

		// Check memory
		$memory_limit = ini_get('memory_limit');
		if (eregi('([0-9]+)M', $memory_limit, $matches)) {
			// could be stored as "16M" rather than 16777216 for example
			$memory_limit = $matches[1] * 1048576;
		}
		if ($memory_limit <= 0) {
			// memory limits probably disabled
		} elseif ($memory_limit <= 3145728) {
	    	$this->startup_error .= 'PHP has less than 3MB available memory and will very likely run out. Increase memory_limit in php.ini';
		} elseif ($memory_limit <= 12582912) {
	    	$this->startup_warning .= 'PHP has less than 12MB available memory and might run out if all modules are loaded. Increase memory_limit in php.ini';
		}

		// Check safe_mode off
		if ((bool) ini_get('safe_mode')) {
		    $this->warning('WARNING: Safe mode is on, shorten support disabled, md5data/sha1data for ogg vorbis disabled, ogg vorbos/flac tag writing disabled.');
		}


		// define a constant rather than looking up every time it is needed
		if (!defined('GETID3_OS_ISWINDOWS')) {
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
				define('GETID3_OS_ISWINDOWS', true);
			} else {
				define('GETID3_OS_ISWINDOWS', false);
			}
		}

		// Get base path of getID3() - ONCE
		if (!defined('GETID3_INCLUDEPATH')) {
			foreach (get_included_files() as $key => $val) {
				if (basename($val) == 'getid3.php') {
					define('GETID3_INCLUDEPATH', dirname($val).DIRECTORY_SEPARATOR);
					break;
				}
			}
		}

		// Load support library
		if (!include_once(GETID3_INCLUDEPATH.'getid3.lib.php')) {
			$this->startup_error .= 'getid3.lib.php is missing or corrupt';
		}


		// Needed for Windows only:
		// Define locations of helper applications for Shorten, VorbisComment, MetaFLAC
		//   as well as other helper functions such as head, tail, md5sum, etc
		// IMPORTANT: This path cannot have spaces in it. If neccesary, use the 8dot3 equivalent
		//   ie for "C:/Program Files/Apache/" put "C:/PROGRA~1/APACHE/"
		// IMPORTANT: This path must include the trailing slash
		if (GETID3_OS_ISWINDOWS && !defined('GETID3_HELPERAPPSDIR')) {

			$helperappsdir = GETID3_INCLUDEPATH.'..'.DIRECTORY_SEPARATOR.'helperapps'; // must not have any space in this path

			if (!is_dir($helperappsdir)) {
				$this->startup_error .= '"'.$helperappsdir.'" cannot be defined as GETID3_HELPERAPPSDIR because it does not exist';
			} elseif (strpos(realpath($helperappsdir), ' ') !== false) {
				$DirPieces = explode(DIRECTORY_SEPARATOR, realpath($helperappsdir));
				foreach ($DirPieces as $key => $value) {
					if ((strpos($value, '.') !== false) && (strpos($value, ' ') === false)) {
						if (strpos($value, '.') > 8) {
							$value = substr($value, 0, 6).'~1';
						}
					} elseif ((strpos($value, ' ') !== false) || strlen($value) > 8) {
						$value = substr($value, 0, 6).'~1';
					}
					$DirPieces[$key] = strtoupper($value);
				}
				$this->startup_error .= 'GETID3_HELPERAPPSDIR must not have any spaces in it - use 8dot3 naming convention if neccesary (on this server that would be something like "'.implode(DIRECTORY_SEPARATOR, $DirPieces).'" - NOTE: this may or may not be the actual 8.3 equivalent of "'.$helperappsdir.'", please double-check). You can run "dir /x" from the commandline to see the correct 8.3-style names.';
			}
			define('GETID3_HELPERAPPSDIR', realpath($helperappsdir).DIRECTORY_SEPARATOR);
		}

	}


	// public: setOption
	function setOption($optArray) {
		if (!is_array($optArray) || empty($optArray)) {
			return false;
		}
		foreach ($optArray as $opt => $val) {
			//if (isset($this, $opt) === false) {
			if (isset($this->$opt) === false) {
				continue;
			}
			$this->$opt = $val;
		}
		return true;
	}


	// public: analyze file - replaces GetAllFileInfo() and GetTagOnly()
	function analyze($filename) {

		if (!empty($this->startup_error)) {
			return $this->error($this->startup_error);
		}
		if (!empty($this->startup_warning)) {
			$this->warning($this->startup_warning);
		}

		// init result array and set parameters
		$this->info = array();
		$this->info['GETID3_VERSION'] = GETID3_VERSION;

		// Check encoding/iconv support
		if (!function_exists('iconv') && !in_array($this->encoding, array('ISO-8859-1', 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-16'))) {
			$errormessage = 'iconv() support is needed for encodings other than ISO-8859-1, UTF-8, UTF-16LE, UTF16-BE, UTF-16. ';
			if (GETID3_OS_ISWINDOWS) {
				$errormessage .= 'PHP does not have iconv() support. Please enable php_iconv.dll in php.ini, and copy iconv.dll from c:/php/dlls to c:/windows/system32';
			} else {
				$errormessage .= 'PHP is not compiled with iconv() support. Please recompile with the --with-iconv switch';
			}
	    	return $this->error($errormessage);
		}

		// Disable magic_quotes_runtime, if neccesary
		$old_magic_quotes_runtime = get_magic_quotes_runtime(); // store current setting of magic_quotes_runtime
		if ($old_magic_quotes_runtime) {
			set_magic_quotes_runtime(0);                        // turn off magic_quotes_runtime
			if (get_magic_quotes_runtime()) {
				return $this->error('Could not disable magic_quotes_runtime - getID3() cannot work properly with this setting enabled');
			}
		}

		// remote files not supported
		if (preg_match('/^(ht|f)tp:\/\//', $filename)) {
			return $this->error('Remote files are not supported in this version of getID3() - please copy the file locally first');
		}

		$filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
		$filename = preg_replace('#'.preg_quote(DIRECTORY_SEPARATOR).'{2,}#', DIRECTORY_SEPARATOR, $filename);

		// open local file
		if (file_exists($filename) && ($fp = @fopen($filename, 'rb'))) {
			// great
		} else {
			return $this->error('Could not open file "'.$filename.'"');
		}

		// set parameters
		$this->info['filesize'] = filesize($filename);

		// option_max_2gb_check
		if ($this->option_max_2gb_check) {
			// PHP doesn't support integers larger than 31-bit (~2GB)
			// filesize() simply returns (filesize % (pow(2, 32)), no matter the actual filesize
			// ftell() returns 0 if seeking to the end is beyond the range of unsigned integer
			fseek($fp, 0, SEEK_END);
			if ((($this->info['filesize'] != 0) && (ftell($fp) == 0)) ||
				($this->info['filesize'] < 0) ||
				(ftell($fp) < 0)) {
					$real_filesize = false;
					if (GETID3_OS_ISWINDOWS) {
						$commandline = 'dir /-C "'.str_replace('/', DIRECTORY_SEPARATOR, $filename).'"';
						$dir_output = `$commandline`;
						if (eregi('1 File\(s\)[ ]+([0-9]+) bytes', $dir_output, $matches)) {
							$real_filesize = (float) $matches[1];
						}
					} else {
						$commandline = 'ls -o -g -G --time-style=long-iso '.escapeshellarg($filename);
						$dir_output = `$commandline`;
						if (eregi('([0-9]+) ([0-9]{4}-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}) '.preg_quote($filename).'$', $dir_output, $matches)) {
							$real_filesize = (float) $matches[1];
						}
					}
					if ($real_filesize === false) {
						unset($this->info['filesize']);
						fclose($fp);
						return $this->error('File is most likely larger than 2GB and is not supported by PHP');
					} elseif ($real_filesize < pow(2, 31)) {
						unset($this->info['filesize']);
						fclose($fp);
						return $this->error('PHP seems to think the file is larger than 2GB, but filesystem reports it as '.number_format($real_filesize, 3).'GB, please report to info@getid3.org');
					}
					$this->info['filesize'] = $real_filesize;
					$this->error('File is larger than 2GB (filesystem reports it as '.number_format($real_filesize, 3).'GB) and is not properly supported by PHP.');
			}
		}

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

		// set redundant parameters - might be needed in some include file
		$this->info['filename']            = basename($filename);
		$this->info['filepath']            = str_replace('\\', '/', realpath(dirname($filename)));
		$this->info['filenamepath']        = $this->info['filepath'].'/'.$this->info['filename'];


		// handle ID3v2 tag - done first - already at beginning of file
		// ID3v2 detection (even if not parsing) is always done otherwise fileformat is much harder to detect
		if ($this->option_tag_id3v2) {

			$GETID3_ERRORARRAY = &$this->info['warning'];
			if (getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v2.php', __FILE__, false)) {
				$tag = new getid3_id3v2($fp, $this->info);
				unset($tag);
			}

		} else {

			fseek($fp, 0, SEEK_SET);
			$header = fread($fp, 10);
			if (substr($header, 0, 3) == 'ID3'  &&  strlen($header) == 10) {
				$this->info['id3v2']['header']           = true;
				$this->info['id3v2']['majorversion']     = ord($header{3});
				$this->info['id3v2']['minorversion']     = ord($header{4});
				$this->info['id3v2']['headerlength']     = getid3_lib::BigEndian2Int(substr($header, 6, 4), 1) + 10; // length of ID3v2 tag in 10-byte header doesn't include 10-byte header length

				$this->info['id3v2']['tag_offset_start'] = 0;
				$this->info['id3v2']['tag_offset_end']   = $this->info['id3v2']['tag_offset_start'] + $this->info['id3v2']['headerlength'];
				$this->info['avdataoffset']              = $this->info['id3v2']['tag_offset_end'];
			}

		}


		// handle ID3v1 tag
		if ($this->option_tag_id3v1) {
			if (!@include_once(GETID3_INCLUDEPATH.'module.tag.id3v1.php')) {
				return $this->error('module.tag.id3v1.php is missing - you may disable option_tag_id3v1.');
			}
			$tag = new getid3_id3v1($fp, $this->info);
			unset($tag);
		}

		// handle APE tag
		if ($this->option_tag_apetag) {
			if (!@include_once(GETID3_INCLUDEPATH.'module.tag.apetag.php')) {
				return $this->error('module.tag.apetag.php is missing - you may disable option_tag_apetag.');
			}
			$tag = new getid3_apetag($fp, $this->info);
			unset($tag);
		}

		// handle lyrics3 tag
		if ($this->option_tag_lyrics3) {
			if (!@include_once(GETID3_INCLUDEPATH.'module.tag.lyrics3.php')) {
				return $this->error('module.tag.lyrics3.php is missing - you may disable option_tag_lyrics3.');
			}
			$tag = new getid3_lyrics3($fp, $this->info);
			unset($tag);
		}

		// read 32 kb file data
		fseek($fp, $this->info['avdataoffset'], SEEK_SET);
		$formattest = fread($fp, 32774);

		// determine format
		$determined_format = $this->GetFileFormat($formattest, $filename);

		// unable to determine file format
		if (!$determined_format) {
			fclose($fp);
			return $this->error('unable to determine file format');
		}

		// check for illegal ID3 tags
		if (isset($determined_format['fail_id3']) && (in_array('id3v1', $this->info['tags']) || in_array('id3v2', $this->info['tags']))) {
			if ($determined_format['fail_id3'] === 'ERROR') {
				fclose($fp);
				return $this->error('ID3 tags not allowed on this file type.');
			} elseif ($determined_format['fail_id3'] === 'WARNING') {
				$this->info['warning'][] = 'ID3 tags not allowed on this file type.';
			}
		}

		// check for illegal APE tags
		if (isset($determined_format['fail_ape']) && in_array('ape', $this->info['tags'])) {
			if ($determined_format['fail_ape'] === 'ERROR') {
				fclose($fp);
				return $this->error('APE tags not allowed on this file type.');
			} elseif ($determined_format['fail_ape'] === 'WARNING') {
				$this->info['warning'][] = 'APE tags not allowed on this file type.';
			}
		}

		// set mime type
		$this->info['mime_type'] = $determined_format['mime_type'];

		// supported format signature pattern detected, but module deleted
		if (!file_exists(GETID3_INCLUDEPATH.$determined_format['include'])) {
			fclose($fp);
			return $this->error('Format not supported, module "'.$determined_format['include'].'" was removed.');
		}

		// module requires iconv support
        if (!function_exists('iconv') && @$determined_format['iconv_req']) {
		    return $this->error('iconv support is required for this module ('.$determined_format['include'].').');
		}

		// include module
		include_once(GETID3_INCLUDEPATH.$determined_format['include']);

		// instantiate module class
		$class_name = 'getid3_'.$determined_format['module'];
		if (!class_exists($class_name)) {
			return $this->error('Format not supported, module "'.$determined_format['include'].'" is corrupt.');
		}
		if (isset($determined_format['option'])) {
			$class = new $class_name($fp, $this->info, $determined_format['option']);
		} else {
			$class = new $class_name($fp, $this->info);
		}
		unset($class);

		// close file
		fclose($fp);

		// process all tags - copy to 'tags' and convert charsets
		if ($this->option_tags_process) {
			$this->HandleAllTags();
		}

		// perform more calculations
		if ($this->option_extra_info) {
			$this->ChannelsBitratePlaytimeCalculations();
			$this->CalculateCompressionRatioVideo();
			$this->CalculateCompressionRatioAudio();
			$this->CalculateReplayGain();
			$this->ProcessAudioStreams();
		}

		// get the MD5 sum of the audio/video portion of the file - without ID3/APE/Lyrics3/etc header/footer tags
		if ($this->option_md5_data) {
			// do not cald md5_data if md5_data_source is present - set by flac only - future MPC/SV8 too
			if (!$this->option_md5_data_source || empty($this->info['md5_data_source'])) {
				$this->getHashdata('md5');
			}
		}

		// get the SHA1 sum of the audio/video portion of the file - without ID3/APE/Lyrics3/etc header/footer tags
		if ($this->option_sha1_data) {
			$this->getHashdata('sha1');
		}

		// remove undesired keys
		$this->CleanUp();

		// restore magic_quotes_runtime setting
		set_magic_quotes_runtime($old_magic_quotes_runtime);

		// return info array
		return $this->info;
	}


	// private: error handling
	function error($message) {

		$this->CleanUp();

		$this->info['error'][] = $message;
		return $this->info;
	}


	// private: warning handling
	function warning($message) {
		$this->info['warning'][] = $message;
		return true;
	}


	// private: CleanUp
	function CleanUp() {

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
	}


	// return array containing information about all supported formats
	function GetFileFormatArray() {
		static $format_info = array();
		if (empty($format_info)) {
			$format_info = array(

				// Audio formats

				// AC-3   - audio      - Dolby AC-3 / Dolby Digital
				'ac3'  => array(
							'pattern'   => '^\x0B\x77',
							'group'     => 'audio',
							'module'    => 'ac3',
							'mime_type' => 'audio/ac3',
						),

				// AAC  - audio       - Advanced Audio Coding (AAC) - ADIF format
				'adif' => array(
							'pattern'   => '^ADIF',
							'group'     => 'audio',
							'module'    => 'aac',
							'option'    => 'adif',
							'mime_type' => 'application/octet-stream',
							'fail_ape'  => 'WARNING',
						),


				// AAC  - audio       - Advanced Audio Coding (AAC) - ADTS format (very similar to MP3)
				'adts' => array(
							'pattern'   => '^\xFF[\xF0-\xF1\xF8-\xF9]',
							'group'     => 'audio',
							'module'    => 'aac',
							'option'    => 'adts',
							'mime_type' => 'application/octet-stream',
							'fail_ape'  => 'WARNING',
						),


				// AU   - audio       - NeXT/Sun AUdio (AU)
				'au'   => array(
							'pattern'   => '^\.snd',
							'group'     => 'audio',
							'module'    => 'au',
							'mime_type' => 'audio/basic',
						),

				// AVR  - audio       - Audio Visual Research
				'avr'  => array(
							'pattern'   => '^2BIT',
							'group'     => 'audio',
							'module'    => 'avr',
							'mime_type' => 'application/octet-stream',
						),

				// BONK - audio       - Bonk v0.9+
				'bonk' => array(
							'pattern'   => '^\x00(BONK|INFO|META| ID3)',
							'group'     => 'audio',
							'module'    => 'bonk',
							'mime_type' => 'audio/xmms-bonk',
						),

				// DSS  - audio       - Digital Speech Standard
				'dss'  => array(
							'pattern'   => '^[\x02]dss',
							'group'     => 'audio',
							'module'    => 'dss',
							'mime_type' => 'application/octet-stream',
						),

				// DTS  - audio       - Dolby Theatre System
				'dts'  => array(
							'pattern'   => '^\x7F\xFE\x80\x01',
							'group'     => 'audio',
							'module'    => 'dts',
							'mime_type' => 'audio/dts',
						),

				// FLAC - audio       - Free Lossless Audio Codec
				'flac' => array(
							'pattern'   => '^fLaC',
							'group'     => 'audio',
							'module'    => 'flac',
							'mime_type' => 'audio/x-flac',
						),

				// LA   - audio       - Lossless Audio (LA)
				'la'   => array(
							'pattern'   => '^LA0[2-4]',
							'group'     => 'audio',
							'module'    => 'la',
							'mime_type' => 'application/octet-stream',
						),

				// LPAC - audio       - Lossless Predictive Audio Compression (LPAC)
				'lpac' => array(
							'pattern'   => '^LPAC',
							'group'     => 'audio',
							'module'    => 'lpac',
							'mime_type' => 'application/octet-stream',
						),

				// MIDI - audio       - MIDI (Musical Instrument Digital Interface)
				'midi' => array(
							'pattern'   => '^MThd',
							'group'     => 'audio',
							'module'    => 'midi',
							'mime_type' => 'audio/midi',
						),

				// MAC  - audio       - Monkey's Audio Compressor
				'mac'  => array(
							'pattern'   => '^MAC ',
							'group'     => 'audio',
							'module'    => 'monkey',
							'mime_type' => 'application/octet-stream',
						),

// has been known to produce false matches in random files (e.g. JPEGs), leave out until more precise matching available
//				// MOD  - audio       - MODule (assorted sub-formats)
//				'mod'  => array(
//							'pattern'   => '^.{1080}(M\\.K\\.|M!K!|FLT4|FLT8|[5-9]CHN|[1-3][0-9]CH)',
//							'group'     => 'audio',
//							'module'    => 'mod',
//							'option'    => 'mod',
//							'mime_type' => 'audio/mod',
//						),

				// MOD  - audio       - MODule (Impulse Tracker)
				'it'   => array(
							'pattern'   => '^IMPM',
							'group'     => 'audio',
							'module'    => 'mod',
							'option'    => 'it',
							'mime_type' => 'audio/it',
						),

				// MOD  - audio       - MODule (eXtended Module, various sub-formats)
				'xm'   => array(
							'pattern'   => '^Extended Module',
							'group'     => 'audio',
							'module'    => 'mod',
							'option'    => 'xm',
							'mime_type' => 'audio/xm',
						),

				// MOD  - audio       - MODule (ScreamTracker)
				's3m'  => array(
							'pattern'   => '^.{44}SCRM',
							'group'     => 'audio',
							'module'    => 'mod',
							'option'    => 's3m',
							'mime_type' => 'audio/s3m',
						),

				// MPC  - audio       - Musepack / MPEGplus
				'mpc'  => array(
							'pattern'   => '^(MPCK|MP\+|[\x00\x01\x10\x11\x40\x41\x50\x51\x80\x81\x90\x91\xC0\xC1\xD0\xD1][\x20-37][\x00\x20\x40\x60\x80\xA0\xC0\xE0])',
							'group'     => 'audio',
							'module'    => 'mpc',
							'mime_type' => 'audio/x-musepack',
						),

				// MP3  - audio       - MPEG-audio Layer 3 (very similar to AAC-ADTS)
				'mp3'  => array(
							'pattern'   => '^\xFF[\xE2-\xE7\xF2-\xF7\xFA-\xFF][\x00-\xEB]',
							'group'     => 'audio',
							'module'    => 'mp3',
							'mime_type' => 'audio/mpeg',
						),

				// OFR  - audio       - OptimFROG
				'ofr'  => array(
							'pattern'   => '^(\*RIFF|OFR)',
							'group'     => 'audio',
							'module'    => 'optimfrog',
							'mime_type' => 'application/octet-stream',
						),

				// RKAU - audio       - RKive AUdio compressor
				'rkau' => array(
							'pattern'   => '^RKA',
							'group'     => 'audio',
							'module'    => 'rkau',
							'mime_type' => 'application/octet-stream',
						),

				// SHN  - audio       - Shorten
				'shn'  => array(
							'pattern'   => '^ajkg',
							'group'     => 'audio',
							'module'    => 'shorten',
							'mime_type' => 'audio/xmms-shn',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// TTA  - audio       - TTA Lossless Audio Compressor (http://tta.corecodec.org)
				'tta'  => array(
							'pattern'   => '^TTA',  // could also be '^TTA(\x01|\x02|\x03|2|1)'
							'group'     => 'audio',
							'module'    => 'tta',
							'mime_type' => 'application/octet-stream',
						),

				// VOC  - audio       - Creative Voice (VOC)
				'voc'  => array(
							'pattern'   => '^Creative Voice File',
							'group'     => 'audio',
							'module'    => 'voc',
							'mime_type' => 'audio/voc',
						),

				// VQF  - audio       - transform-domain weighted interleave Vector Quantization Format (VQF)
				'vqf'  => array(
							'pattern'   => '^TWIN',
							'group'     => 'audio',
							'module'    => 'vqf',
							'mime_type' => 'application/octet-stream',
						),

				// WV  - audio        - WavPack (v4.0+)
				'wv'   => array(
							'pattern'   => '^wvpk',
							'group'     => 'audio',
							'module'    => 'wavpack',
							'mime_type' => 'application/octet-stream',
						),


				// Audio-Video formats

				// ASF  - audio/video - Advanced Streaming Format, Windows Media Video, Windows Media Audio
				'asf'  => array(
							'pattern'   => '^\x30\x26\xB2\x75\x8E\x66\xCF\x11\xA6\xD9\x00\xAA\x00\x62\xCE\x6C',
							'group'     => 'audio-video',
							'module'    => 'asf',
							'mime_type' => 'video/x-ms-asf',
							'iconv_req' => false,
						),

				// BINK - audio/video - Bink / Smacker
				'bink' => array(
							'pattern'   => '^(BIK|SMK)',
							'group'     => 'audio-video',
							'module'    => 'bink',
							'mime_type' => 'application/octet-stream',
						),

				// FLV  - audio/video - FLash Video
				'flv' => array(
							'pattern'   => '^FLV\x01',
							'group'     => 'audio-video',
							'module'    => 'flv',
							'mime_type' => 'video/x-flv',
						),

				// MKAV - audio/video - Mastroka
				'matroska' => array(
							'pattern'   => '^\x1A\x45\xDF\xA3',
							'group'     => 'audio-video',
							'module'    => 'matroska',
							'mime_type' => 'video/x-matroska', // may also be audio/x-matroska
						),

				// MPEG - audio/video - MPEG (Moving Pictures Experts Group)
				'mpeg' => array(
							'pattern'   => '^\x00\x00\x01(\xBA|\xB3)',
							'group'     => 'audio-video',
							'module'    => 'mpeg',
							'mime_type' => 'video/mpeg',
						),

				// NSV  - audio/video - Nullsoft Streaming Video (NSV)
				'nsv'  => array(
							'pattern'   => '^NSV[sf]',
							'group'     => 'audio-video',
							'module'    => 'nsv',
							'mime_type' => 'application/octet-stream',
						),

				// Ogg  - audio/video - Ogg (Ogg-Vorbis, Ogg-FLAC, Speex, Ogg-Theora(*), Ogg-Tarkin(*))
				'ogg'  => array(
							'pattern'   => '^OggS',
							'group'     => 'audio',
							'module'    => 'ogg',
							'mime_type' => 'application/ogg',
							'fail_id3'  => 'WARNING',
							'fail_ape'  => 'WARNING',
						),

				// QT   - audio/video - Quicktime
				'quicktime' => array(
							'pattern'   => '^.{4}(cmov|free|ftyp|mdat|moov|pnot|skip|wide)',
							'group'     => 'audio-video',
							'module'    => 'quicktime',
							'mime_type' => 'video/quicktime',
						),

				// RIFF - audio/video - Resource Interchange File Format (RIFF) / WAV / AVI / CD-audio / SDSS = renamed variant used by SmartSound QuickTracks (www.smartsound.com) / FORM = Audio Interchange File Format (AIFF)
				'riff' => array(
							'pattern'   => '^(RIFF|SDSS|FORM)',
							'group'     => 'audio-video',
							'module'    => 'riff',
							'mime_type' => 'audio/x-wave',
							'fail_ape'  => 'WARNING',
						),

				// Real - audio/video - RealAudio, RealVideo
				'real' => array(
							'pattern'   => '^(\\.RMF|\\.ra)',
							'group'     => 'audio-video',
							'module'    => 'real',
							'mime_type' => 'audio/x-realaudio',
						),

				// SWF - audio/video - ShockWave Flash
				'swf' => array(
							'pattern'   => '^(F|C)WS',
							'group'     => 'audio-video',
							'module'    => 'swf',
							'mime_type' => 'application/x-shockwave-flash',
						),


				// Still-Image formats

				// BMP  - still image - Bitmap (Windows, OS/2; uncompressed, RLE8, RLE4)
				'bmp'  => array(
							'pattern'   => '^BM',
							'group'     => 'graphic',
							'module'    => 'bmp',
							'mime_type' => 'image/bmp',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// GIF  - still image - Graphics Interchange Format
				'gif'  => array(
							'pattern'   => '^GIF',
							'group'     => 'graphic',
							'module'    => 'gif',
							'mime_type' => 'image/gif',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// JPEG - still image - Joint Photographic Experts Group (JPEG)
				'jpg'  => array(
							'pattern'   => '^\xFF\xD8\xFF',
							'group'     => 'graphic',
							'module'    => 'jpg',
							'mime_type' => 'image/jpeg',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// PCD  - still image - Kodak Photo CD
				'pcd'  => array(
							'pattern'   => '^.{2048}PCD_IPI\x00',
							'group'     => 'graphic',
							'module'    => 'pcd',
							'mime_type' => 'image/x-photo-cd',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),


				// PNG  - still image - Portable Network Graphics (PNG)
				'png'  => array(
							'pattern'   => '^\x89\x50\x4E\x47\x0D\x0A\x1A\x0A',
							'group'     => 'graphic',
							'module'    => 'png',
							'mime_type' => 'image/png',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),


                // SVG  - still image - Scalable Vector Graphics (SVG)
				'svg'  => array(
							'pattern'   => '<!DOCTYPE svg PUBLIC ',
							'group'     => 'graphic',
							'module'    => 'svg',
							'mime_type' => 'image/svg+xml',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),


				// TIFF  - still image - Tagged Information File Format (TIFF)
				'tiff' => array(
							'pattern'   => '^(II\x2A\x00|MM\x00\x2A)',
							'group'     => 'graphic',
							'module'    => 'tiff',
							'mime_type' => 'image/tiff',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),


				// Data formats

				// ISO  - data        - International Standards Organization (ISO) CD-ROM Image
				'iso'  => array(
							'pattern'   => '^.{32769}CD001',
							'group'     => 'misc',
							'module'    => 'iso',
							'mime_type' => 'application/octet-stream',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
							'iconv_req' => false,
						),

				// RAR  - data        - RAR compressed data
				'rar'  => array(
							'pattern'   => '^Rar\!',
							'group'     => 'archive',
							'module'    => 'rar',
							'mime_type' => 'application/octet-stream',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// SZIP - audio/data  - SZIP compressed data
				'szip' => array(
							'pattern'   => '^SZ\x0A\x04',
							'group'     => 'archive',
							'module'    => 'szip',
							'mime_type' => 'application/octet-stream',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// TAR  - data        - TAR compressed data
				'tar'  => array(
							'pattern'   => '^.{100}[0-9\x20]{7}\x00[0-9\x20]{7}\x00[0-9\x20]{7}\x00[0-9\x20\x00]{12}[0-9\x20\x00]{12}',
							'group'     => 'archive',
							'module'    => 'tar',
							'mime_type' => 'application/x-tar',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// GZIP  - data        - GZIP compressed data
				'gz'  => array(
							'pattern'   => '^\x1F\x8B\x08',
							'group'     => 'archive',
							'module'    => 'gzip',
							'mime_type' => 'application/x-gzip',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// ZIP  - data         - ZIP compressed data
				'zip'  => array(
							'pattern'   => '^PK\x03\x04',
							'group'     => 'archive',
							'module'    => 'zip',
							'mime_type' => 'application/zip',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),


				// Misc other formats

                // PAR2 - data        - Parity Volume Set Specification 2.0
                'par2' => array (
                			'pattern'   => '^PAR2\x00PKT',
                			'group'     => 'misc',
							'module'    => 'par2',
							'mime_type' => 'application/octet-stream',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// PDF  - data        - Portable Document Format
				'pdf'  => array(
							'pattern'   => '^\x25PDF',
							'group'     => 'misc',
							'module'    => 'pdf',
							'mime_type' => 'application/pdf',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),

				// MSOFFICE  - data   - ZIP compressed data
				'msoffice' => array(
							'pattern'   => '^\xD0\xCF\x11\xE0', // D0CF11E == DOCFILE == Microsoft Office Document
							'group'     => 'misc',
							'module'    => 'msoffice',
							'mime_type' => 'application/octet-stream',
							'fail_id3'  => 'ERROR',
							'fail_ape'  => 'ERROR',
						),
			);
		}

		return $format_info;
	}



	function GetFileFormat(&$filedata, $filename='') {
		// this function will determine the format of a file based on usually
		// the first 2-4 bytes of the file (8 bytes for PNG, 16 bytes for JPG,
		// and in the case of ISO CD image, 6 bytes offset 32kb from the start
		// of the file).

		// Identify file format - loop through $format_info and detect with reg expr
		foreach ($this->GetFileFormatArray() as $format_name => $info) {
			// Using preg_match() instead of ereg() - much faster
			// The /s switch on preg_match() forces preg_match() NOT to treat
			// newline (0x0A) characters as special chars but do a binary match
			if (preg_match('/'.$info['pattern'].'/s', $filedata)) {
				$info['include'] = 'module.'.$info['group'].'.'.$info['module'].'.php';
				return $info;
			}
		}


		if (preg_match('/\.mp[123a]$/i', $filename)) {
			// Too many mp3 encoders on the market put gabage in front of mpeg files
			// use assume format on these if format detection failed
			$GetFileFormatArray = $this->GetFileFormatArray();
			$info = $GetFileFormatArray['mp3'];
			$info['include'] = 'module.'.$info['group'].'.'.$info['module'].'.php';
			return $info;
		}

		return false;
	}


	// converts array to $encoding charset from $this->encoding
	function CharConvert(&$array, $encoding) {

		// identical encoding - end here
		if ($encoding == $this->encoding) {
			return;
		}

		// loop thru array
		foreach ($array as $key => $value) {

			// go recursive
			if (is_array($value)) {
				$this->CharConvert($array[$key], $encoding);
			}

			// convert string
			elseif (is_string($value)) {
				$array[$key] = trim(getid3_lib::iconv_fallback($encoding, $this->encoding, $value));
			}
		}
	}


	function HandleAllTags() {

		// key name => array (tag name, character encoding)
		static $tags;
		if (empty($tags)) {
			$tags = array(
				'asf'       => array('asf'           , 'UTF-16LE'),
				'midi'      => array('midi'          , 'ISO-8859-1'),
				'nsv'       => array('nsv'           , 'ISO-8859-1'),
				'ogg'       => array('vorbiscomment' , 'UTF-8'),
				'png'       => array('png'           , 'UTF-8'),
				'tiff'      => array('tiff'          , 'ISO-8859-1'),
				'quicktime' => array('quicktime'     , 'ISO-8859-1'),
				'real'      => array('real'          , 'ISO-8859-1'),
				'vqf'       => array('vqf'           , 'ISO-8859-1'),
				'zip'       => array('zip'           , 'ISO-8859-1'),
				'riff'      => array('riff'          , 'ISO-8859-1'),
				'lyrics3'   => array('lyrics3'       , 'ISO-8859-1'),
				'id3v1'     => array('id3v1'         , $this->encoding_id3v1),
				'id3v2'     => array('id3v2'         , 'UTF-8'), // not according to the specs (every frame can have a different encoding), but getID3() force-converts all encodings to UTF-8
				'ape'       => array('ape'           , 'UTF-8')
			);
		}

		// loop thru comments array
		foreach ($tags as $comment_name => $tagname_encoding_array) {
			list($tag_name, $encoding) = $tagname_encoding_array;

			// fill in default encoding type if not already present
			if (isset($this->info[$comment_name]) && !isset($this->info[$comment_name]['encoding'])) {
				$this->info[$comment_name]['encoding'] = $encoding;
			}

			// copy comments if key name set
			if (!empty($this->info[$comment_name]['comments'])) {

				foreach ($this->info[$comment_name]['comments'] as $tag_key => $valuearray) {
					foreach ($valuearray as $key => $value) {
						if (strlen(trim($value)) > 0) {
							$this->info['tags'][trim($tag_name)][trim($tag_key)][] = $value; // do not trim!! Unicode characters will get mangled if trailing nulls are removed!
						}
					}
				}

				if (!isset($this->info['tags'][$tag_name])) {
					// comments are set but contain nothing but empty strings, so skip
					continue;
				}

				if ($this->option_tags_html) {
					foreach ($this->info['tags'][$tag_name] as $tag_key => $valuearray) {
						foreach ($valuearray as $key => $value) {
							if (is_string($value)) {
								//$this->info['tags_html'][$tag_name][$tag_key][$key] = getid3_lib::MultiByteCharString2HTML($value, $encoding);
								$this->info['tags_html'][$tag_name][$tag_key][$key] = str_replace('&#0;', '', getid3_lib::MultiByteCharString2HTML($value, $encoding));
							} else {
								$this->info['tags_html'][$tag_name][$tag_key][$key] = $value;
							}
						}
					}
				}

				$this->CharConvert($this->info['tags'][$tag_name], $encoding);           // only copy gets converted!
			}

		}
		return true;
	}


	function getHashdata($algorithm) {
		switch ($algorithm) {
			case 'md5':
			case 'sha1':
				break;

			default:
				return $this->error('bad algorithm "'.$algorithm.'" in getHashdata()');
				break;
		}

		if ((@$this->info['fileformat'] == 'ogg') && (@$this->info['audio']['dataformat'] == 'vorbis')) {

			// We cannot get an identical md5_data value for Ogg files where the comments
			// span more than 1 Ogg page (compared to the same audio data with smaller
			// comments) using the normal getID3() method of MD5'ing the data between the
			// end of the comments and the end of the file (minus any trailing tags),
			// because the page sequence numbers of the pages that the audio data is on
			// do not match. Under normal circumstances, where comments are smaller than
			// the nominal 4-8kB page size, then this is not a problem, but if there are
			// very large comments, the only way around it is to strip off the comment
			// tags with vorbiscomment and MD5 that file.
			// This procedure must be applied to ALL Ogg files, not just the ones with
			// comments larger than 1 page, because the below method simply MD5's the
			// whole file with the comments stripped, not just the portion after the
			// comments block (which is the standard getID3() method.

			// The above-mentioned problem of comments spanning multiple pages and changing
			// page sequence numbers likely happens for OggSpeex and OggFLAC as well, but
			// currently vorbiscomment only works on OggVorbis files.

			if ((bool) ini_get('safe_mode')) {

				$this->info['warning'][] = 'Failed making system call to vorbiscomment.exe - '.$algorithm.'_data is incorrect - error returned: PHP running in Safe Mode (backtick operator not available)';
				$this->info[$algorithm.'_data']  = false;

			} else {

				// Prevent user from aborting script
				$old_abort = ignore_user_abort(true);

				// Create empty file
				$empty = tempnam('*', 'getID3');
				touch($empty);


				// Use vorbiscomment to make temp file without comments
				$temp = tempnam('*', 'getID3');
				$file = $this->info['filenamepath'];

				if (GETID3_OS_ISWINDOWS) {

					if (file_exists(GETID3_HELPERAPPSDIR.'vorbiscomment.exe')) {

						$commandline = '"'.GETID3_HELPERAPPSDIR.'vorbiscomment.exe" -w -c "'.$empty.'" "'.$file.'" "'.$temp.'"';
						$VorbisCommentError = `$commandline`;

					} else {

						$VorbisCommentError = 'vorbiscomment.exe not found in '.GETID3_HELPERAPPSDIR;

					}

				} else {

					$commandline = 'vorbiscomment -w -c "'.$empty.'" "'.$file.'" "'.$temp.'" 2>&1';
					$commandline = 'vorbiscomment -w -c '.escapeshellarg($empty).' '.escapeshellarg($file).' '.escapeshellarg($temp).' 2>&1';
					$VorbisCommentError = `$commandline`;

				}

				if (!empty($VorbisCommentError)) {

					$this->info['warning'][]         = 'Failed making system call to vorbiscomment(.exe) - '.$algorithm.'_data will be incorrect. If vorbiscomment is unavailable, please download from http://www.vorbis.com/download.psp and put in the getID3() directory. Error returned: '.$VorbisCommentError;
					$this->info[$algorithm.'_data']  = false;

				} else {

					// Get hash of newly created file
					switch ($algorithm) {
						case 'md5':
							$this->info[$algorithm.'_data'] = getid3_lib::md5_file($temp);
							break;

						case 'sha1':
							$this->info[$algorithm.'_data'] = getid3_lib::sha1_file($temp);
							break;
					}
				}

				// Clean up
				unlink($empty);
				unlink($temp);

				// Reset abort setting
				ignore_user_abort($old_abort);

			}

		} else {

			if (!empty($this->info['avdataoffset']) || (isset($this->info['avdataend']) && ($this->info['avdataend'] < $this->info['filesize']))) {

				// get hash from part of file
				$this->info[$algorithm.'_data'] = getid3_lib::hash_data($this->info['filenamepath'], $this->info['avdataoffset'], $this->info['avdataend'], $algorithm);

			} else {

				// get hash from whole file
				switch ($algorithm) {
					case 'md5':
						$this->info[$algorithm.'_data'] = getid3_lib::md5_file($this->info['filenamepath']);
						break;

					case 'sha1':
						$this->info[$algorithm.'_data'] = getid3_lib::sha1_file($this->info['filenamepath']);
						break;
				}
			}

		}
		return true;
	}


	function ChannelsBitratePlaytimeCalculations() {

		// set channelmode on audio
		if (@$this->info['audio']['channels'] == '1') {
			$this->info['audio']['channelmode'] = 'mono';
		} elseif (@$this->info['audio']['channels'] == '2') {
			$this->info['audio']['channelmode'] = 'stereo';
		}

		// Calculate combined bitrate - audio + video
		$CombinedBitrate  = 0;
		$CombinedBitrate += (isset($this->info['audio']['bitrate']) ? $this->info['audio']['bitrate'] : 0);
		$CombinedBitrate += (isset($this->info['video']['bitrate']) ? $this->info['video']['bitrate'] : 0);
		if (($CombinedBitrate > 0) && empty($this->info['bitrate'])) {
			$this->info['bitrate'] = $CombinedBitrate;
		}
		//if ((isset($this->info['video']) && !isset($this->info['video']['bitrate'])) || (isset($this->info['audio']) && !isset($this->info['audio']['bitrate']))) {
		//	// for example, VBR MPEG video files cannot determine video bitrate:
		//	// should not set overall bitrate and playtime from audio bitrate only
		//	unset($this->info['bitrate']);
		//}

		// video bitrate undetermined, but calculable
		if (isset($this->info['video']['dataformat']) && $this->info['video']['dataformat'] && (!isset($this->info['video']['bitrate']) || ($this->info['video']['bitrate'] == 0))) {
			// if video bitrate not set
			if (isset($this->info['audio']['bitrate']) && ($this->info['audio']['bitrate'] > 0) && ($this->info['audio']['bitrate'] == $this->info['bitrate'])) {
				// AND if audio bitrate is set to same as overall bitrate
				if (isset($this->info['playtime_seconds']) && ($this->info['playtime_seconds'] > 0)) {
					// AND if playtime is set
					if (isset($this->info['avdataend']) && isset($this->info['avdataoffset'])) {
						// AND if AV data offset start/end is known
						// THEN we can calculate the video bitrate
						$this->info['bitrate'] = round((($this->info['avdataend'] - $this->info['avdataoffset']) * 8) / $this->info['playtime_seconds']);
						$this->info['video']['bitrate'] = $this->info['bitrate'] - $this->info['audio']['bitrate'];
					}
				}
			}
		}

		if ((!isset($this->info['playtime_seconds']) || ($this->info['playtime_seconds'] <= 0)) && !empty($this->info['bitrate'])) {
			$this->info['playtime_seconds'] = (($this->info['avdataend'] - $this->info['avdataoffset']) * 8) / $this->info['bitrate'];
		}

		if (!isset($this->info['bitrate']) && !empty($this->info['playtime_seconds'])) {
			$this->info['bitrate'] = (($this->info['avdataend'] - $this->info['avdataoffset']) * 8) / $this->info['playtime_seconds'];
		}
//echo '<pre>';
//var_dump($this->info['bitrate']);
//var_dump($this->info['audio']['bitrate']);
//var_dump($this->info['video']['bitrate']);
//echo '</pre>';
		if (isset($this->info['bitrate']) && empty($this->info['audio']['bitrate']) && empty($this->info['video']['bitrate'])) {
			if (isset($this->info['audio']['dataformat']) && empty($this->info['video']['resolution_x'])) {
				// audio only
				$this->info['audio']['bitrate'] = $this->info['bitrate'];
			} elseif (isset($this->info['video']['resolution_x']) && empty($this->info['audio']['dataformat'])) {
				// video only
				$this->info['video']['bitrate'] = $this->info['bitrate'];
			}
		}

		// Set playtime string
		if (!empty($this->info['playtime_seconds']) && empty($this->info['playtime_string'])) {
			$this->info['playtime_string'] = getid3_lib::PlaytimeString($this->info['playtime_seconds']);
		}
	}


	function CalculateCompressionRatioVideo() {
		if (empty($this->info['video'])) {
			return false;
		}
		if (empty($this->info['video']['resolution_x']) || empty($this->info['video']['resolution_y'])) {
			return false;
		}
		if (empty($this->info['video']['bits_per_sample'])) {
			return false;
		}

		switch ($this->info['video']['dataformat']) {
			case 'bmp':
			case 'gif':
			case 'jpeg':
			case 'jpg':
			case 'png':
			case 'tiff':
				$FrameRate = 1;
				$PlaytimeSeconds = 1;
				$BitrateCompressed = $this->info['filesize'] * 8;
				break;

			default:
				if (!empty($this->info['video']['frame_rate'])) {
					$FrameRate = $this->info['video']['frame_rate'];
				} else {
					return false;
				}
				if (!empty($this->info['playtime_seconds'])) {
					$PlaytimeSeconds = $this->info['playtime_seconds'];
				} else {
					return false;
				}
				if (!empty($this->info['video']['bitrate'])) {
					$BitrateCompressed = $this->info['video']['bitrate'];
				} else {
					return false;
				}
				break;
		}
		$BitrateUncompressed = $this->info['video']['resolution_x'] * $this->info['video']['resolution_y'] * $this->info['video']['bits_per_sample'] * $FrameRate;

		$this->info['video']['compression_ratio'] = $BitrateCompressed / $BitrateUncompressed;
		return true;
	}


	function CalculateCompressionRatioAudio() {
		if (empty($this->info['audio']['bitrate']) || empty($this->info['audio']['channels']) || empty($this->info['audio']['sample_rate'])) {
			return false;
		}
		$this->info['audio']['compression_ratio'] = $this->info['audio']['bitrate'] / ($this->info['audio']['channels'] * $this->info['audio']['sample_rate'] * (!empty($this->info['audio']['bits_per_sample']) ? $this->info['audio']['bits_per_sample'] : 16));

		if (!empty($this->info['audio']['streams'])) {
			foreach ($this->info['audio']['streams'] as $streamnumber => $streamdata) {
				if (!empty($streamdata['bitrate']) && !empty($streamdata['channels']) && !empty($streamdata['sample_rate'])) {
					$this->info['audio']['streams'][$streamnumber]['compression_ratio'] = $streamdata['bitrate'] / ($streamdata['channels'] * $streamdata['sample_rate'] * (!empty($streamdata['bits_per_sample']) ? $streamdata['bits_per_sample'] : 16));
				}
			}
		}
		return true;
	}


	function CalculateReplayGain() {
		if (isset($this->info['replay_gain'])) {
			$this->info['replay_gain']['reference_volume'] = 89;
			if (isset($this->info['replay_gain']['track']['adjustment'])) {
				$this->info['replay_gain']['track']['volume'] = $this->info['replay_gain']['reference_volume'] - $this->info['replay_gain']['track']['adjustment'];
			}
			if (isset($this->info['replay_gain']['album']['adjustment'])) {
				$this->info['replay_gain']['album']['volume'] = $this->info['replay_gain']['reference_volume'] - $this->info['replay_gain']['album']['adjustment'];
			}

			if (isset($this->info['replay_gain']['track']['peak'])) {
				$this->info['replay_gain']['track']['max_noclip_gain'] = 0 - getid3_lib::RGADamplitude2dB($this->info['replay_gain']['track']['peak']);
			}
			if (isset($this->info['replay_gain']['album']['peak'])) {
				$this->info['replay_gain']['album']['max_noclip_gain'] = 0 - getid3_lib::RGADamplitude2dB($this->info['replay_gain']['album']['peak']);
			}
		}
		return true;
	}

	function ProcessAudioStreams() {
		if (!empty($this->info['audio']['bitrate']) || !empty($this->info['audio']['channels']) || !empty($this->info['audio']['sample_rate'])) {
			if (!isset($this->info['audio']['streams'])) {
				foreach ($this->info['audio'] as $key => $value) {
					if ($key != 'streams') {
						$this->info['audio']['streams'][0][$key] = $value;
					}
				}
			}
		}
		return true;
	}

	function getid3_tempnam() {
		return tempnam($this->tempdir, 'gI3');
	}

}

?>