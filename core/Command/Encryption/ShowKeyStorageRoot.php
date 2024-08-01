<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Encryption;

use OC\Encryption\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowKeyStorageRoot extends Command {
	public function __construct(
		protected Util $util,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('encryption:show-key-storage-root')
			->setDescription('Show current key storage root');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$currentRoot = $this->util->getKeyStorageRoot();

		$rootDescription = $currentRoot !== '' ? $currentRoot : 'default storage location (data/)';

		$output->writeln("Current key storage root:  <info>$rootDescription</info>");
		return 0;
	}
}
