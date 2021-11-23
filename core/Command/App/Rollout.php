<?php
/**
 * @copyright Copyright (c) 2021, Felix Stupp <felix.stupp+github@banananet.work>
 *
 * @author Felix Stupp <felix.stupp+github@banananet.work>
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
namespace OC\Core\Command\App;

use OC\Installer;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Rollout extends Command {

	/** @var IAppManager */
	protected $appManager;

	/** @var int */
	protected $exitCode = 0;

	/**
	 * @param IAppManager $appManager
	 */
	public function __construct(IAppManager $appManager) {
		parent::__construct();
		$this->appManager = $appManager;
	}

	protected function configure(): void {
		$this
			->setName('app:rollout')
			->setDescription('rollout list of apps which should be either installed and enabled or disabled or removed')
			->addArgument(
				'app-ids',
				InputArgument::REQUIRED | InputArgument::IS_ARRAY,
				'list of apps; names with suffix "+" or no known suffix will be installed/enabled, while names with suffix "-" will be disabled, if required'
			)
			->addOption(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'install apps regardless of the Nextcloud version requirement'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appIds = $input->getArgument('app-ids');
		$forceEnable = (bool) $input->getOption('force');

		foreach ($appIds as $appIdMarked) {
			$appId = substr($appIdMarked, 0, -1);
			$marker = substr($appIdMarked, 1);
			if ($marker == "+") {
				$this->enableApp($appId, $forceEnable, $output);
			} elseif ($marker == "-") {
				$this->disableApp($appId, $output);
			} else {
				// ignore marker as no known found
				$this->enableApp($appIdMarked, $forceEnable, $output);
			}
		}

		return $this->exitCode;
	}

	/**
	 * @param string $appId
	 * @param OutputInterface $output
	 */
	private function disableApp(string $appId, OutputInterface $output) {
		if ($this->appManager->isInstalled($appId) === false) {
			$output->writeln('No such app enabled: ' . $appId);
			return;
		}

		try {
			$this->appManager->disableApp($appId);
			$appVersion = $this->appManager->getAppVersion($appId);
			$output->writeln($appId . ' ' . $appVersion . ' disabled');
		} catch (\Exception $e) {
			$output->writeln($e->getMessage());
			$this->exitCode = 1;
		}
	}

	/**
	 * @param string $appId
	 * @param bool $forceEnable
	 * @param OutputInterface $output
	 */
	private function enableApp(string $appId, bool $forceEnable, OutputInterface $output): void {
		if ($this->appManager->isInstalled($appId)) {
			$output->writeln($appId . ' already enabled');
			return;
		}

		try {
			/** @var Installer $installer */
			$installer = \OC::$server->query(Installer::class);

			if (false === $installer->isDownloaded($appId)) {
				$installer->downloadApp($appId);
			}

			$installer->installApp($appId, $forceEnable);
			$appVersion = $this->appManager->getAppVersion($appId);

			$this->appManager->enableApp($appId, $forceEnable);
			$output->writeln($appId . ' ' . $appVersion . ' enabled');
		} catch (AppPathNotFoundException $e) {
			$output->writeln($appId . ' not found');
			$this->exitCode = 1;
		} catch (\Exception $e) {
			$output->writeln($e->getMessage());
			$this->exitCode = 1;
		}
	}
}
