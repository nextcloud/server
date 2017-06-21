<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC;

use OC\App\AppStore\Bundles\BundleFetcher;
use OC\Repair\CleanTags;
use OC\Repair\Collation;
use OC\Repair\MoveUpdaterStepFile;
use OC\Repair\NC11\CleanPreviews;
use OC\Repair\NC11\FixMountStorages;
use OC\Repair\NC11\MoveAvatars;
use OC\Repair\NC12\InstallCoreBundle;
use OC\Repair\NC12\UpdateLanguageCodes;
use OC\Repair\OldGroupMembershipShares;
use OC\Repair\Owncloud\SaveAccountsTableData;
use OC\Repair\RemoveRootShares;
use OC\Repair\NC13\RepairInvalidPaths;
use OC\Repair\SqliteAutoincrement;
use OC\Repair\RepairMimeTypes;
use OC\Repair\RepairInvalidShares;
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
			new Collation(\OC::$server->getConfig(), \OC::$server->getLogger(), \OC::$server->getDatabaseConnection(), false),
			new RepairMimeTypes(\OC::$server->getConfig()),
			new CleanTags(\OC::$server->getDatabaseConnection(), \OC::$server->getUserManager()),
			new RepairInvalidShares(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection()),
			new RemoveRootShares(\OC::$server->getDatabaseConnection(), \OC::$server->getUserManager(), \OC::$server->getLazyRootFolder()),
			new MoveUpdaterStepFile(\OC::$server->getConfig()),
			new MoveAvatars(
				\OC::$server->getJobList(),
				\OC::$server->getConfig()
			),
			new CleanPreviews(
				\OC::$server->getJobList(),
				\OC::$server->getUserManager(),
				\OC::$server->getConfig()
			),
			new FixMountStorages(\OC::$server->getDatabaseConnection()),
			new UpdateLanguageCodes(\OC::$server->getDatabaseConnection(), \OC::$server->getConfig()),
			new InstallCoreBundle(
				\OC::$server->query(BundleFetcher::class),
				\OC::$server->getConfig(),
				\OC::$server->query(Installer::class)
			),
			new RepairInvalidPaths(\OC::$server->getDatabaseConnection())
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
			new OldGroupMembershipShares(\OC::$server->getDatabaseConnection(), \OC::$server->getGroupManager())
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
		$config = \OC::$server->getConfig();
		$steps = [
			new Collation(\OC::$server->getConfig(), \OC::$server->getLogger(), $connection, true),
			new SqliteAutoincrement($connection),
			new SaveAccountsTableData($connection, $config),
		];

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
