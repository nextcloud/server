<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteConfig extends Command {
	public function __construct(
		protected Helper $helper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:delete-config')
			->setDescription('deletes an existing LDAP configuration')
			->addArgument(
				'configID',
				InputArgument::REQUIRED,
				'the configuration ID'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configPrefix = $input->getArgument('configID');

		$success = $this->helper->deleteServerConfiguration($configPrefix);

		if (!$success) {
			$output->writeln("Cannot delete configuration with configID '{$configPrefix}'");
			return self::FAILURE;
		}

		$output->writeln("Deleted configuration with configID '{$configPrefix}'");
		return self::SUCCESS;
	}
}
