<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Encryption;

use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Files\View;
use OCP\Encryption\IEncryptionModule;
use OCP\Encryption\IManager;
use OCP\IUserManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DecryptAll {
	/** @var array<string,list<string>> files which couldn't be decrypted */
	protected array $failed = [];

	public function __construct(
		protected IManager $encryptionManager,
		protected IUserManager $userManager,
		protected View $rootView,
	) {
	}

	/**
	 * start to decrypt all files
	 *
	 * @param string $user which users data folder should be decrypted, default = all users
	 * @throws \Exception
	 */
	public function decryptAll(InputInterface $input, OutputInterface $output, string $user = ''): bool {
		if ($user !== '' && $this->userManager->userExists($user) === false) {
			$output->writeln('User "' . $user . '" does not exist. Please check the username and try again');
			return false;
		}

		$output->writeln('prepare encryption modules...');
		if ($this->prepareEncryptionModules($input, $output, $user) === false) {
			return false;
		}
		$output->writeln(' done.');

		$this->failed = [];
		$this->decryptAllUsersFiles($output, $user);

		/** @psalm-suppress RedundantCondition $this->failed is modified by decryptAllUsersFiles, not clear why psalm fails to see it */
		if (empty($this->failed)) {
			$output->writeln('all files could be decrypted successfully!');
		} else {
			$output->writeln('Files for following users couldn\'t be decrypted, ');
			$output->writeln('maybe the user is not set up in a way that supports this operation: ');
			foreach ($this->failed as $uid => $paths) {
				$output->writeln('    ' . $uid);
				foreach ($paths as $path) {
					$output->writeln('        ' . $path);
				}
			}
			$output->writeln('');
		}

		return true;
	}

	/**
	 * prepare encryption modules to perform the decrypt all function
	 */
	protected function prepareEncryptionModules(InputInterface $input, OutputInterface $output, string $user): bool {
		// prepare all encryption modules for decrypt all
		$encryptionModules = $this->encryptionManager->getEncryptionModules();
		foreach ($encryptionModules as $moduleDesc) {
			/** @var IEncryptionModule $module */
			$module = call_user_func($moduleDesc['callback']);
			$output->writeln('');
			$output->writeln('Prepare "' . $module->getDisplayName() . '"');
			$output->writeln('');
			if ($module->prepareDecryptAll($input, $output, $user) === false) {
				$output->writeln('Module "' . $moduleDesc['displayName'] . '" does not support the functionality to decrypt all files again or the initialization of the module failed!');
				return false;
			}
		}

		return true;
	}

	/**
	 * iterate over all user and encrypt their files
	 *
	 * @param string $user which users files should be decrypted, default = all users
	 */
	protected function decryptAllUsersFiles(OutputInterface $output, string $user = ''): void {
		$output->writeln("\n");

		$userList = [];
		if ($user === '') {
			$fetchUsersProgress = new ProgressBar($output);
			$fetchUsersProgress->setFormat(" %message% \n [%bar%]");
			$fetchUsersProgress->start();
			$fetchUsersProgress->setMessage('Fetch list of users...');
			$fetchUsersProgress->advance();

			foreach ($this->userManager->getBackends() as $backend) {
				$limit = 500;
				$offset = 0;
				do {
					$users = $backend->getUsers('', $limit, $offset);
					foreach ($users as $user) {
						$userList[] = $user;
					}
					$offset += $limit;
					$fetchUsersProgress->advance();
				} while (count($users) >= $limit);
				$fetchUsersProgress->setMessage('Fetch list of users... finished');
				$fetchUsersProgress->finish();
			}
		} else {
			$userList[] = $user;
		}

		$output->writeln("\n\n");

		$progress = new ProgressBar($output);
		$progress->setFormat(" %message% \n [%bar%]");
		$progress->start();
		$progress->setMessage('starting to decrypt files...');
		$progress->advance();

		$numberOfUsers = count($userList);
		$userNo = 1;
		foreach ($userList as $uid) {
			$userCount = "$uid ($userNo of $numberOfUsers)";
			$this->decryptUsersFiles($uid, $progress, $userCount);
			$userNo++;
		}

		$progress->setMessage('starting to decrypt files... finished');
		$progress->finish();

		$output->writeln("\n\n");
	}

	/**
	 * encrypt files from the given user
	 */
	protected function decryptUsersFiles(string $uid, ProgressBar $progress, string $userCount): void {
		$this->setupUserFS($uid);
		$directories = [];
		$directories[] = '/' . $uid . '/files';

		while ($root = array_pop($directories)) {
			$content = $this->rootView->getDirectoryContent($root);
			foreach ($content as $file) {
				// only decrypt files owned by the user
				if ($file->getStorage()->instanceOfStorage('OCA\Files_Sharing\SharedStorage')) {
					continue;
				}
				$path = $root . '/' . $file['name'];
				if ($this->rootView->is_dir($path)) {
					$directories[] = $path;
					continue;
				} else {
					try {
						$progress->setMessage("decrypt files for user $userCount: $path");
						$progress->advance();
						if ($file->isEncrypted() === false) {
							$progress->setMessage("decrypt files for user $userCount: $path (already decrypted)");
							$progress->advance();
						} else {
							if ($this->decryptFile($path) === false) {
								$progress->setMessage("decrypt files for user $userCount: $path (already decrypted)");
								$progress->advance();
							}
						}
					} catch (\Exception $e) {
						if (isset($this->failed[$uid])) {
							$this->failed[$uid][] = $path;
						} else {
							$this->failed[$uid] = [$path];
						}
					}
				}
			}
		}
	}

	/**
	 * encrypt file
	 */
	protected function decryptFile(string $path): bool {
		// skip already decrypted files
		$fileInfo = $this->rootView->getFileInfo($path);
		if ($fileInfo !== false && !$fileInfo->isEncrypted()) {
			return true;
		}

		$source = $path;
		$target = $path . '.decrypted.' . $this->getTimestamp();

		try {
			$this->rootView->copy($source, $target);
			$this->rootView->touch($target, $fileInfo->getMTime());
			$this->rootView->rename($target, $source);
		} catch (DecryptionFailedException $e) {
			if ($this->rootView->file_exists($target)) {
				$this->rootView->unlink($target);
			}
			return false;
		}

		return true;
	}

	/**
	 * get current timestamp
	 */
	protected function getTimestamp(): int {
		return time();
	}

	/**
	 * setup user file system
	 */
	protected function setupUserFS(string $uid): void {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
	}
}
