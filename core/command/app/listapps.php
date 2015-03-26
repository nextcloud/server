<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
		$versions = \OC_App::getAppVersions();

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
			$output->writeln(' - ' . $app . (isset($versions[$app]) ? ' (' . $versions[$app] . ')' : ''));
		}
		$output->writeln('Disabled:');
		foreach ($disabledApps as $app) {
			$output->writeln(' - ' . $app . (isset($versions[$app]) ? ' (' . $versions[$app] . ')' : ''));
		}
	}
}
