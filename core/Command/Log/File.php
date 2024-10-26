<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Log;

use OCP\IConfig;

use Stecman\Component\Symfony\Console\BashCompletion\Completion;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\ShellPathCompletion;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class File extends Command implements Completion\CompletionAwareInterface {
	public function __construct(
		protected IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('log:file')
			->setDescription('manipulate logging backend')
			->addOption(
				'enable',
				null,
				InputOption::VALUE_NONE,
				'enable this logging backend'
			)
			->addOption(
				'file',
				null,
				InputOption::VALUE_REQUIRED,
				'set the log file path'
			)
			->addOption(
				'rotate-size',
				null,
				InputOption::VALUE_REQUIRED,
				'set the file size for log rotation, 0 = disabled'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$toBeSet = [];

		if ($input->getOption('enable')) {
			$toBeSet['log_type'] = 'file';
		}

		if ($file = $input->getOption('file')) {
			$toBeSet['logfile'] = $file;
		}

		if (($rotateSize = $input->getOption('rotate-size')) !== null) {
			$rotateSize = \OCP\Util::computerFileSize($rotateSize);
			$this->validateRotateSize($rotateSize);
			$toBeSet['log_rotate_size'] = $rotateSize;
		}

		// set config
		foreach ($toBeSet as $option => $value) {
			$this->config->setSystemValue($option, $value);
		}

		// display config
		// TODO: Drop backwards compatibility for config in the future
		$logType = $this->config->getSystemValue('log_type', 'file');
		if ($logType === 'file' || $logType === 'owncloud') {
			$enabledText = 'enabled';
		} else {
			$enabledText = 'disabled';
		}
		$output->writeln('Log backend file: ' . $enabledText);

		$dataDir = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');
		$defaultLogFile = rtrim($dataDir, '/') . '/nextcloud.log';
		$output->writeln('Log file: ' . $this->config->getSystemValue('logfile', $defaultLogFile));

		$rotateSize = $this->config->getSystemValue('log_rotate_size', 100 * 1024 * 1024);
		if ($rotateSize) {
			$rotateString = \OCP\Util::humanFileSize($rotateSize);
		} else {
			$rotateString = 'disabled';
		}
		$output->writeln('Rotate at: ' . $rotateString);
		return 0;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function validateRotateSize(false|int|float $rotateSize): void {
		if ($rotateSize === false) {
			throw new \InvalidArgumentException('Error parsing log rotation file size');
		}
		if ($rotateSize < 0) {
			throw new \InvalidArgumentException('Log rotation file size must be non-negative');
		}
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeOptionValues($optionName, CompletionContext $context) {
		if ($optionName === 'file') {
			$helper = new ShellPathCompletion(
				$this->getName(),
				'file',
				Completion::TYPE_OPTION
			);
			return $helper->run();
		} elseif ($optionName === 'rotate-size') {
			return [0];
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
