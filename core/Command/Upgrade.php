<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command;

use OC\Console\TimestampFormatter;
use OC\DB\MigratorExecuteSqlEvent;
use OC\Repair\Events\RepairAdvanceEvent;
use OC\Repair\Events\RepairErrorEvent;
use OC\Repair\Events\RepairFinishEvent;
use OC\Repair\Events\RepairInfoEvent;
use OC\Repair\Events\RepairStartEvent;
use OC\Repair\Events\RepairStepEvent;
use OC\Repair\Events\RepairWarningEvent;
use OC\Updater;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Upgrade extends Command {
	public const ERROR_SUCCESS = 0;
	public const ERROR_NOT_INSTALLED = 1;
	public const ERROR_MAINTENANCE_MODE = 2;
	public const ERROR_UP_TO_DATE = 0;
	public const ERROR_INVALID_ARGUMENTS = 4;
	public const ERROR_FAILURE = 5;

	public function __construct(
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('upgrade')
			->setDescription('run upgrade routines after installation of a new release. The release has to be installed before.');
	}

	/**
	 * Execute the upgrade command
	 *
	 * @param InputInterface $input input interface
	 * @param OutputInterface $output output interface
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if (Util::needUpgrade()) {
			if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
				// Prepend each line with a little timestamp
				$timestampFormatter = new TimestampFormatter($this->config, $output->getFormatter());
				$output->setFormatter($timestampFormatter);
			}

			$self = $this;
			$updater = Server::get(Updater::class);
			$incompatibleOverwrites = $this->config->getSystemValue('app_install_overwrite', []);

			/** @var IEventDispatcher $dispatcher */
			$dispatcher = Server::get(IEventDispatcher::class);
			$progress = new ProgressBar($output);
			$progress->setFormat(" %message%\n %current%/%max% [%bar%] %percent:3s%%");
			$listener = function (MigratorExecuteSqlEvent $event) use ($progress, $output): void {
				$message = $event->getSql();
				if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
					$output->writeln(' Executing SQL ' . $message);
				} else {
					if (strlen($message) > 60) {
						$message = substr($message, 0, 57) . '...';
					}
					$progress->setMessage($message);
					if ($event->getCurrentStep() === 1) {
						$output->writeln('');
						$progress->start($event->getMaxStep());
					}
					$progress->setProgress($event->getCurrentStep());
					if ($event->getCurrentStep() === $event->getMaxStep()) {
						$progress->setMessage('Done');
						$progress->finish();
						$output->writeln('');
					}
				}
			};
			$repairListener = function (Event $event) use ($progress, $output): void {
				if ($event instanceof RepairStartEvent) {
					$progress->setMessage('Starting ...');
					$output->writeln($event->getCurrentStepName());
					$output->writeln('');
					$progress->start($event->getMaxStep());
				} elseif ($event instanceof RepairAdvanceEvent) {
					$desc = $event->getDescription();
					if (!empty($desc)) {
						$progress->setMessage($desc);
					}
					$progress->advance($event->getIncrement());
				} elseif ($event instanceof RepairFinishEvent) {
					$progress->setMessage('Done');
					$progress->finish();
					$output->writeln('');
				} elseif ($event instanceof RepairStepEvent) {
					if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
						$output->writeln('<info>Repair step: ' . $event->getStepName() . '</info>');
					}
				} elseif ($event instanceof RepairInfoEvent) {
					if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
						$output->writeln('<info>Repair info: ' . $event->getMessage() . '</info>');
					}
				} elseif ($event instanceof RepairWarningEvent) {
					$output->writeln('<error>Repair warning: ' . $event->getMessage() . '</error>');
				} elseif ($event instanceof RepairErrorEvent) {
					$output->writeln('<error>Repair error: ' . $event->getMessage() . '</error>');
				}
			};

			$dispatcher->addListener(MigratorExecuteSqlEvent::class, $listener);
			$dispatcher->addListener(RepairStartEvent::class, $repairListener);
			$dispatcher->addListener(RepairAdvanceEvent::class, $repairListener);
			$dispatcher->addListener(RepairFinishEvent::class, $repairListener);
			$dispatcher->addListener(RepairStepEvent::class, $repairListener);
			$dispatcher->addListener(RepairInfoEvent::class, $repairListener);
			$dispatcher->addListener(RepairWarningEvent::class, $repairListener);
			$dispatcher->addListener(RepairErrorEvent::class, $repairListener);


			$updater->listen('\OC\Updater', 'maintenanceEnabled', function () use ($output): void {
				$output->writeln('<info>Turned on maintenance mode</info>');
			});
			$updater->listen('\OC\Updater', 'maintenanceDisabled', function () use ($output): void {
				$output->writeln('<info>Turned off maintenance mode</info>');
			});
			$updater->listen('\OC\Updater', 'maintenanceActive', function () use ($output): void {
				$output->writeln('<info>Maintenance mode is kept active</info>');
			});
			$updater->listen('\OC\Updater', 'updateEnd',
				function ($success) use ($output, $self): void {
					if ($success) {
						$message = '<info>Update successful</info>';
					} else {
						$message = '<error>Update failed</error>';
					}
					$output->writeln($message);
				});
			$updater->listen('\OC\Updater', 'dbUpgradeBefore', function () use ($output): void {
				$output->writeln('<info>Updating database schema</info>');
			});
			$updater->listen('\OC\Updater', 'dbUpgrade', function () use ($output): void {
				$output->writeln('<info>Updated database</info>');
			});
			$updater->listen('\OC\Updater', 'incompatibleAppDisabled', function ($app) use ($output, &$incompatibleOverwrites): void {
				if (!in_array($app, $incompatibleOverwrites)) {
					$output->writeln('<comment>Disabled incompatible app: ' . $app . '</comment>');
				}
			});
			$updater->listen('\OC\Updater', 'upgradeAppStoreApp', function ($app) use ($output): void {
				$output->writeln('<info>Update app ' . $app . ' from App Store</info>');
			});
			$updater->listen('\OC\Updater', 'appSimulateUpdate', function ($app) use ($output): void {
				$output->writeln("<info>Checking whether the database schema for <$app> can be updated (this can take a long time depending on the database size)</info>");
			});
			$updater->listen('\OC\Updater', 'appUpgradeStarted', function ($app, $version) use ($output): void {
				$output->writeln("<info>Updating <$app> ...</info>");
			});
			$updater->listen('\OC\Updater', 'appUpgrade', function ($app, $version) use ($output): void {
				$output->writeln("<info>Updated <$app> to $version</info>");
			});
			$updater->listen('\OC\Updater', 'failure', function ($message) use ($output, $self): void {
				$output->writeln("<error>$message</error>");
			});
			$updater->listen('\OC\Updater', 'setDebugLogLevel', function ($logLevel, $logLevelName) use ($output): void {
				$output->writeln('<info>Setting log level to debug</info>');
			});
			$updater->listen('\OC\Updater', 'resetLogLevel', function ($logLevel, $logLevelName) use ($output): void {
				$output->writeln('<info>Resetting log level</info>');
			});
			$updater->listen('\OC\Updater', 'startCheckCodeIntegrity', function () use ($output): void {
				$output->writeln('<info>Starting code integrity check...</info>');
			});
			$updater->listen('\OC\Updater', 'finishedCheckCodeIntegrity', function () use ($output): void {
				$output->writeln('<info>Finished code integrity check</info>');
			});

			$success = $updater->upgrade();

			$this->postUpgradeCheck($input, $output);

			if (!$success) {
				return self::ERROR_FAILURE;
			}

			return self::ERROR_SUCCESS;
		} elseif ($this->config->getSystemValueBool('maintenance')) {
			//Possible scenario: Nextcloud core is updated but an app failed
			$output->writeln('<comment>Nextcloud is in maintenance mode</comment>');
			$output->write('<comment>Maybe an upgrade is already in process. Please check the '
				. 'logfile (data/nextcloud.log). If you want to re-run the '
				. 'upgrade procedure, remove the "maintenance mode" from '
				. 'config.php and call this script again.</comment>', true);
			return self::ERROR_MAINTENANCE_MODE;
		} else {
			$output->writeln('<info>No upgrade required.</info>');
			$output->writeln('');
			$output->writeln('Note: This command triggers the upgrade actions associated with a new version. The new version\'s updated source files must be deployed in advance.');
			$doc = $this->urlGenerator->linkToDocs('admin-update');
			$output->writeln('See the upgrade documentation: ' . $doc . ' for more information.');
			return self::ERROR_UP_TO_DATE;
		}
	}

	/**
	 * Perform a post upgrade check (specific to the command line tool)
	 *
	 * @param InputInterface $input input interface
	 * @param OutputInterface $output output interface
	 */
	protected function postUpgradeCheck(InputInterface $input, OutputInterface $output) {
		$trustedDomains = $this->config->getSystemValue('trusted_domains', []);
		if (empty($trustedDomains)) {
			$output->write(
				'<warning>The setting "trusted_domains" could not be '
				. 'set automatically by the upgrade script, '
				. 'please set it manually</warning>'
			);
		}
	}
}
