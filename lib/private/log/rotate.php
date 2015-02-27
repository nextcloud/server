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
 * will stay limited and older entries are available for a while longer.
 * For more professional log management set the 'logfile' config to a different
 * location and manage that with your own tools.
 */
class Rotate extends \OC\BackgroundJob\Job {
	private $max_log_size;
	public function run($logFile) {
		$this->max_log_size = \OC_Config::getValue('log_rotate_size', false);
		if ($this->max_log_size) {
			$filesize = @filesize($logFile);
			if ($filesize >= $this->max_log_size) {
				$this->rotate($logFile);
			}
		}
	}

	protected function rotate($logfile) {
		$rotatedLogfile = $logfile.'.1';
		rename($logfile, $rotatedLogfile);
		$msg = 'Log file "'.$logfile.'" was over '.$this->max_log_size.' bytes, moved to "'.$rotatedLogfile.'"';
		\OC_Log::write('OC\Log\Rotate', $msg, \OC_Log::WARN);
	}
}
