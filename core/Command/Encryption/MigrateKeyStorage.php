<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Encryption;

use OC\Encryption\Keys\Storage;
use OC\Encryption\Util;
use OC\Files\View;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateKeyStorage extends Command {
	public function __construct(
		protected View $rootView,
		protected IUserManager $userManager,
		protected IConfig $config,
		protected Util $util,
		private ICrypto $crypto,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('encryption:migrate-key-storage-format')
			->setDescription('Migrate the format of the keystorage to a newer format');
	}

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
		if (!$this->rootView->is_dir($root . '/files_encryption')) {
			return;
		}

		$this->traverseKeys($root . '/files_encryption', null, $output);
	}

	private function traverseKeys(string $folder, ?string $uid, OutputInterface $output): void {
		$listing = $this->rootView->getDirectoryContent($folder);

		foreach ($listing as $node) {
			if ($node['mimetype'] === 'httpd/unix-directory') {
				continue;
			}

			if ($node['name'] === 'fileKey'
				|| str_ends_with($node['name'], '.privateKey')
				|| str_ends_with($node['name'], '.publicKey')
				|| str_ends_with($node['name'], '.shareKey')) {
				$path = $folder . '/' . $node['name'];

				$content = $this->rootView->file_get_contents($path);

				if ($content === false) {
					$output->writeln("<error>Failed to open path $path</error>");
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
					'uid' => $uid,
				];

				$enc = $this->crypto->encrypt(json_encode($data));
				$this->rootView->file_put_contents($path, $enc);
			}
		}
	}

	private function traverseFileKeys(string $folder, OutputInterface $output): void {
		$listing = $this->rootView->getDirectoryContent($folder);

		foreach ($listing as $node) {
			if ($node['mimetype'] === 'httpd/unix-directory') {
				$this->traverseFileKeys($folder . '/' . $node['name'], $output);
			} else {
				$endsWith = function (string $haystack, string $needle): bool {
					$length = strlen($needle);
					if ($length === 0) {
						return true;
					}

					return (substr($haystack, -$length) === $needle);
				};

				if ($node['name'] === 'fileKey'
					|| $endsWith($node['name'], '.privateKey')
					|| $endsWith($node['name'], '.publicKey')
					|| $endsWith($node['name'], '.shareKey')) {
					$path = $folder . '/' . $node['name'];

					$content = $this->rootView->file_get_contents($path);

					if ($content === false) {
						$output->writeln("<error>Failed to open path $path</error>");
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
					$this->rootView->file_put_contents($path, $enc);
				}
			}
		}
	}


	/**
	 * setup file system for the given user
	 */
	protected function setupUserFS(string $uid): void {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
	}


	/**
	 * iterate over each user and move the keys to the new storage
	 */
	protected function updateUsersKeys(string $root, OutputInterface $output): void {
		$progress = new ProgressBar($output);
		$progress->start();

		foreach ($this->userManager->getBackends() as $backend) {
			$limit = 500;
			$offset = 0;
			do {
				$users = $backend->getUsers('', $limit, $offset);
				foreach ($users as $user) {
					$progress->advance();
					$this->setupUserFS($user);
					$this->updateUserKeys($root, $user, $output);
				}
				$offset += $limit;
			} while (count($users) >= $limit);
		}
		$progress->finish();
	}

	/**
	 * move user encryption folder to new root folder
	 *
	 * @throws \Exception
	 */
	protected function updateUserKeys(string $root, string $user, OutputInterface $output): void {
		if ($this->userManager->userExists($user)) {
			$source = $root . '/' . $user . '/files_encryption/OC_DEFAULT_MODULE';
			if ($this->rootView->is_dir($source)) {
				$this->traverseKeys($source, $user, $output);
			}

			$source = $root . '/' . $user . '/files_encryption/keys';
			if ($this->rootView->is_dir($source)) {
				$this->traverseFileKeys($source, $output);
			}
		}
	}
}
