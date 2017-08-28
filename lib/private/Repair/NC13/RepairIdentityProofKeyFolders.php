<?php
/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
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


namespace OC\Repair\NC13;


use OC\Files\AppData\Factory;
use OCP\Files\IRootFolder;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RepairIdentityProofKeyFolders implements IRepairStep {

	/** @var IConfig */
	private $config;

	/** @var \OC\Files\AppData\AppData */
	private $appDataIdentityProof;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var string */
	private $identityProofDir;

	/**
	 * RepairIdentityProofKeyFolders constructor.
	 *
	 * @param IConfig $config
	 * @param Factory $appDataFactory
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(IConfig $config, Factory $appDataFactory, IRootFolder $rootFolder) {
		$this->config = $config;
		$this->appDataIdentityProof = $appDataFactory->get('identityproof');
		$this->rootFolder = $rootFolder;

		$instanceId = $this->config->getSystemValue('instanceid', null);
		if ($instanceId === null) {
			throw new \RuntimeException('no instance id!');
		}
		$this->identityProofDir = 'appdata_' . $instanceId . '/identityproof/';
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return "Rename folder with user specific keys";
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @param IOutput $output
	 * @throws \Exception in case of failure
	 * @since 9.1.0
	 */
	public function run(IOutput $output) {
		$versionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');
		if (version_compare($versionFromBeforeUpdate, '13.0.0.1', '<=')) {
			$count = $this->repair();
			$output->info('Repaired ' . $count . ' folders');
		}
	}

	/**
	 * rename all dirs with user specific keys to 'user-uid'
	 *
	 * @return int
	 */
	private function repair() {
		$count = 0;
		$dirListing = $this->appDataIdentityProof->getDirectoryListing();
		/** @var ISimpleFolder $folder */
		foreach ($dirListing as $folder) {
			$name = $folder->getName();
			$node = $this->rootFolder->get($this->identityProofDir . $name);
			$node->move($this->identityProofDir . 'user-' . $name);
			$count++;
		}

		return $count;
	}
}
