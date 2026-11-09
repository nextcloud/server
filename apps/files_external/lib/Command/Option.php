<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Command;

use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Option extends Config {
	/**
	 * Known backend/auth credential key names that belong to files_external:config,
	 * not mount options. Includes Password auth (`user`, `password`) and the
	 * docs/help wording (`username` from files_external:option/config help text).
	 */
	private const CREDENTIAL_KEYS = [
		'user',
		'username',
		'password',
	];

	#[\Override]
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
				'value to set the config option to; when omitted, the existing value is printed; with --value-from-file, this is treated as a file pat'
			)->addOption(
				'value-from-file',
				null,
				InputOption::VALUE_NONE,
				'treat the value argument as a file path and read the config value from that file'
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
	protected function setOption(StorageConfig $mount, $key, $value, OutputInterface $output): int {
		if ($this->isCredentialKey($mount, $key)) {
			$output->writeln('<error>"' . $key . '" is a backend configuration / credential key, not a mount option. Use "occ files_external:config" instead.</error>');
			return self::FAILURE;
		}

		$decoded = json_decode($value, true);
		if (!is_null($decoded)) {
			$value = $decoded;
		}
		$mount->setMountOption($key, $value);
		$this->globalService->updateStorage($mount);
		return self::SUCCESS;
	}

	/**
	 * Whether $key is a backend/auth credential that must be set via files_external:config.
	 */
	private function isCredentialKey(StorageConfig $mount, string $key): bool {
		if (in_array($key, self::CREDENTIAL_KEYS, true)) {
			return true;
		}

		$parameters = array_merge(
			$mount->getBackend()->getParameters(),
			$mount->getAuthMechanism()->getParameters()
		);
		foreach ($parameters as $parameter) {
			if ($parameter->getName() === $key
				&& $parameter->getType() === DefinitionParameter::VALUE_PASSWORD) {
				return true;
			}
		}

		return false;
	}
}
