<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command;

use OC\Core\Command\User\ListCommand;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Base extends Command implements CompletionAwareInterface {
	public const OUTPUT_FORMAT_PLAIN = 'plain';
	public const OUTPUT_FORMAT_JSON = 'json';
	public const OUTPUT_FORMAT_JSON_PRETTY = 'json_pretty';

	protected string $defaultOutputFormat = self::OUTPUT_FORMAT_PLAIN;
	private bool $php_pcntl_signal = false;
	private bool $interrupted = false;

	protected function configure() {
		// Some of our commands do not extend this class; and some of those that do do not call parent::configure()
		$defaultHelp = 'More extensive and thorough documentation may be found at ' . \OCP\Server::get(\OCP\Defaults::class)->getDocBaseUrl() . PHP_EOL;
		$this
			->setHelp($defaultHelp)
			->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat
			)
		;
	}

	protected function writeArrayInOutputFormat(InputInterface $input, OutputInterface $output, iterable $items, string $prefix = '  - '): void {
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON:
				$items = (is_array($items) ? $items : iterator_to_array($items));
				$output->writeln(json_encode($items));
				break;
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$items = (is_array($items) ? $items : iterator_to_array($items));
				$output->writeln(json_encode($items, JSON_PRETTY_PRINT));
				break;
			default:
				foreach ($items as $key => $item) {
					if (is_iterable($item)) {
						$output->writeln($prefix . $key . ':');
						$this->writeArrayInOutputFormat($input, $output, $item, '  ' . $prefix);
						continue;
					}
					if (!is_int($key) || get_class($this) === ListCommand::class) {
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

	protected function writeTableInOutputFormat(InputInterface $input, OutputInterface $output, array $items): void {
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON:
				$output->writeln(json_encode($items));
				break;
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$output->writeln(json_encode($items, JSON_PRETTY_PRINT));
				break;
			default:
				$table = new Table($output);
				$table->setRows($items);
				if (!empty($items) && is_string(array_key_first(reset($items)))) {
					$table->setHeaders(array_keys(reset($items)));
				}
				$table->render();
				break;
		}
	}

	protected function writeStreamingTableInOutputFormat(InputInterface $input, OutputInterface $output, \Iterator $items, int $tableGroupSize): void {
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON:
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				$this->writeStreamingJsonArray($input, $output, $items);
				break;
			default:
				foreach ($this->chunkIterator($items, $tableGroupSize) as $chunk) {
					$this->writeTableInOutputFormat($input, $output, $chunk);
				}
				break;
		}
	}

	protected function writeStreamingJsonArray(InputInterface $input, OutputInterface $output, \Iterator $items): void {
		$first = true;
		$outputType = $input->getOption('output');

		$output->writeln('[');
		foreach ($items as $item) {
			if (!$first) {
				$output->writeln(',');
			}
			if ($outputType === self::OUTPUT_FORMAT_JSON_PRETTY) {
				$output->write(json_encode($item, JSON_PRETTY_PRINT));
			} else {
				$output->write(json_encode($item));
			}
			$first = false;
		}
		$output->writeln("\n]");
	}

	public function chunkIterator(\Iterator $iterator, int $count): \Iterator {
		$chunk = [];

		for ($i = 0; $iterator->valid(); $i++) {
			$chunk[] = $iterator->current();
			$iterator->next();
			if (count($chunk) == $count) {
				// Got a full chunk, yield and start a new one
				yield $chunk;
				$chunk = [];
			}
		}

		if (count($chunk)) {
			// Yield the last chunk even if incomplete
			yield $chunk;
		}
	}


	/**
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

	protected function valueToString($value, bool $returnNull = true): ?string {
		if ($value === false) {
			return 'false';
		} elseif ($value === true) {
			return 'true';
		} elseif ($value === null) {
			return $returnNull ? null : 'null';
		} if ($value instanceof \UnitEnum) {
			return $value->value;
		} else {
			return $value;
		}
	}

	/**
	 * Throw InterruptedException when interrupted by user
	 *
	 * @throws InterruptedException
	 */
	protected function abortIfInterrupted() {
		if ($this->php_pcntl_signal === false) {
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
	 */
	public function cancelOperation(): void {
		$this->interrupted = true;
	}

	public function run(InputInterface $input, OutputInterface $output): int {
		// check if the php pcntl_signal functions are accessible
		$this->php_pcntl_signal = function_exists('pcntl_signal');
		if ($this->php_pcntl_signal) {
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
	 */
	public function completeOptionValues($optionName, CompletionContext $context) {
		if ($optionName === 'output') {
			return ['plain', 'json', 'json_pretty'];
		}
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		return [];
	}
}
