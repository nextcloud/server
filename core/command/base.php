<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace OC\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Base extends Command {
	protected function configure() {
		$this
			->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, print or json, default is plain)',
				'plain'
			)
		;
	}

	protected function writeArrayInOutputFormat(InputInterface $input, OutputInterface $output, $items) {
		$outputFormat = $input->getOption('output');
		switch ($outputFormat) {
			case 'json':
			case 'print':
				if ($outputFormat === 'json') {
					$output->writeln(json_encode($items));
				} else {
					print_r($items);
				}
				break;
			default:
				foreach ($items as $key => $item) {
					$output->writeln(' - ' . (!is_int($key) ? $key . ': ' : '') . $item);
				}
				break;
		}
	}
}
