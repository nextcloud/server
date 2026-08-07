<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Encryption;

use OC\Encryption\Util;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateKeyStorage extends Command {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IConfig $config,
		private readonly Util $util,
		private readonly ICrypto $crypto,
		private readonly ISetupManager $setupManager,
		private readonly IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		parent::configure();
		$this
			->setName('encryption:migrate-key-storage-format')
			->setDescription('Migrate the format of the keystorage to a newer format');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$root = $this->util->getKeyStorageRoot();

		$output->writeln('Updating key storage format');
		$this->updateKeys($root, $output);
		$output->writeln('Key storage format successfully updated');

		return 0;
	}

	/**
	 * Move keys to new key storage root
	 *
	 * @throws \Exception
	 */
	protected function updateKeys(string $root, OutputInterface $output): bool {
		$output->writeln('Start to update the keys:');

		$this->updateSystemKeys($root, $output);
		$this->updateUsersKeys($root, $output);
		$this->config->deleteSystemValue('encryption.key_storage_migrated');
		return true;
	}

	/**
	 * Move system key folder
	 */
	protected function updateSystemKeys(string $root, OutputInterface $output): void {
		try {
			$folder = $this->rootFolder->get($root . '/files_encryption');
		} catch (NotFoundException $e) {
			$folder = null;
		}

		if ($folder instanceof Folder) {
			$this->traverseKeys($folder, null, $output);
		}
	}

	private function traverseKeys(Folder $folder, ?Iuser $user, OutputInterface $output): void {
		$listing = $folder->getDirectoryListing();

		foreach ($listing as $node) {
			if (!$node instanceof File) {
				continue;
			}

			if ($node->getName() === 'fileKey'
				|| str_ends_with($node->getName(), '.privateKey')
				|| str_ends_with($node->getName(), '.publicKey')
				|| str_ends_with($node->getName(), '.shareKey')) {

				try {
					$content = $node->getContent();
				} catch (\Exception) {
					$output->writeln('<error>Failed to open path ' . $node->getPath() . '</error>');
					continue;
				}

				try {
					$this->crypto->decrypt($content);
					continue;
				} catch (\Exception $e) {
					// Ignore we now update the data.
				}

				$data = [
					'key' => base64_encode($content),
					'uid' => $user?->getUID() ?? null,
				];

				$enc = $this->crypto->encrypt(json_encode($data));
				$node->putContent($enc);
			}
		}
	}

	private function traverseFileKeys(Folder $folder, OutputInterface $output): void {
		$listing = $folder->getDirectoryListing();

		foreach ($listing as $node) {
			if ($node instanceof Folder) {
				$this->traverseFileKeys($node, $output);
			} elseif ($node instanceof File) {
				if ($node->getName() === 'fileKey'
					|| str_ends_with($node->getName(), '.privateKey')
					|| str_ends_with($node->getName(), '.publicKey')
					|| str_ends_with($node->getName(), '.shareKey')) {

					try {
						$content = $node->getContent();
					} catch (\Exception) {
						$output->writeln('<error>Failed to open path ' . $node->getPath() . '</error>');
						continue;
					}

					try {
						$this->crypto->decrypt($content);
						continue;
					} catch (\Exception $e) {
						// Ignore we now update the data.
					}

					$data = [
						'key' => base64_encode($content)
					];

					$enc = $this->crypto->encrypt(json_encode($data));
					$node->putContent($enc);
				}
			}
		}
	}

	/**
	 * iterate over each user and move the keys to the new storage
	 */
	protected function updateUsersKeys(string $root, OutputInterface $output): void {
		$progress = new ProgressBar($output);
		$progress->start();

		$this->userManager->callForAllUsers(function (IUser $user) use ($progress, $root, $output): void {
			$progress->advance();
			$this->setupManager->tearDown();
			$this->setupManager->setupForUser($user);
			$this->updateUserKeys($root, $user, $output);
		});
		$progress->finish();
	}

	/**
	 * move user encryption folder to new root folder
	 *
	 * @throws \Exception
	 */
	protected function updateUserKeys(string $root, IUser $user, OutputInterface $output): void {
		$source = $root . '/' . $user->getUID() . '/files_encryption/OC_DEFAULT_MODULE';
		try {
			$folder = $this->rootFolder->get($source);
		} catch (NotFoundException) {
			$folder = null;
		}

		if ($folder instanceof Folder) {
			$this->traverseKeys($folder, $user, $output);
		}

		$source = $root . '/' . $user->getUID() . '/files_encryption/keys';

		try {
			$folder = $this->rootFolder->get($source);
		} catch (NotFoundException) {
			$folder = null;
		}

		if ($folder instanceof Folder) {
			$this->traverseFileKeys($folder, $output);
		}
	}
}
