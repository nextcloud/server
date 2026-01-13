<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Encryption;

use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Files\FileInfo;
use OC\Files\View;
use OCP\Encryption\IEncryptionModule;
use OCP\Encryption\IManager;
use OCP\IUserManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * DecryptAll handles bulk decryption of files for users.
 */
class DecryptAll {
	/** @var array<string,list<string>> files which couldn't be decrypted */
	protected array $failed = [];

	protected readonly LoggerInterface $logger;

	public function __construct(
		protected readonly IManager $encryptionManager,
		protected readoly IUserManager $userManager,
		protected readonly View $rootView,
	) {
		// TODO: Inject LoggerInterface
		$this->logger = \OC::$server->get(LoggerInterface::class);
		// TODO: Inject SetupManager
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

		// TODO: Drop below output; maybe still use $this->failed to return false (if we can't switch to void)
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
	 * iterate over all user and decrypt their files
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
		$progress->setMessage('Decrypting files...');
		$progress->advance();

		$numberOfUsers = count($userList);
		$userNo = 1;
		foreach ($userList as $uid) {
			$userCount = "$uid ($userNo of $numberOfUsers)";
			$this->decryptUsersFiles($uid, $progress, $userCount);
			$userNo++;
		}

		$progress->setMessage('Decrypting files... finished');
		$progress->finish();

		$output->writeln("\n\n");
	}

/**
 * Decrypt all files as the given user.
 *
 * Recursively traverses the user's files directory, skipping files and folders not owned by the user,
 * and attempts to decrypt each file.
 */
 protected function decryptUsersFiles(string $uid, ProgressBar $progress, string $userCount): void {
		$this->setupUserFS($uid);
		$directories = [];
		$directories[] = '/' . $uid . '/files';

		while ($root = array_pop($directories)) {
			$content = $this->rootView->getDirectoryContent($root);
			/** @var FileInfo $file */
			foreach ($content as $file) {
				$path = $root . '/' . $file->getName();

				if ($file->getOwner() !== $uid) {
					$progress->setMessage("Skipping shared/unowned file/folder $path");
					$progress->advance();
					continue;
				}

				if ($file->getType() === FileInfo::TYPE_FOLDER) {
					$directories[] = $path;
					continue;
				}

				$progress->setMessage("Decrypting file for user $userCount: $path");
				$progress->advance();

				try {
					if ($this->decryptFile($path) === false) {
						$progress->setMessage("Skipping already decrypted file $path for user $userCount");
						$progress->advance();
					}
				} catch (\Exception $e) {
					$progress->setMessage("Failed to decrypt path $path: " . $e->getMessage());					
					$progress->advance();
					$this->logger->error('Failed to decrypt path {path}', [ 'user' => $uid, 'path' => $path, 'exception' => $e, ]);
					// TODO: we can probably drop this since we're now outputting above like we do in EncryptAll
					if (isset($this->failed[$uid])) {
						$this->failed[$uid][] = $path;
					} else {
						$this->failed[$uid] = [$path];
					}
				}
			}
		}
	}

	/**
	 * Attempt to decrypt a single file.
	 * @param string $path  The full filesystem path to the file.
	 *
	 * @throws DecryptionFailedException If file copy or rename fails during decryption.
	 * @throws RuntimeException If file info cannot be retrieved or touch fails.
	 *
	 * @return bool True if decryption succeeded, false if file is already decrypted.
	 */
	protected function decryptFile(string $path): bool {
		$fileInfo = $this->rootView->getFileInfo($path);
	
		if ($fileInfo === false) {
    		throw new \RuntimeException("Could not retrieve file info for $path");
		}
		
		if (!$fileInfo->isEncrypted()) {
			return false;
		}

		$source = $path;
		$target = $path . '.decrypted.' . time();

		try {
			if ($this->rootView->copy($source, $target) === false) {
				throw new DecryptionFailedException("Failed to copy $source -> $target");
			}

			if ($this->rootView->touch($target, $fileInfo->getMTime()) === false) {
				throw new \RuntimeException("Failed to update mtime for $target");
			}

			if ($this->rootView->rename($target, $source) === false) {
				throw new DecryptionFailedException("Failed to rename $target -> $source");
			}
		} catch (\Exception $e) {
			if ($this->rootView->file_exists($target)) {
				$this->logger->debug("Cleaning up failed temp file $target after decryption exception", [ 'user' => $uid, 'path' => $path, ]);
				$this->rootView->unlink($target);
			}
			throw $e;
		}

		return true;
	}

	/**
	 * setup user file system
	 */
	protected function setupUserFS(string $uid): void {
		// TODO: Refactor to use injected SetupManager (like EncryptAll does) + the IUser objeect
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
	}
}
