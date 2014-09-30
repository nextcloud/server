<?php
/**
 * Copyright (c) 2014 Martin Konrad <info@martin-konrad.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\user_ldap\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \OCA\user_ldap\lib\Helper;

class DeleteConfig extends Command {

	protected function configure() {
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


	protected function execute(InputInterface $input, OutputInterface $output) {
		$configPrefix = $input->getArgument('configID');;

		$success = Helper::deleteServerConfiguration($configPrefix);

		if($success) {
			$output->writeln("Deleted configuration with configID '{$configPrefix}'");
		} else {
			$output->writeln("Cannot delete configuration with configID '{$configPrefix}'");
		}
	}
}
