<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Repair;

use OC\Hooks\BasicEmitter;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MoveUpdaterStepFile extends BasicEmitter  implements \OC\RepairStep {

	/** @var \OCP\IConfig */
	protected $config;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct($config) {
		$this->config = $config;
	}

	public function getName() {
		return 'Move .step file of updater to backup location';
	}

	public function run() {

		$dataDir = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT);
		$instanceId = $this->config->getSystemValue('instanceid', null);

		if(!is_string($instanceId) || empty($instanceId)) {
			return;
		}

		$updaterFolderPath = $dataDir . '/updater-' . $instanceId;
		$stepFile = $updaterFolderPath . '/.step';
		if(file_exists($stepFile)) {
			$this->emit('\OC\Repair', 'info', array('.step file exists'));

			$previousStepFile = $updaterFolderPath . '/.step-previous-update';

			// cleanup
			if(file_exists($previousStepFile)) {
				if(\OC_Helper::rmdirr($previousStepFile)) {
					$this->emit('\OC\Repair', 'info', array('.step-previous-update removed'));
				} else {
					$this->emit('\OC\Repair', 'info', array('.step-previous-update can\'t be removed - abort move of .step file'));
					return;
				}
			}

			// move step file
			if(rename($stepFile, $previousStepFile)) {
				$this->emit('\OC\Repair', 'info', array('.step file moved to .step-previous-update'));
			} else {
				$this->emit('\OC\Repair', 'warning', array('.step file can\'t be moved'));
			}
		}
	}
}

