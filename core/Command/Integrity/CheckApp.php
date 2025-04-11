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
			->addArgument('appid', InputArgument::REQUIRED, 'Application to check')
			->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path to application. If none is given it will be guessed.');
	}

	/**
	 * {@inheritdoc }
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appid = $input->getArgument('appid');
		$path = (string)$input->getOption('path');
		if ($path === '') {
			$path = $this->appLocator->getAppPath($appid);
		}
		if ($this->appManager->isShipped($appid) || $this->fileAccessHelper->file_exists($path . '/appinfo/signature.json')) {
			// Only verify if the application explicitly ships a signature.json file
			$result = $this->checker->verifyAppSignature($appid, $path, true);
			$this->writeArrayInOutputFormat($input, $output, $result);
			if (count($result) > 0) {
				$output->writeln('<error>' . count($result) . ' errors found</error>', OutputInterface::VERBOSITY_VERBOSE);
				return 1;
			}
			$output->writeln('<info>No errors found</info>', OutputInterface::VERBOSITY_VERBOSE);
		} else {
			$output->writeln('<comment>App signature not found, skipping app integrity check</comment>');
		}
		return 0;
	}
}
