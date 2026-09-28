<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Encryption;

use OCP\Encryption\IManager;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetDefaultModule extends Command {
	public function __construct(
		protected IManager $encryptionManager,
		protected IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('encryption:set-default-module')
			->setDescription('Set the encryption default module')
			->addArgument(
				'module',
				InputArgument::REQUIRED,
				'ID of the encryption module that should be used'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$isMaintenanceModeEnabled = $this->config->getSystemValue('maintenance', false);
		if ($isMaintenanceModeEnabled) {
			$output->writeln('Maintenance mode must be disabled when setting default module,');
			$output->writeln('in order to load the relevant encryption modules correctly.');
			return 1;
		}

		$moduleId = $input->getArgument('module');

		if ($moduleId === $this->encryptionManager->getDefaultEncryptionModuleId()) {
			$output->writeln('"' . $moduleId . '"" is already the default module');
		} elseif ($this->encryptionManager->setDefaultEncryptionModule($moduleId)) {
			$output->writeln('<info>Set default module to "' . $moduleId . '"</info>');
		} else {
			$output->writeln('<error>The specified module "' . $moduleId . '" does not exist</error>');
			return 1;
		}
		return 0;
	}
}
