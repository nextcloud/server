<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Command;

use OCA\Files_External\Lib\StorageConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Option extends Config {
	#[\Override]
	protected function configure(): void {
		$this
			->setName('files_external:option')
			->setDescription('Get or set a global external storage mount option')
			->addArgument(
				'mount_id',
				InputArgument::REQUIRED,
				'ID of the global mount to read or update'
			)->addArgument(
				'key',
				InputArgument::REQUIRED,
				'mount option name'
			)->addArgument(
				'value',
				InputArgument::OPTIONAL,
				'value to set; omit to print the current value; with --value-from-file, this is a file path'
			)->addOption(
				'value-from-file',
				null,
				InputOption::VALUE_NONE,
				'read the value from the file specified by value'
			)->setHelp(<<<'HELP'
Gets or sets one mount option on a global external storage mount.

If value is omitted, the current option value is printed. If value is
provided, the mount option is updated.

Mount options control how the mount behaves in Nextcloud. They are distinct
from backend configuration values such as host, share, username, and
password, which are managed with files_external:config.

JSON values are decoded when supplied, so values such as false, true,
numbers, arrays, and objects can be provided where supported by the option.
Use --value-from-file when the value should be read from a file.

Use files_external:list to find the mount ID.

Examples:
  occ files_external:option 1 readonly
  occ files_external:option 1 readonly true
  occ files_external:option 1 previews false
  occ files_external:option 1 filesystem_check_changes 0
  occ files_external:option 1 option_name /path/to/value --value-from-file
HELP
			);
	}

	/**
	 * @param string $key
	 */
	#[\Override]
	protected function getOption(StorageConfig $mount, $key, OutputInterface $output): void {
		$value = $mount->getMountOption($key);
		if (!is_string($value)) { // show bools and objects correctly
			$value = json_encode($value);
		}
		$output->writeln((string)$value);
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	#[\Override]
	protected function setOption(StorageConfig $mount, $key, $value, OutputInterface $output): void {
		$decoded = json_decode($value, true);
		if (!is_null($decoded)) {
			$value = $decoded;
		}
		$mount->setMountOption($key, $value);
		$this->globalService->updateStorage($mount);
	}
}
