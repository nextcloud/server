<?php
/**
 * Copyright (c) 2013 Owen Winkler <ringmaster@midnightcircus.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Upgrade extends Command {
	protected function configure() {
		$this
			->setName('upgrade')
			->setDescription('run upgrade routines')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		include \OC::$SERVERROOT . '/upgrade.php';
	}
}
