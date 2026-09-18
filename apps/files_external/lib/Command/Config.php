<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\AppFramework\Http;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Config extends Base {
	public function __construct(
		protected GlobalStoragesService $globalService,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('files_external:config')
			->setDescription('Get or set a global external storage configuration value')
			->addArgument(
				'mount_id',
				InputArgument::REQUIRED,
				'ID of the global mount to read or update'
			)->addArgument(
				'key',
				InputArgument::REQUIRED,
				'backend configuration key, or mountpoint/mount_point'
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
Gets or sets one backend configuration value on a global external storage
mount.

If value is omitted, the current value is printed. If value is provided, the
configuration is updated. The special keys mountpoint and mount_point can be
used to read or change the mount point.

Use --value-from-file when the value should be read from a file, for example
for a long secret or certificate. JSON values such as true, false, numbers,
arrays, and objects are decoded when they are supplied as valid JSON.

Use files_external:list to find the mount ID.

Examples:
  occ files_external:config 1 host
  occ files_external:config 1 host files.example.com
  occ files_external:config 1 password 'secret'
  occ files_external:config 1 private_key /path/to/key --value-from-file
  occ files_external:config 1 mountpoint Documents
HELP
			);
		parent::configure();
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mountId = $input->getArgument('mount_id');
		$key = $input->getArgument('key');
		try {
			$mount = $this->globalService->getStorage($mountId);
		} catch (NotFoundException $e) {
			$output->writeln('<error>Mount with id "' . $mountId . ' not found, check "occ files_external:list" to get available mounts"</error>');
			return Http::STATUS_NOT_FOUND;
		}

		$value = $input->getArgument('value');
		if ($value !== null) {
			if ($input->getOption('value-from-file')) {
				$file = $value;
				$value = file_get_contents($file);
				if ($value === false) {
					$output->writeln('<error>Failed to load value from ' . $file . '</error>');
					return 1;
				}
			}
			$this->setOption($mount, $key, $value, $output);
		} else {
			$this->getOption($mount, $key, $output);
		}
		return self::SUCCESS;
	}

	/**
	 * @param string $key
	 */
	protected function getOption(StorageConfig $mount, $key, OutputInterface $output): void {
		if ($key === 'mountpoint' || $key === 'mount_point') {
			$value = $mount->getMountPoint();
		} else {
			$value = $mount->getBackendOption($key);
		}
		if (!is_string($value) && json_decode(json_encode($value)) === $value) { // show bools and objects correctly
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
		if (!is_null($decoded) && json_encode($decoded) === $value) {
			$value = $decoded;
		}
		if ($key === 'mountpoint' || $key === 'mount_point') {
			$mount->setMountPoint($value);
		} else {
			$mount->setBackendOption($key, $value);
		}
		$this->globalService->updateStorage($mount);
	}
}
