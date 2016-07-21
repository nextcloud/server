<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

/**
 * This rotates the current logfile to a new name, this way the total log usage
 * will stay limited and older entries are available for a while longer.
 * For more professional log management set the 'logfile' config to a different
 * location and manage that with your own tools.
 */
class Rotate extends \OC\BackgroundJob\Job {
	private $max_log_size;
	public function run($logFile) {
		$this->max_log_size = \OC::$server->getConfig()->getSystemValue('log_rotate_size', false);
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
		\OCP\Util::writeLog('OC\Log\Rotate', $msg, \OCP\Util::WARN);
	}
}
