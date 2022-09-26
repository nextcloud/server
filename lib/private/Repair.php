<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OC;

use OCP\AppFramework\QueryException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Collaboration\Resources\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\Repair\AddBruteForceCleanupJob;
use OC\Repair\AddCleanupUpdaterBackupsJob;
use OC\Repair\CleanTags;
use OC\Repair\ClearFrontendCaches;
use OC\Repair\ClearGeneratedAvatarCache;
use OC\Repair\Collation;
use OC\Repair\Events\RepairAdvanceEvent;
use OC\Repair\Events\RepairErrorEvent;
use OC\Repair\Events\RepairFinishEvent;
use OC\Repair\Events\RepairInfoEvent;
use OC\Repair\Events\RepairStartEvent;
use OC\Repair\Events\RepairStepEvent;
use OC\Repair\Events\RepairWarningEvent;
use OC\Repair\MoveUpdaterStepFile;
use OC\Repair\NC11\FixMountStorages;
use OC\Repair\NC13\AddLogRotateJob;
use OC\Repair\NC14\AddPreviewBackgroundCleanupJob;
use OC\Repair\NC16\AddClenupLoginFlowV2BackgroundJob;
use OC\Repair\NC16\CleanupCardDAVPhotoCache;
use OC\Repair\NC16\ClearCollectionsAccessCache;
use OC\Repair\NC18\ResetGeneratedAvatarFlag;
use OC\Repair\NC20\EncryptionLegacyCipher;
use OC\Repair\NC20\EncryptionMigration;
use OC\Repair\NC20\ShippedDashboardEnable;
use OC\Repair\NC21\AddCheckForUserCertificatesJob;
use OC\Repair\NC21\ValidatePhoneNumber;
use OC\Repair\NC22\LookupServerSendCheck;
use OC\Repair\NC24\AddTokenCleanupJob;
use OC\Repair\OldGroupMembershipShares;
use OC\Repair\Owncloud\CleanPreviews;
use OC\Repair\Owncloud\DropAccountTermsTable;
use OC\Repair\Owncloud\MigrateOauthTables;
use OC\Repair\Owncloud\MoveAvatars;
use OC\Repair\Owncloud\SaveAccountsTableData;
use OC\Repair\Owncloud\UpdateLanguageCodes;
use OC\Repair\RemoveLinkShares;
use OC\Repair\RepairDavShares;
use OC\Repair\RepairInvalidShares;
use OC\Repair\RepairMimeTypes;
use OC\Repair\SqliteAutoincrement;
use OC\Template\JSCombiner;
use Psr\Log\LoggerInterface;
use Throwable;

class Repair implements IOutput {

	/** @var IRepairStep[] */
	private $repairSteps;

	private IEventDispatcher $dispatcher;

	/** @var string */
	private $currentStep;

	private LoggerInterface $logger;

	/**
	 * Creates a new repair step runner
	 *
	 * @param IRepairStep[] $repairSteps array of RepairStep instances
	 */
	public function __construct(array $repairSteps, IEventDispatcher $dispatcher, LoggerInterface $logger) {
		$this->repairSteps = $repairSteps;
		$this->dispatcher = $dispatcher;
		$this->logger = $logger;
	}

