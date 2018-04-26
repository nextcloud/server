<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author duritong <peter.meier+github@immerda.ch>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Phiber2000 <phiber2000@gmx.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Pulzer <t.pulzer@kniel.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Log;
use OC\SystemConfig;
use OCP\Log\IFileBased;
use OCP\Log\IWriter;
use OCP\ILogger;

/**
 * logging utilities
 *
 * Log is saved at data/nextcloud.log (on default)
 */

class File implements IWriter, IFileBased {
	/** @var string */
	protected $logFile;
	/** @var SystemConfig */
	private $config;

	public function __construct(string $path, string $fallbackPath = '', SystemConfig $config) {
		$this->logFile = $path;
		if (!file_exists($this->logFile)) {
			if(
				(
					!is_writable(dirname($this->logFile))
					|| !touch($this->logFile)
				)
				&& $fallbackPath !== ''
			) {
				$this->logFile = $fallbackPath;
			}
		}
		$this->config = $config;
	}

	/**
	 * write a message in the log
	 * @param string $app
	 * @param string|array $message
	 * @param int $level
	 */
	public function write(string $app, $message, int $level) {
		// default to ISO8601
		$format = $this->config->getValue('logdateformat', \DateTime::ATOM);
		$logTimeZone = $this->config->getValue('logtimezone', 'UTC');
		try {
			$timezone = new \DateTimeZone($logTimeZone);
		} catch (\Exception $e) {
			$timezone = new \DateTimeZone('UTC');
		}
		$time = \DateTime::createFromFormat("U.u", number_format(microtime(true), 4, ".", ""));
		if ($time === false) {
			$time = new \DateTime(null, $timezone);
		} else {
			// apply timezone if $time is created from UNIX timestamp
			$time->setTimezone($timezone);
		}
		$request = \OC::$server->getRequest();
		$reqId = $request->getId();
		$remoteAddr = $request->getRemoteAddress();
		// remove username/passwords from URLs before writing the to the log file
		$time = $time->format($format);
		$url = ($request->getRequestUri() !== '') ? $request->getRequestUri() : '--';
		$method = is_string($request->getMethod()) ? $request->getMethod() : '--';
		if($this->config->getValue('installed', false)) {
			$user = \OC_User::getUser() ? \OC_User::getUser() : '--';
		} else {
			$user = '--';
		}
		$userAgent = $request->getHeader('User-Agent');
		if ($userAgent === '') {
			$userAgent = '--';
		}
		$version = $this->config->getValue('version', '');
		$entry = compact(
			'reqId',
			'level',
			'time',
			'remoteAddr',
			'user',
			'app',
			'method',
			'url',
			'message',
			'userAgent',
			'version'
		);
		// PHP's json_encode only accept proper UTF-8 strings, loop over all
		// elements to ensure that they are properly UTF-8 compliant or convert
		// them manually.
		foreach($entry as $key => $value) {
			if(is_string($value)) {
				$testEncode = json_encode($value);
				if($testEncode === false) {
					$entry[$key] = utf8_encode($value);
				}
			}
		}
		$entry = json_encode($entry, JSON_PARTIAL_OUTPUT_ON_ERROR);
		$handle = @fopen($this->logFile, 'a');
		if ((fileperms($this->logFile) & 0777) != 0640) {
			@chmod($this->logFile, 0640);
		}
		if ($handle) {
			fwrite($handle, $entry."\n");
			fclose($handle);
		} else {
			// Fall back to error_log
			error_log($entry);
		}
		if (php_sapi_name() === 'cli-server') {
			error_log($message, 4);
		}
	}

	/**
	 * get entries from the log in reverse chronological order
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function getEntries(int $limit=50, int $offset=0):array {
		$minLevel = $this->config->getValue("loglevel", ILogger::WARN);
		$entries = array();
		$handle = @fopen($this->logFile, 'rb');
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
	public function getLogFilePath():string {
		return $this->logFile;
	}
}
