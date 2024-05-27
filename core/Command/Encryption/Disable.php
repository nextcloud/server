<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Encryption;

use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Disable extends Command {
	public function __construct(
		protected IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('encryption:disable')
			->setDescription('Disable encryption')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($this->config->getAppValue('core', 'encryption_enabled', 'no') !== 'yes') {
			$output->writeln('Encryption is already disabled');
		} else {
			$this->config->setAppValue('core', 'encryption_enabled', 'no');
			$output->writeln('<info>Encryption disabled</info>');
		}
		return 0;
	}
}
