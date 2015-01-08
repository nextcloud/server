<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Robin Appelman icewind1991@gmail.com
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
 * logging utilities
 *
 * Log is saved at data/owncloud.log (on default)
 */

class OC_Log_Owncloud {
	static protected $logFile;

	/**
	 * Init class data
	 */
	public static function init() {
		$defaultLogFile = OC_Config::getValue("datadirectory", OC::$SERVERROOT.'/data').'/owncloud.log';
		self::$logFile = OC_Config::getValue("logfile", $defaultLogFile);

		/*
		* Fall back to default log file if specified logfile does not exist
		* and can not be created. Error suppression is required in order to
		* not end up in the error handler which will try to log the error.
		* A better solution (compared to error suppression) would be checking
		* !is_writable(dirname(self::$logFile)) before touch(), but
		* is_writable() on directories used to be pretty unreliable on Windows
		* for at least some time.
		*/
		if (!file_exists(self::$logFile) && !@touch(self::$logFile)) {
			self::$logFile = $defaultLogFile;
		}
	}

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int $level
	 */
	public static function write($app, $message, $level) {
		$minLevel=min(OC_Config::getValue( "loglevel", OC_Log::WARN ), OC_Log::ERROR);
		if($level>=$minLevel) {
			// default to ISO8601
			$format = OC_Config::getValue('logdateformat', 'c');
			$logtimezone=OC_Config::getValue( "logtimezone", 'UTC' );
			try {
				$timezone = new DateTimeZone($logtimezone);
			} catch (Exception $e) {
				$timezone = new DateTimeZone('UTC');
			}
			$time = new DateTime(null, $timezone);
			$reqId = \OC_Request::getRequestID();
			$remoteAddr = \OC_Request::getRemoteAddress();
			// remove username/passwords from URLs before writing the to the log file
			$time = $time->format($format);
			if($minLevel == OC_Log::DEBUG) {
				$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '--';
				$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '--';
				$entry = compact('reqId', 'remoteAddr', 'app', 'message', 'level', 'time', 'method', 'url');
			}
			else {
				$entry = compact('reqId', 'remoteAddr', 'app', 'message', 'level', 'time');
			}
			$entry = json_encode($entry);
			$handle = @fopen(self::$logFile, 'a');
			@chmod(self::$logFile, 0640);
			if ($handle) {
				fwrite($handle, $entry."\n");
				fclose($handle);
			} else {
				// Fall back to error_log
				error_log($entry);
			}
		}
	}

	/**
	 * get entries from the log in reverse chronological order
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public static function getEntries($limit=50, $offset=0) {
		self::init();
		$minLevel=OC_Config::getValue( "loglevel", OC_Log::WARN );
		$entries = array();
		$handle = @fopen(self::$logFile, 'rb');
		if ($handle) {
			fseek($handle, 0, SEEK_END);
			$pos = ftell($handle);
			$line = '';
			$entriesCount = 0;
			$lines = 0;
			// Loop through each character of the file looking for new lines
			while ($pos >= 0 && ($limit === null ||$entriesCount < $limit)) {
				fseek($handle, $pos);
				$ch = fgetc($handle);
				if ($ch == "\n" || $pos == 0) {
					if ($line != '') {
						// Add the first character if at the start of the file,
						// because it doesn't hit the else in the loop
						if ($pos == 0) {
							$line = $ch.$line;
						}
						$entry = json_decode($line);
						// Add the line as an entry if it is passed the offset and is equal or above the log level
						if ($entry->level >= $minLevel) {
							$lines++;
							if ($lines > $offset) {
								$entries[] = $entry;
								$entriesCount++;
							}
						}
						$line = '';
					}
				} else {
					$line = $ch.$line;
				}
				$pos--;
			}
			fclose($handle);
		}
		return $entries;
	}

	/**
	 * @return string
	 */
	public static function getLogFilePath() {
		return self::$logFile;
	}
}
