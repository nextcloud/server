<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Maintenance;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateHtaccess extends Command {
	protected function configure() {
		$this
			->setName('maintenance:update:htaccess')
			->setDescription('Updates the .htaccess file');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if (\OC\Setup::updateHtaccess()) {
			$output->writeln('.htaccess has been updated');
			return 0;
		} else {
			$output->writeln('<error>Error updating .htaccess file, not enough permissions, not enough free space or "overwrite.cli.url" set to an invalid URL?</error>');
			return 1;
		}
	}
}
