<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC;

use OC\Repair\AssetCache;
use OC\Repair\AvatarPermissions;
use OC\Repair\CleanTags;
use OC\Repair\Collation;
use OC\Repair\DropOldJobs;
use OC\Repair\OldGroupMembershipShares;
use OC\Repair\RemoveGetETagEntries;
use OC\Repair\RemoveOldShares;
use OC\Repair\RemoveRootShares;
use OC\Repair\SharePropagation;
use OC\Repair\SqliteAutoincrement;
use OC\Repair\DropOldTables;
use OC\Repair\FillETags;
use OC\Repair\InnoDB;
use OC\Repair\RepairLegacyStorages;
use OC\Repair\RepairMimeTypes;
use OC\Repair\SearchLuceneTables;
use OC\Repair\UpdateOutdatedOcsIds;
use OC\Repair\RepairInvalidShares;
use OC\Repair\RepairUnmergedShares;
use OCP\AppFramework\QueryException;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

class Repair implements IOutput{
	/* @var IRepairStep[] */
	private $repairSteps;
	/** @var EventDispatcher */
	private $dispatcher;
	/** @var string */
	private $currentStep;

	/**
	 * Creates a new repair step runner
	 *
	 * @param IRepairStep[] $repairSteps array of RepairStep instances
	 * @param EventDispatcher $dispatcher
	 */
	public function __construct($repairSteps = [], EventDispatcher $dispatcher = null) {
		$this->repairSteps = $repairSteps;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Run a series of repair steps for common problems
	 */
	public function run() {
		if (count($this->repairSteps) === 0) {
			$this->emit('\OC\Repair', 'info', array('No repair steps available'));
			return;
		}
		// run each repair step
		foreach ($this->repairSteps as $step) {
			$this->currentStep = $step->getName();
			$this->emit('\OC\Repair', 'step', [$this->currentStep]);
			$step->run($this);
		}
	}

	/**
	 * Add repair step
	 *
	 * @param IRepairStep|string $repairStep repair step
	 * @throws \Exception
	 */
	public function addStep($repairStep) {
		if (is_string($repairStep)) {
			try {
				$s = \OC::$server->query($repairStep);
			} catch (QueryException $e) {
				if (class_exists($repairStep)) {
					$s = new $repairStep();
				} else {
					throw new \Exception("Repair step '$repairStep' is unknown");
				}
			}

			if ($s instanceof IRepairStep) {
				$this->repairSteps[] = $s;
			} else {
				throw new \Exception("Repair step '$repairStep' is not of type \\OCP\\Migration\\IRepairStep");
			}
		} else {
			$this->repairSteps[] = $repairStep;
		}
	}

	/**
	 * Returns the default repair steps to be run on the
	 * command line or after an upgrade.
	 *
	 * @return IRepairStep[]
	 */
	public static function getRepairSteps() {
		return [
			new RepairMimeTypes(\OC::$server->getConfig()),
			new RepairLegacyStorages(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection()),
			new AssetCache(),
			new FillETags(\OC::$server->getDatabaseConnection()),
			new CleanTags(\OC::$server->getDatabaseConnection(), \OC::$server->getUserManager()),
			new DropOldTables(\OC::$server->getDatabaseConnection()),
			new DropOldJobs(\OC::$server->getJobList()),
			new RemoveGetETagEntries(\OC::$server->getDatabaseConnection()),
			new UpdateOutdatedOcsIds(\OC::$server->getConfig()),
			new RepairInvalidShares(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection()),
			new SharePropagation(\OC::$server->getConfig()),
			new RemoveOldShares(\OC::$server->getDatabaseConnection()),
			new AvatarPermissions(\OC::$server->getDatabaseConnection()),
			new RemoveRootShares(\OC::$server->getDatabaseConnection(), \OC::$server->getUserManager(), \OC::$server->getLazyRootFolder()),
			new RepairUnmergedShares(
				\OC::$server->getConfig(),
				\OC::$server->getDatabaseConnection(),
				\OC::$server->getUserManager(),
				\OC::$server->getGroupManager()
			),
		];
	}

	/**
	 * Returns expensive repair steps to be run on the
	 * command line with a special option.
	 *
	 * @return IRepairStep[]
	 */
	public static function getExpensiveRepairSteps() {
		return [
			new OldGroupMembershipShares(\OC::$server->getDatabaseConnection(), \OC::$server->getGroupManager()),
		];
	}

	/**
	 * Returns the repair steps to be run before an
	 * upgrade.
	 *
	 * @return IRepairStep[]
	 */
	public static function getBeforeUpgradeRepairSteps() {
		$connection = \OC::$server->getDatabaseConnection();
		$steps = [
			new InnoDB(),
			new Collation(\OC::$server->getConfig(), $connection),
			new SqliteAutoincrement($connection),
			new SearchLuceneTables(),
		];

		//There is no need to delete all previews on every single update
		//only 7.0.0 through 7.0.2 generated broken previews
		$currentVersion = \OC::$server->getConfig()->getSystemValue('version');
		if (version_compare($currentVersion, '7.0.0.0', '>=') &&
			version_compare($currentVersion, '7.0.3.4', '<=')) {
			$steps[] = new \OC\Repair\Preview();
		}

		return $steps;
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param array $arguments
	 */
	public function emit($scope, $method, array $arguments = []) {
		if (!is_null($this->dispatcher)) {
			$this->dispatcher->dispatch("$scope::$method",
				new GenericEvent("$scope::$method", $arguments));
		}
	}

	public function info($string) {
		// for now just emit as we did in the past
		$this->emit('\OC\Repair', 'info', array($string));
	}

	/**
	 * @param string $message
	 */
	public function warning($message) {
		// for now just emit as we did in the past
		$this->emit('\OC\Repair', 'warning', [$message]);
	}

	/**
	 * @param int $max
	 */
	public function startProgress($max = 0) {
		// for now just emit as we did in the past
		$this->emit('\OC\Repair', 'startProgress', [$max, $this->currentStep]);
	}

	/**
	 * @param int $step
	 * @param string $description
	 */
	public function advance($step = 1, $description = '') {
		// for now just emit as we did in the past
		$this->emit('\OC\Repair', 'advance', [$step, $description]);
	}

	/**
	 * @param int $max
	 */
	public function finishProgress() {
		// for now just emit as we did in the past
		$this->emit('\OC\Repair', 'finishProgress', []);
	}
}
