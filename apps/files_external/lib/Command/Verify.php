<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\MountConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\AppFramework\Http;
use OCP\Files\StorageNotAvailableException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Verify extends Base {
	public function __construct(
		protected GlobalStoragesService $globalService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files_external:verify')
			->setDescription('Verify mount configuration')
			->addArgument(
				'mount_id',
				InputArgument::REQUIRED,
				'The id of the mount to check'
			)->addOption(
				'config',
				'c',
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Additional config option to set before checking in key=value pairs, required for certain auth backends such as login credentails'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mountId = $input->getArgument('mount_id');
		$configInput = $input->getOption('config');

		try {
			$mount = $this->globalService->getStorage($mountId);
		} catch (NotFoundException $e) {
			$output->writeln('<error>Mount with id "' . $mountId . ' not found, check "occ files_external:list" to get available mounts"</error>');
			return Http::STATUS_NOT_FOUND;
		}

		$this->updateStorageStatus($mount, $configInput, $output);

		$this->writeArrayInOutputFormat($input, $output, [
			'status' => StorageNotAvailableException::getStateCodeName($mount->getStatus()),
			'code' => $mount->getStatus(),
			'message' => $mount->getStatusMessage()
		]);
		return self::SUCCESS;
	}

	private function manipulateStorageConfig(StorageConfig $storage): void {
		$authMechanism = $storage->getAuthMechanism();
		$authMechanism->manipulateStorageConfig($storage);
		$backend = $storage->getBackend();
		$backend->manipulateStorageConfig($storage);
	}

	private function updateStorageStatus(StorageConfig &$storage, $configInput, OutputInterface $output): void {
		try {
			try {
				$this->manipulateStorageConfig($storage);
			} catch (InsufficientDataForMeaningfulAnswerException $e) {
				if (count($configInput) === 0) { // extra config options might solve the error
					throw $e;
				}
			}

			foreach ($configInput as $configOption) {
				if (!strpos($configOption, '=')) {
					$output->writeln('<error>Invalid mount configuration option "' . $configOption . '"</error>');
					return;
				}
				[$key, $value] = explode('=', $configOption, 2);
				$storage->setBackendOption($key, $value);
			}

			$backend = $storage->getBackend();
			// update status (can be time-consuming)
			$storage->setStatus(
				MountConfig::getBackendStatus(
					$backend->getStorageClass(),
					$storage->getBackendOptions(),
					false
				)
			);
		} catch (InsufficientDataForMeaningfulAnswerException $e) {
			$status = $e->getCode() ?: StorageNotAvailableException::STATUS_INDETERMINATE;
			$storage->setStatus(
				$status,
				$e->getMessage()
			);
		} catch (StorageNotAvailableException $e) {
			$storage->setStatus(
				$e->getCode(),
				$e->getMessage()
			);
		} catch (\Exception $e) {
			// FIXME: convert storage exceptions to StorageNotAvailableException
			$storage->setStatus(
				StorageNotAvailableException::STATUS_ERROR,
				get_class($e) . ': ' . $e->getMessage()
			);
		}
	}
}
