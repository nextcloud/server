<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OC\DB\ConnectionAdapter;
use OC\Repair\AddBruteForceCleanupJob;
use OC\Repair\AddCleanupDeletedUsersBackgroundJob;
use OC\Repair\AddCleanupUpdaterBackupsJob;
use OC\Repair\AddMetadataGenerationJob;
use OC\Repair\AddMovePreviewJob;
use OC\Repair\AddRemoveOldTasksBackgroundJob;
use OC\Repair\CleanTags;
use OC\Repair\CleanUpAbandonedApps;
use OC\Repair\ClearFrontendCaches;
use OC\Repair\ClearGeneratedAvatarCache;
use OC\Repair\Collation;
use OC\Repair\ConfigKeyMigration;
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
use OC\Repair\NC22\LookupServerSendCheck;
use OC\Repair\NC24\AddTokenCleanupJob;
use OC\Repair\NC25\AddMissingSecretJob;
use OC\Repair\NC29\SanitizeAccountProperties;
use OC\Repair\NC30\RemoveLegacyDatadirFile;
use OC\Repair\OldGroupMembershipShares;
use OC\Repair\Owncloud\CleanPreviews;
use OC\Repair\Owncloud\DropAccountTermsTable;
use OC\Repair\Owncloud\MigrateOauthTables;
use OC\Repair\Owncloud\MigratePropertiesTable;
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
use OCA\DAV\Migration\RemoveObjectProperties;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Collaboration\Resources\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\AppData\IAppDataFactory;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Notification\IManager as INotificationManager;
use OCP\Server;
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
				$s = Server::get($repairStep);
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
			new Collation(Server::get(IConfig::class), Server::get(LoggerInterface::class), Server::get(IDBConnection::class), false),
			new CleanTags(Server::get(IDBConnection::class), Server::get(IUserManager::class)),
			new RepairInvalidShares(Server::get(IConfig::class), Server::get(IDBConnection::class)),
			new MoveUpdaterStepFile(Server::get(IConfig::class)),
			new MoveAvatars(
				Server::get(IJobList::class),
				Server::get(IConfig::class)
			),
			new CleanPreviews(
				Server::get(IJobList::class),
				Server::get(IUserManager::class),
				Server::get(IConfig::class)
			),
			Server::get(MigratePropertiesTable::class),
			Server::get(MigrateOauthTables::class),
			new UpdateLanguageCodes(Server::get(IDBConnection::class), Server::get(IConfig::class)),
			new AddLogRotateJob(Server::get(IJobList::class)),
			new ClearFrontendCaches(Server::get(ICacheFactory::class), Server::get(JSCombiner::class)),
			Server::get(ClearGeneratedAvatarCache::class),
			new AddPreviewBackgroundCleanupJob(Server::get(IJobList::class)),
			new AddCleanupUpdaterBackupsJob(Server::get(IJobList::class)),
			new CleanupCardDAVPhotoCache(Server::get(IConfig::class), Server::get(IAppDataFactory::class), Server::get(LoggerInterface::class)),
			new AddClenupLoginFlowV2BackgroundJob(Server::get(IJobList::class)),
			new RemoveLinkShares(Server::get(IDBConnection::class), Server::get(IConfig::class), Server::get(IGroupManager::class), Server::get(INotificationManager::class), Server::get(ITimeFactory::class)),
			new ClearCollectionsAccessCache(Server::get(IConfig::class), Server::get(IManager::class)),
			Server::get(ResetGeneratedAvatarFlag::class),
			Server::get(EncryptionLegacyCipher::class),
			Server::get(EncryptionMigration::class),
			Server::get(ShippedDashboardEnable::class),
			Server::get(AddBruteForceCleanupJob::class),
			Server::get(AddCheckForUserCertificatesJob::class),
			Server::get(RepairDavShares::class),
			Server::get(LookupServerSendCheck::class),
			Server::get(AddTokenCleanupJob::class),
			Server::get(CleanUpAbandonedApps::class),
			Server::get(AddMissingSecretJob::class),
			Server::get(AddRemoveOldTasksBackgroundJob::class),
			Server::get(AddMetadataGenerationJob::class),
			Server::get(RepairLogoDimension::class),
			Server::get(RemoveLegacyDatadirFile::class),
			Server::get(AddCleanupDeletedUsersBackgroundJob::class),
			Server::get(SanitizeAccountProperties::class),
			Server::get(AddMovePreviewJob::class),
			Server::get(ConfigKeyMigration::class),
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
			new OldGroupMembershipShares(Server::get(IDBConnection::class), Server::get(IGroupManager::class)),
			new RemoveBrokenProperties(Server::get(IDBConnection::class)),
			new RepairMimeTypes(
				Server::get(IConfig::class),
				Server::get(IAppConfig::class),
				Server::get(IDBConnection::class)
			),
			Server::get(DeleteSchedulingObjects::class),
			Server::get(RemoveObjectProperties::class),
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
		$connectionAdapter = Server::get(ConnectionAdapter::class);
		$config = Server::get(IConfig::class);
		$steps = [
			new Collation(Server::get(IConfig::class), Server::get(LoggerInterface::class), $connectionAdapter, true),
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
