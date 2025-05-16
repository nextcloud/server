<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Integrity;

use OC\Core\Command\Base;
use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\AppLocator;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCP\App\IAppManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckApp
 *
 * @package OC\Core\Command\Integrity
 */
class CheckApp extends Base {
	public function __construct(
		private Checker $checker,
		private AppLocator $appLocator,
		private FileAccessHelper $fileAccessHelper,
		private IAppManager $appManager,
	) {
		parent::__construct();
	}

	/**
	 * {@inheritdoc }
	 */
	protected function configure() {
		parent::configure();
		$this
			->setName('integrity:check-app')
			->setDescription('Check integrity of an app using a signature.')
			->addArgument('appid', InputArgument::OPTIONAL, 'Application to check')
			->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path to application. If none is given it will be guessed.')
			->addOption('all', null, InputOption::VALUE_NONE, 'Check integrity of all apps.');
	}

	/**
	 * {@inheritdoc }
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('all') && $input->getArgument('appid')) {
			$output->writeln('<error>Option "--all" cannot be combined with an appid</error>');
			return 1;
		}

		if (!$input->getArgument('appid') && !$input->getOption('all')) {
			$output->writeln('<error>Please specify an appid, or "--all" to verify all apps</error>');
			return 1;
		}

		if ($input->getArgument('appid')) {
			$appIds = [$input->getArgument('appid')];
		} else {
			$appIds = $this->appManager->getAllAppsInAppsFolders();
		}

		$errorsFound = false;

		foreach ($appIds as $appId) {
			$path = (string)$input->getOption('path');
			if ($path === '') {
				$path = $this->appLocator->getAppPath($appId);
			}

			if ($this->appManager->isShipped($appId) || $this->fileAccessHelper->file_exists($path . '/appinfo/signature.json')) {
				// Only verify if the application explicitly ships a signature.json file
				$result = $this->checker->verifyAppSignature($appId, $path, true);

				if (count($result) > 0) {
					$output->writeln('<error>' . $appId . ': ' . count($result) . ' errors found:</error>');
					$this->writeArrayInOutputFormat($input, $output, $result);
					$errorsFound = true;
				}
			} else {
				$output->writeln('<comment>' . $appId . ': ' . 'App signature not found, skipping app integrity check</comment>');
			}
		}

		if (!$errorsFound) {
			$output->writeln('<info>No errors found</info>', OutputInterface::VERBOSITY_VERBOSE);
			return 0;
		}

		return 1;
	}
}
