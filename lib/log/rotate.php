<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Log;

/**
 * This rotates the current logfile to a new name, this way the total log usage
 * will stay limited and older entries are available for a while longer. The
 * total disk usage is twice LOG_SIZE_LIMIT.
 * For more professional log management set the 'logfile' config to a different
 * location and manage that with your own tools.
 */
class Rotate extends \OC\BackgroundJob\Job {
	const LOG_SIZE_LIMIT = 104857600; // 100 MiB
	public function run($logFile) {
		$filesize = @filesize($logFile);
		if ($filesize >= self::LOG_SIZE_LIMIT) {
			$this->rotate($logFile);
		}
	}

	protected function rotate($logfile) {
		$rotatedLogfile = $logfile.'.1';
		rename($logfile, $rotatedLogfile);
		$msg = 'Log file "'.$logfile.'" was over 100MB, moved to "'.$rotatedLogfile.'"';
		\OC_Log::write('OC\Log\Rotate', $msg, \OC_Log::WARN);
	}
}
