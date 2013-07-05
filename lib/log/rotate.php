<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Log;

class Rotate extends \OC\BackgroundJob\Job {
	const LOG_SIZE_LIMIT = 104857600; // 100 MB
	public function run($logFile) {
		$filesize = filesize($logFile);
		if ($filesize >= self::LOG_SIZE_LIMIT) {
			$this->rotate($logFile);
		}
	}

	protected function rotate($logfile) {
		$rotated_logfile = $logfile.'.1';
		rename($logfile, $rotated_logfile);
		$msg = 'Log file "'.$logfile.'" was over 100MB, moved to "'.$rotated_logfile.'"';
		\OC_Log::write('OC\Log\Rotate', $msg, \OC_Log::WARN);
	}
}