	/**
	 * Run a series of repair steps for common problems
	 */
	public function run() {
		if (count($this->repairSteps) === 0) {
			$this->dispatcher->dispatchTyped(new RepairInfoEvent('No repair steps available'));

			return;
		}
		// run each repair step
		foreach ($this->repairSteps as $step) {
			$this->currentStep = $step->getName();
			$this->dispatcher->dispatchTyped(new RepairStepEvent($this->currentStep));
			try {
				$step->run($this);
			} catch (\Exception $e) {
				$this->logger->error("Exception while executing repair step " . $step->getName(), ['exception' => $e]);
				$this->dispatcher->dispatchTyped(new RepairErrorEvent($e->getMessage()));
			}
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
				$s = \OC::$server->get($repairStep);
			} catch (QueryException $e) {
				if (class_exists($repairStep)) {
					try {
						// Last resort: hope there are no constructor arguments
						$s = new $repairStep();
					} catch (Throwable $inner) {
						// Well, it was worth a try
						throw new \Exception("Repair step '$repairStep' can't be instantiated: " . $e->getMessage(), 0, $e);
					}
				} else {
					throw new \Exception("Repair step '$repairStep' is unknown", 0, $e);
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
			new Collation(\OC::$server->getConfig(), \OC::$server->get(LoggerInterface::class), \OC::$server->getDatabaseConnection(), false),
			new RepairMimeTypes(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection()),
			new CleanTags(\OC::$server->getDatabaseConnection(), \OC::$server->getUserManager()),
			new RepairInvalidShares(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection()),
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
			new MigrateOauthTables(\OC::$server->get(Connection::class)),
			new FixMountStorages(\OC::$server->getDatabaseConnection()),
			new UpdateLanguageCodes(\OC::$server->getDatabaseConnection(), \OC::$server->getConfig()),
			new AddLogRotateJob(\OC::$server->getJobList()),
			new ClearFrontendCaches(\OC::$server->getMemCacheFactory(), \OC::$server->query(JSCombiner::class)),
			\OCP\Server::get(ClearGeneratedAvatarCache::class),
			new AddPreviewBackgroundCleanupJob(\OC::$server->getJobList()),
			new AddCleanupUpdaterBackupsJob(\OC::$server->getJobList()),
			new CleanupCardDAVPhotoCache(\OC::$server->getConfig(), \OC::$server->getAppDataDir('dav-photocache'), \OC::$server->get(LoggerInterface::class)),
			new AddClenupLoginFlowV2BackgroundJob(\OC::$server->getJobList()),
			new RemoveLinkShares(\OC::$server->getDatabaseConnection(), \OC::$server->getConfig(), \OC::$server->getGroupManager(), \OC::$server->getNotificationManager(), \OC::$server->query(ITimeFactory::class)),
			new ClearCollectionsAccessCache(\OC::$server->getConfig(), \OC::$server->query(IManager::class)),
			\OCP\Server::get(ResetGeneratedAvatarFlag::class),
			\OCP\Server::get(EncryptionLegacyCipher::class),
			\OCP\Server::get(EncryptionMigration::class),
			\OCP\Server::get(ShippedDashboardEnable::class),
			\OCP\Server::get(AddBruteForceCleanupJob::class),
			\OCP\Server::get(AddCheckForUserCertificatesJob::class),
			\OCP\Server::get(RepairDavShares::class),
			\OCP\Server::get(LookupServerSendCheck::class),
			\OCP\Server::get(AddTokenCleanupJob::class),
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
			\OC::$server->get(ValidatePhoneNumber::class),
		];
	}

	/**
	 * Returns the repair steps to be run before an
	 * upgrade.
	 *
	 * @return IRepairStep[]
	 */
	public static function getBeforeUpgradeRepairSteps() {
		/** @var Connection $connection */
		$connection = \OC::$server->get(Connection::class);
		/** @var ConnectionAdapter $connectionAdapter */
		$connectionAdapter = \OC::$server->get(ConnectionAdapter::class);
		$config = \OC::$server->getConfig();
		$steps = [
			new Collation(\OC::$server->getConfig(), \OC::$server->get(LoggerInterface::class), $connectionAdapter, true),
			new SqliteAutoincrement($connection),
			new SaveAccountsTableData($connectionAdapter, $config),
			new DropAccountTermsTable($connectionAdapter),
		];

		return $steps;
	}

	/**
	 * @param string $message
	 */
	public function info($message) {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairInfoEvent($message));
	}

	/**
	 * @param string $message
	 */
	public function warning($message) {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairWarningEvent($message));
	}

	/**
	 * @param int $max
	 */
	public function startProgress($max = 0) {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairStartEvent($max, $this->currentStep));
	}

	/**
	 * @param int $step number of step to advance
	 * @param string $description
	 */
	public function advance($step = 1, $description = '') {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairAdvanceEvent($step, $description));
	}

	/**
	 * @param int $max
	 */
	public function finishProgress() {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairFinishEvent());
	}
}
