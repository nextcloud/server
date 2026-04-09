<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Command;

use OCA\Files_External\Lib\StorageConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class Option extends Config {
	protected function configure(): void {
		$this
			->setName('files_external:option')
			->setDescription('Manage mount options for a mount')
			->addArgument(
				'mount_id',
				InputArgument::REQUIRED,
				'The id of the mount to edit'
			)->addArgument(
				'key',
				InputArgument::REQUIRED,
				'key of the mount option to set/get'
			)->addArgument(
				'value',
				InputArgument::OPTIONAL,
				'value to set the mount option to, when no value is provided the existing value will be printed'
			);
	}

	/**
	 * @param string $key
	 */
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
	protected function setOption(StorageConfig $mount, $key, $value, OutputInterface $output): void {
		$decoded = json_decode($value, true);
		if (!is_null($decoded)) {
			$value = $decoded;
		}
		$mount->setMountOption($key, $value);
		$this->globalService->updateStorage($mount);
	}
}
