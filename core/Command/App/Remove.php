<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Patrik Kernstock <info@pkern.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Patrik Kernstock <info@pkern.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Command\App;

use OC\Installer;
use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Remove extends Command implements CompletionAwareInterface {
	public function __construct(
		protected IAppManager $manager,
		private Installer $installer,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('app:remove')
			->setDescription('remove an app')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'remove the specified app'
			)
			->addOption(
				'keep-data',
				null,
				InputOption::VALUE_NONE,
				'keep app data and do not remove them'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appId = $input->getArgument('app-id');

		// Check if the app is installed
		if (!$this->manager->isInstalled($appId)) {
			$output->writeln($appId . ' is not installed');
			return 1;
		}

		// Removing shipped apps is not possible, therefore we pre-check that
		// before trying to remove it
		if ($this->manager->isShipped($appId)) {
			$output->writeln($appId . ' could not be removed as it is a shipped app');
			return 1;
		}

		// If we want to keep the data of the app, we simply don't disable it here.
		// App uninstall tasks are being executed when disabled. More info: PR #11627.
		if (!$input->getOption('keep-data')) {
			try {
				$this->manager->disableApp($appId);
				$output->writeln($appId . ' disabled');
			} catch (Throwable $e) {
				$output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
				$this->logger->error($e->getMessage(), [
					'app' => 'CLI',
					'exception' => $e,
				]);
				return 1;
			}
		}

		// Let's try to remove the app...
		try {
			$result = $this->installer->removeApp($appId);
		} catch (Throwable $e) {
			$output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
			$this->logger->error($e->getMessage(), [
				'app' => 'CLI',
				'exception' => $e,
			]);
			return 1;
		}

		if ($result === false) {
			$output->writeln($appId . ' could not be removed');
			return 1;
		}

		$appVersion = $this->manager->getAppVersion($appId);
		$output->writeln($appId . ' ' . $appVersion . ' removed');

		return 0;
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeOptionValues($optionName, CompletionContext $context): array {
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context): array {
		if ($argumentName === 'app-id') {
			return $this->manager->getInstalledApps();
		}
		return [];
	}
}
