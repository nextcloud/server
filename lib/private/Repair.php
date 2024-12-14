<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\Repair\AddAppConfigLazyMigration;
use OC\Repair\AddBruteForceCleanupJob;
use OC\Repair\AddCleanupDeletedUsersBackgroundJob;
use OC\Repair\AddCleanupUpdaterBackupsJob;
use OC\Repair\AddMetadataGenerationJob;
use OC\Repair\AddRemoveOldTasksBackgroundJob;
use OC\Repair\CleanTags;
use OC\Repair\CleanUpAbandonedApps;
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
use OC\Repair\NC25\AddMissingSecretJob;
use OC\Repair\NC30\RemoveLegacyDatadirFile;
use OC\Repair\OldGroupMembershipShares;
use OC\Repair\Owncloud\CleanPreviews;
use OC\Repair\Owncloud\DropAccountTermsTable;
use OC\Repair\Owncloud\MigrateOauthTables;
use OC\Repair\Owncloud\MoveAvatars;
use OC\Repair\Owncloud\SaveAccountsTableData;
use OC\Repair\Owncloud\UpdateLanguageCodes;
use OC\Repair\RemoveBrokenProperties;
use OC\Repair\RemoveLinkShares;
use OC\Repair\RepairDavShares;
use OC\Repair\RepairInvalidShares;
use OC\Repair\RepairLogoDimension;
use OC\Repair\RepairMimeTypes;
use OC\Template\JSCombiner;
use OCA\DAV\Migration\DeleteSchedulingObjects;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Collaboration\Resources\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Notification\IManager as INotificationManager;
use Psr\Log\LoggerInterface;
use Throwable;

class Repair implements IOutput {
	/** @var IRepairStep[] */
	private array $repairSteps = [];

	private string $currentStep;

	public function __construct(
		private IEventDispatcher $dispatcher,
		private LoggerInterface $logger,
	) {
	}

	/** @param IRepairStep[] $repairSteps */
	public function setRepairSteps(array $repairSteps): void {
		$this->repairSteps = $repairSteps;
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
				$this->logger->error('Exception while executing repair step ' . $step->getName(), ['exception' => $e]);
				$this->dispatcher->dispatchTyped(new RepairErrorEvent($e->getMessage()));
			}
		}

		$this->repairSteps = [];
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
	public static function getRepairSteps(): array {
		return [
			new Collation(\OC::$server->getConfig(), \OC::$server->get(LoggerInterface::class), \OC::$server->getDatabaseConnection(), false),
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
			new UpdateLanguageCodes(\OC::$server->getDatabaseConnection(), \OC::$server->getConfig()),
			new AddLogRotateJob(\OC::$server->getJobList()),
			new ClearFrontendCaches(\OC::$server->getMemCacheFactory(), \OCP\Server::get(JSCombiner::class)),
			\OCP\Server::get(ClearGeneratedAvatarCache::class),
			new AddPreviewBackgroundCleanupJob(\OC::$server->getJobList()),
			new AddCleanupUpdaterBackupsJob(\OC::$server->getJobList()),
			new CleanupCardDAVPhotoCache(\OC::$server->getConfig(), \OC::$server->getAppDataDir('dav-photocache'), \OC::$server->get(LoggerInterface::class)),
			new AddClenupLoginFlowV2BackgroundJob(\OC::$server->getJobList()),
			new RemoveLinkShares(\OC::$server->getDatabaseConnection(), \OC::$server->getConfig(), \OC::$server->getGroupManager(), \OC::$server->get(INotificationManager::class), \OCP\Server::get(ITimeFactory::class)),
			new ClearCollectionsAccessCache(\OC::$server->getConfig(), \OCP\Server::get(IManager::class)),
			\OCP\Server::get(ResetGeneratedAvatarFlag::class),
			\OCP\Server::get(EncryptionLegacyCipher::class),
			\OCP\Server::get(EncryptionMigration::class),
			\OCP\Server::get(ShippedDashboardEnable::class),
			\OCP\Server::get(AddBruteForceCleanupJob::class),
			\OCP\Server::get(AddCheckForUserCertificatesJob::class),
			\OCP\Server::get(RepairDavShares::class),
			\OCP\Server::get(LookupServerSendCheck::class),
			\OCP\Server::get(AddTokenCleanupJob::class),
			\OCP\Server::get(CleanUpAbandonedApps::class),
			\OCP\Server::get(AddMissingSecretJob::class),
			\OCP\Server::get(AddRemoveOldTasksBackgroundJob::class),
			\OCP\Server::get(AddMetadataGenerationJob::class),
			\OCP\Server::get(AddAppConfigLazyMigration::class),
			\OCP\Server::get(RepairLogoDimension::class),
			\OCP\Server::get(RemoveLegacyDatadirFile::class),
			\OCP\Server::get(AddCleanupDeletedUsersBackgroundJob::class),
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
			new RemoveBrokenProperties(\OCP\Server::get(IDBConnection::class)),
			new RepairMimeTypes(
				\OCP\Server::get(IConfig::class),
				\OCP\Server::get(IAppConfig::class),
				\OCP\Server::get(IDBConnection::class)
			),
			\OC::$server->get(ValidatePhoneNumber::class),
			\OC::$server->get(DeleteSchedulingObjects::class),
		];
	}

	/**
	 * Returns the repair steps to be run before an
	 * upgrade.
	 *
	 * @return IRepairStep[]
	 */
	public static function getBeforeUpgradeRepairSteps() {
		/** @var ConnectionAdapter $connectionAdapter */
		$connectionAdapter = \OC::$server->get(ConnectionAdapter::class);
		$config = \OC::$server->getConfig();
		$steps = [
			new Collation(\OC::$server->getConfig(), \OC::$server->get(LoggerInterface::class), $connectionAdapter, true),
			new SaveAccountsTableData($connectionAdapter, $config),
			new DropAccountTermsTable($connectionAdapter),
		];

		return $steps;
	}

	public function debug(string $message): void {
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
