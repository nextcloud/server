<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Maintenance;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataFingerprint extends Command {
	public function __construct(
		protected IConfig $config,
		protected ITimeFactory $timeFactory,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('maintenance:data-fingerprint')
			->setDescription('update the systems data-fingerprint after a backup is restored');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$fingerPrint = md5($this->timeFactory->getTime());
		$this->config->setSystemValue('data-fingerprint', $fingerPrint);
		$output->writeln('<info>Updated data-fingerprint to ' . $fingerPrint . '</info>');
		return 0;
	}
}
