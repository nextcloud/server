<?php
/**
 * @copyright 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV\Repair;

class RepairStepFactory {
	/**
	 * @var IRepairStep[]
	 */
	private array $repairSteps = [];

	/**
	 * @return IRepairStep[]
	 */
	public function getRepairSteps(): array {
		return $this->repairSteps;
	}

	public function addRepairStep(IRepairStep $repairStep): self {
		$this->repairSteps[] = $repairStep;
		return $this;
	}

	public function registerRepairStep(string $repairStep): self {
		$this->addRepairStep(new $repairStep);
		return $this;
	}
}
