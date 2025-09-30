<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Encryption\Command;

use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Files\FileInfo;
use OC\Files\View;
use OCA\Encryption\KeyManager;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DropLegacyFileKey extends Command {
	private View $rootView;

	public function __construct(
		private IUserManager $userManager,
		private KeyManager $keyManager,
	) {
		parent::__construct();

		$this->rootView = new View();
	}

	protected function configure(): void {
		$this
			->setName('encryption:drop-legacy-filekey')
			->setDescription('Scan the files for the legacy filekey format using RC4 and get rid of it (if master key is enabled)');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$result = true;

		$output->writeln('<info>Scanning all files for legacy filekey</info>');

		foreach ($this->userManager->getBackends() as $backend) {
			$limit = 500;
			$offset = 0;
			do {
				$users = $backend->getUsers('', $limit, $offset);
				foreach ($users as $user) {
					$output->writeln('Scanning all files for ' . $user);
					$this->setupUserFS($user);
					$result = $result && $this->scanFolder($output, '/' . $user);
				}
				$offset += $limit;
			} while (count($users) >= $limit);
		}

		if ($result) {
			$output->writeln('All scanned files are properly encrypted.');
			return self::SUCCESS;
		}

		return self::FAILURE;
	}

	private function scanFolder(OutputInterface $output, string $folder): bool {
		$clean = true;

		foreach ($this->rootView->getDirectoryContent($folder) as $item) {
			$path = $folder . '/' . $item['name'];
			if ($this->rootView->is_dir($path)) {
				if ($this->scanFolder($output, $path) === false) {
					$clean = false;
				}
			} else {
				if (!$item->isEncrypted()) {
					// ignore
					continue;
				}

				$stats = $this->rootView->stat($path);
				if (!isset($stats['hasHeader']) || $stats['hasHeader'] === false) {
					$clean = false;
					$output->writeln('<error>' . $path . ' does not have a proper header</error>');
				} else {
					try {
						$legacyFileKey = $this->keyManager->getFileKey($path, true);
						if ($legacyFileKey === '') {
							$output->writeln('Got an empty legacy filekey for ' . $path . ', continuing', OutputInterface::VERBOSITY_VERBOSE);
							continue;
						}
					} catch (GenericEncryptionException $e) {
						$output->writeln('Got a decryption error for legacy filekey for ' . $path . ', continuing', OutputInterface::VERBOSITY_VERBOSE);
						continue;
					}
					/* If that did not throw and filekey is not empty, a legacy filekey is used */
					$clean = false;
					$output->writeln($path . ' is using a legacy filekey, migrating');
					$this->migrateSinglefile($path, $item, $output);
				}
			}
		}

		return $clean;
	}

	private function migrateSinglefile(string $path, FileInfo $fileInfo, OutputInterface $output): void {
		$source = $path;
		$target = $path . '.reencrypted.' . time();

		try {
			$this->rootView->copy($source, $target);
			$copyResource = $this->rootView->fopen($target, 'r');
			$sourceResource = $this->rootView->fopen($source, 'w');
			if ($copyResource === false || $sourceResource === false) {
				throw new DecryptionFailedException('Failed to open ' . $source . ' or ' . $target);
			}
			if (stream_copy_to_stream($copyResource, $sourceResource) === false) {
				$output->writeln('<error>Failed to copy ' . $target . ' data into ' . $source . '</error>');
				$output->writeln('<error>Leaving both files in there to avoid data loss</error>');
				return;
			}
			$this->rootView->touch($source, $fileInfo->getMTime());
			$this->rootView->unlink($target);
			$output->writeln('<info>Migrated ' . $source . '</info>', OutputInterface::VERBOSITY_VERBOSE);
		} catch (DecryptionFailedException $e) {
			if ($this->rootView->file_exists($target)) {
				$this->rootView->unlink($target);
			}
			$output->writeln('<error>Failed to migrate ' . $path . '</error>');
			$output->writeln('<error>' . $e . '</error>', OutputInterface::VERBOSITY_VERBOSE);
		} finally {
			if (is_resource($copyResource)) {
				fclose($copyResource);
			}
			if (is_resource($sourceResource)) {
				fclose($sourceResource);
			}
		}
	}

	/**
	 * setup user file system
	 */
	protected function setupUserFS(string $uid): void {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
	}
}
