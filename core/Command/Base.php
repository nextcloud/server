<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
	const OUTPUT_FORMAT_PLAIN = 'plain';
	const OUTPUT_FORMAT_JSON = 'json';
	const OUTPUT_FORMAT_JSON_PRETTY = 'json_pretty';

	protected $defaultOutputFormat = self::OUTPUT_FORMAT_PLAIN;

	/** @var boolean */
	private $php_pcntl_signal = false;

	/** @var boolean */
	private $interrupted = false;

	protected function configure() {
		$this
			->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat
			)
		;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param array $items
	 * @param string $prefix
	 */
	protected function writeArrayInOutputFormat(InputInterface $input, OutputInterface $output, $items, $prefix = '  - ') {
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON:
				$output->writeln(json_encode($items));
				break;
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$output->writeln(json_encode($items, JSON_PRETTY_PRINT));
				break;
			default:
				foreach ($items as $key => $item) {
					if (is_array($item)) {
						$output->writeln($prefix . $key . ':');
						$this->writeArrayInOutputFormat($input, $output, $item, '  ' . $prefix);
						continue;
					}
					if (!is_int($key)) {
						$value = $this->valueToString($item);
						if (!is_null($value)) {
							$output->writeln($prefix . $key . ': ' . $value);
						} else {
							$output->writeln($prefix . $key);
						}
					} else {
						$output->writeln($prefix . $this->valueToString($item));
					}
				}
				break;
		}
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param mixed $item
	 */
	protected function writeMixedInOutputFormat(InputInterface $input, OutputInterface $output, $item) {
		if (is_array($item)) {
			$this->writeArrayInOutputFormat($input, $output, $item, '');
			return;
		}

		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON:
				$output->writeln(json_encode($item));
				break;
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$output->writeln(json_encode($item, JSON_PRETTY_PRINT));
				break;
			default:
				$output->writeln($this->valueToString($item, false));
				break;
		}
	}

	protected function valueToString($value, $returnNull = true) {
		if ($value === false) {
			return 'false';
		} else if ($value === true) {
			return 'true';
		} else if ($value === null) {
			return ($returnNull) ? null : 'null';
		} else {
			return $value;
		}
	}

	/**
	 * @return bool
	 */
	protected function hasBeenInterrupted() {
		// return always false if pcntl_signal functions are not accessible
		if ($this->php_pcntl_signal) {
			pcntl_signal_dispatch();
			return $this->interrupted;
		} else {
			return false;
		}
	}

	/**
	 * Changes the status of the command to "interrupted" if ctrl-c has been pressed
	 *
	 * Gives a chance to the command to properly terminate what it's doing
	 */
	protected function cancelOperation() {
		$this->interrupted = true;
	}

	public function run(InputInterface $input, OutputInterface $output) {
		// check if the php pcntl_signal functions are accessible
		$this->php_pcntl_signal = function_exists('pcntl_signal');
		if ($this->php_pcntl_signal) {
			// Collect interrupts and notify the running command
			pcntl_signal(SIGTERM, [$this, 'cancelOperation']);
			pcntl_signal(SIGINT, [$this, 'cancelOperation']);
		}

		return parent::run($input, $output);
	}
}
