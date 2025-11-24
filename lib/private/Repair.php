<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

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
use OCA\DAV\Migration\DeleteSchedulingObjects;
use OCA\DAV\Migration\RemoveObjectProperties;
use OCA\Files_Sharing\Repair\CleanupShareTarget;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Repair implements IOutput {
	/** @var list<IRepairStep> */
	private array $repairSteps = [];

	private string $currentStep;

	public function __construct(
		private readonly IEventDispatcher $dispatcher,
		private readonly LoggerInterface $logger,
	) {
	}

	/** @param list<IRepairStep> $repairSteps */
	public function setRepairSteps(array $repairSteps): void {
		$this->repairSteps = $repairSteps;
	}

	/**
	 * Run a series of repair steps for common problems
	 */
	public function run(): void {
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
	 * @param IRepairStep|class-string<IRepairStep> $repairStep repair step
	 * @throws \Exception
	 */
	public function addStep(IRepairStep|string $repairStep): void {
		if (is_string($repairStep)) {
			try {
				$s = Server::get($repairStep);
			} catch (ContainerExceptionInterface $e) {
				if (class_exists($repairStep)) {
					try {
						// Last resort: hope there are no constructor arguments
						$s = new $repairStep();
					} catch (Throwable) {
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
	 * @return list<IRepairStep>
	 */
	public static function getRepairSteps(): array {
		return [
			new Collation(Server::get(IConfig::class), Server::get(LoggerInterface::class), Server::get(IDBConnection::class), false),
			Server::get(CleanTags::class),
			Server::get(RepairInvalidShares::class),
			Server::get(MoveUpdaterStepFile::class),
			Server::get(MoveAvatars::class),
			Server::get(CleanPreviews::class),
			Server::get(MigratePropertiesTable::class),
			Server::get(MigrateOauthTables::class),
			Server::get(UpdateLanguageCodes::class),
			Server::get(AddLogRotateJob::class),
			Server::get(ClearFrontendCaches::class),
			Server::get(ClearGeneratedAvatarCache::class),
			Server::get(AddPreviewBackgroundCleanupJob::class),
			Server::get(AddCleanupUpdaterBackupsJob::class),
			Server::get(CleanupCardDAVPhotoCache::class),
			Server::get(AddClenupLoginFlowV2BackgroundJob::class),
			Server::get(RemoveLinkShares::class),
			Server::get(ClearCollectionsAccessCache::class),
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
	 * @return list<IRepairStep>
	 */
	public static function getExpensiveRepairSteps(): array {
		return [
			Server::get(OldGroupMembershipShares::class),
			Server::get(RemoveBrokenProperties::class),
			Server::get(RepairMimeTypes::class),
			Server::get(DeleteSchedulingObjects::class),
			Server::get(RemoveObjectProperties::class),
			Server::get(CleanupShareTarget::class),
		];
	}

	/**
	 * Returns the repair steps to be run before an
	 * upgrade.
	 *
	 * @return list<IRepairStep>
	 */
	public static function getBeforeUpgradeRepairSteps(): array {
		return [
			new Collation(Server::get(IConfig::class), Server::get(LoggerInterface::class), Server::get(IDBConnection::class), true),
			Server::get(SaveAccountsTableData::class),
			Server::get(DropAccountTermsTable::class),
		];
	}

	public function debug(string $message): void {
	}

	/**
	 * @param string $message
	 */
	public function info($message): void {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairInfoEvent($message));
	}

	/**
	 * @param string $message
	 */
	public function warning($message): void {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairWarningEvent($message));
	}

	/**
	 * @param int $max
	 */
	public function startProgress($max = 0): void {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairStartEvent($max, $this->currentStep));
	}

	/**
	 * @param int $step number of step to advance
	 * @param string $description
	 */
	public function advance($step = 1, $description = ''): void {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairAdvanceEvent($step, $description));
	}

	public function finishProgress(): void {
		// for now just emit as we did in the past
		$this->dispatcher->dispatchTyped(new RepairFinishEvent());
	}
}
