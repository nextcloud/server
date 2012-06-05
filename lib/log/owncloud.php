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
		$datadir=OC_Config::getValue( "datadirectory", OC::$SERVERROOT.'/data' );
		self::$logFile=OC_Config::getValue( "logfile", $datadir.'/owncloud.log' );
	}

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string $message
	 * @param int level
	 */
	public static function write($app, $message, $level) {
		$minLevel=min(OC_Config::getValue( "loglevel", OC_Log::WARN ),OC_Log::ERROR);
		if($level>=$minLevel){
			$entry=array('app'=>$app, 'message'=>$message, 'level'=>$level,'time'=>time());
			$fh=fopen(self::$logFile, 'a');
			fwrite($fh, json_encode($entry)."\n");
			fclose($fh);
		}
	}

	/**
	 * get entries from the log in reverse chronological order
	 * @param int limit
	 * @param int offset
	 * @return array
	 */
	public static function getEntries($limit=50, $offset=0){
		self::init();
		$minLevel=OC_Config::getValue( "loglevel", OC_Log::WARN );
		$entries = array();
		$handle = @fopen(self::$logFile, 'r');
		if ($handle) {
			// Just a guess to set the file pointer to the right spot
			$maxLineLength = 150;
			fseek($handle, -($limit * $maxLineLength + $offset * $maxLineLength), SEEK_END);
			// Skip first line, because it is most likely a partial line
			fgets($handle);
			while (!feof($handle)) {
				$line = fgets($handle);
				if (!empty($line)) {
					$entry = json_decode($line);
					if ($entry->level >= $minLevel) {
						$entries[] = $entry;
					}
				}
			}
			fclose($handle);
			// Extract the needed entries and reverse the order
			$entries = array_reverse(array_slice($entries, -($limit + $offset), $limit));
		}
		return $entries;
	}
}
