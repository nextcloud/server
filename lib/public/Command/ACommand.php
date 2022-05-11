<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\Command;

use OC\Core\Command\InterruptedException;
use OC\Core\Command\User\ListCommand;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 25.0.0
 */
abstract class ACommand extends Command implements CompletionAwareInterface {
	/**
	 * @since 25.0.0
	 */
	public const OUTPUT_FORMAT_PLAIN = 'plain';
	/**
	 * @since 25.0.0
	 */
	public const OUTPUT_FORMAT_JSON = 'json';
	/**
	 * @since 25.0.0
	 */
	public const OUTPUT_FORMAT_JSON_PRETTY = 'json_pretty';

	protected bool $phpPcntlSignal = false;
	protected bool $interrupted = false;

	/**
	 * @since 25.0.0
	 */
	protected function configure(): void {
		parent::configure();
		$this
			->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				self::OUTPUT_FORMAT_PLAIN
			)
		;
	}

	/**
	 * @since 25.0.0
	 */
	protected function writeArrayInOutputFormat(InputInterface $input, OutputInterface $output, array $items, string $prefix = '  - '): void {
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
					if (!is_int($key) || ListCommand::class === get_class($this)) {
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
	 * @since 25.0.0
	 */
	protected function writeMixedInOutputFormat(InputInterface $input, OutputInterface $output, $item): void {
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

	/**
	 * @param mixed $value
	 * @param bool $returnNull
	 * @return ?string
	 * @since 25.0.0
	 */
	protected function valueToString($value, bool $returnNull = true): ?string {
		if ($value === false) {
			return 'false';
		} elseif ($value === true) {
			return 'true';
		} elseif ($value === null) {
			return $returnNull ? null : 'null';
		} else {
			return $value;
		}
	}

	/**
	 * Throw InterruptedException when interrupted by user
	 *
	 * @throws InterruptedException
	 * @since 25.0.0
	 */
	protected function abortIfInterrupted(): void {
		if ($this->phpPcntlSignal === false) {
			return;
		}

		pcntl_signal_dispatch();

		if ($this->interrupted === true) {
			throw new InterruptedException('Command interrupted by user');
		}
	}

	/**
	 * Changes the status of the command to "interrupted" if ctrl-c has been pressed
	 *
	 * Gives a chance to the command to properly terminate what it's doing
	 * @since 25.0.0
	 */
	protected function cancelOperation(): void {
		$this->interrupted = true;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws \Exception
	 * @since 25.0.0
	 */
	public function run(InputInterface $input, OutputInterface $output): int {
		// check if the php pcntl_signal functions are accessible
		$this->phpPcntlSignal = function_exists('pcntl_signal');
		if ($this->phpPcntlSignal) {
			// Collect interrupts and notify the running command
			pcntl_signal(SIGTERM, [$this, 'cancelOperation']);
			pcntl_signal(SIGINT, [$this, 'cancelOperation']);
		}

		return parent::run($input, $output);
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 * @since 25.0.0
	 */
	public function completeOptionValues($optionName, CompletionContext $context): array {
		if ($optionName === 'output') {
			return ['plain', 'json', 'json_pretty'];
		}
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 * @since 25.0.0
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context): array {
		return [];
	}
}
