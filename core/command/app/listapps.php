<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListApps extends Command {
	protected function configure() {
		$this
			->setName('app:list')
			->setDescription('List all available apps');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$apps = \OC_App::getAllApps();
		$enabledApps = array();
		$disabledApps = array();

		//sort enabled apps above disabled apps
		foreach ($apps as $app) {
			if (\OC_App::isEnabled($app)) {
				$enabledApps[] = $app;
			} else {
				$disabledApps[] = $app;
			}
		}

		sort($enabledApps);
		sort($disabledApps);
		$output->writeln('Enabled:');
		foreach ($enabledApps as $app) {
			$output->writeln(' - ' . $app);
		}
		$output->writeln('Disabled:');
		foreach ($disabledApps as $app) {
			$output->writeln(' - ' . $app);
		}
	}
}
