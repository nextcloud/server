<?php

/**
 * SPDX-FileCopyrightText: 2021-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2019 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Encryption\Command;

use OC\Files\SetupManager;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OC\ServerNotAvailableException;
use OCA\Encryption\Util;
use OCP\Encryption\Exceptions\InvalidHeaderException;
use OCP\HintException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixEncryptedVersion extends Command {
	private bool $supportLegacy = false;

	public function __construct(
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
		private readonly IUserManager $userManager,
		private readonly Util $util,
		private readonly View $view,
		private readonly SetupManager $setupManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('encryption:fix-encrypted-version')
			->setDescription('Fix the encrypted version if the encrypted file(s) are not downloadable.')
			->addArgument(
				'user',
				InputArgument::OPTIONAL,
				'The id of the user whose files need fixing'
			)->addOption(
				'path',
				'p',
				InputOption::VALUE_REQUIRED,
				'Limit files to fix with path, e.g., --path="/Music/Artist". If path indicates a directory, all the files inside directory will be fixed.'
			)->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'Run the fix for all users on the system, mutually exclusive with specifying a user id.'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$skipSignatureCheck = $this->config->getSystemValueBool('encryption_skip_signature_check', false);
		$this->supportLegacy = $this->config->getSystemValueBool('encryption.legacy_format_support', false);

		if ($skipSignatureCheck) {
			$output->writeln("<error>Repairing is not possible when \"encryption_skip_signature_check\" is set. Please disable this flag in the configuration.</error>\n");
			return self::FAILURE;
		}

		if (!$this->util->isMasterKeyEnabled()) {
			$output->writeln("<error>Repairing only works with master key encryption.</error>\n");
			return self::FAILURE;
		}

		$user = $input->getArgument('user');
		$all = $input->getOption('all');
		$pathOption = \trim(($input->getOption('path') ?? ''), '/');

		if (!$user && !$all) {
			$output->writeln('Either a user id or --all needs to be provided');
			return self::FAILURE;
		}

		if ($user) {
			if ($all) {
				$output->writeln('Specifying a user id and --all are mutually exclusive');
				return self::FAILURE;
			}

			$user = $this->userManager->get($user);
			if ($user === null) {
				$output->writeln("<error>User id $user does not exist. Please provide a valid user id</error>");
				return self::FAILURE;
			}

			return $this->runForUser($user, $pathOption, $output) ? self::SUCCESS : self::FAILURE;
		}

		foreach ($this->userManager->getSeenUsers() as $user) {
			$output->writeln('Processing files for ' . $user->getUID());
			if (!$this->runForUser($user, $pathOption, $output)) {
				return self::FAILURE;
			}
		}
		return self::SUCCESS;
	}

	private function runForUser(IUser $user, string $pathOption, OutputInterface $output): bool {
		$pathToWalk = '/' . $user->getUID() . '/files';
		if ($pathOption !== '') {
			$pathToWalk = "$pathToWalk/$pathOption";
		}
		return $this->walkPathOfUser($user, $pathToWalk, $output);
	}

	private function walkPathOfUser(IUser $user, string $path, OutputInterface $output): bool {
		$this->setupUserFileSystem($user);
		if (!$this->view->file_exists($path)) {
			$output->writeln("<error>Path \"$path\" does not exist. Please provide a valid path.</error>");
			return false;
		}

		if ($this->view->is_file($path)) {
			$output->writeln("Verifying the content of file \"$path\"");
			$this->verifyFileContent($path, $output);
			return true;
		}
		$directories = [];
		$directories[] = $path;
		while ($root = \array_pop($directories)) {
			$directoryContent = $this->view->getDirectoryContent($root);
			foreach ($directoryContent as $file) {
				$path = $root . '/' . $file['name'];
				if ($this->view->is_dir($path)) {
					$directories[] = $path;
				} else {
					$output->writeln("Verifying the content of file \"$path\"");
					$this->verifyFileContent($path, $output);
				}
			}
		}
		return true;
	}

	/**
	 * @param bool $ignoreCorrectEncVersionCall, setting this variable to false avoids recursion
	 */
	private function verifyFileContent(string $path, OutputInterface $output, bool $ignoreCorrectEncVersionCall = true): bool {
		try {
			// since we're manually poking around the encrypted state we need to ensure that this isn't cached in the encryption wrapper
			$mount = $this->view->getMount($path);
			$storage = $mount->getStorage();
			if ($storage && $storage->instanceOfStorage(Encryption::class)) {
				$storage->clearIsEncryptedCache();
			}

			/**
			 * In encryption, the files are read in a block size of 8192 bytes
			 * Read block size of 8192 and a bit more (808 bytes)
			 * If there is any problem, the first block should throw the signature
			 * mismatch error. Which as of now, is enough to proceed ahead to
			 * correct the encrypted version.
			 */
			$handle = $this->view->fopen($path, 'rb');

			if ($handle === false) {
				$output->writeln("<warning>Failed to open file: \"$path\" skipping</warning>");
				return true;
			}

			if (\fread($handle, 9001) !== false) {
				$fileInfo = $this->view->getFileInfo($path);
				if (!$fileInfo) {
					$output->writeln("<warning>File info not found for file: \"$path\"</warning>");
					return true;
				}
				$encryptedVersion = $fileInfo->getEncryptedVersion();
				$stat = $this->view->stat($path);
				if (($encryptedVersion == 0) && isset($stat['hasHeader']) && ($stat['hasHeader'] == true)) {
					// The file has encrypted to false but has an encryption header
					if ($ignoreCorrectEncVersionCall === true) {
						// Lets rectify the file by correcting encrypted version
						$output->writeln("<info>Attempting to fix the path: \"$path\"</info>");
						return $this->correctEncryptedVersion($path, $output);
					}
					return false;
				}
				$output->writeln("<info>The file \"$path\" is: OK</info>");
			}

			\fclose($handle);

			return true;
		} catch (ServerNotAvailableException|InvalidHeaderException $e) {
			// not a "bad signature" error and likely "legacy cipher" exception
			// this could mean that the file is maybe not encrypted but the encrypted version is set
			if (!$this->supportLegacy && $ignoreCorrectEncVersionCall === true) {
				$output->writeln("<info>Attempting to fix the path: \"$path\"</info>");
				return $this->correctEncryptedVersion($path, $output, true);
			}
			return false;
		} catch (HintException $e) {
			$this->logger->warning('Issue: ' . $e->getMessage());
			// If allowOnce is set to false, this becomes recursive.
			if ($ignoreCorrectEncVersionCall === true) {
				// Lets rectify the file by correcting encrypted version
				$output->writeln("<info>Attempting to fix the path: \"$path\"</info>");
				return $this->correctEncryptedVersion($path, $output);
			}
			return false;
		}
	}

	/**
	 * @param bool $includeZero whether to try zero version for unencrypted file
	 */
	private function correctEncryptedVersion(string $path, OutputInterface $output, bool $includeZero = false): bool {
		$fileInfo = $this->view->getFileInfo($path);
		if (!$fileInfo) {
			$output->writeln("<warning>File info not found for file: \"$path\"</warning>");
			return true;
		}
		$fileId = $fileInfo->getId();
		if ($fileId === -1) {
			$output->writeln("<warning>File info contains no id for file: \"$path\"</warning>");
			return true;
		}
		$encryptedVersion = $fileInfo->getEncryptedVersion();
		$wrongEncryptedVersion = $encryptedVersion;

		$storage = $fileInfo->getStorage();

		$cache = $storage->getCache();
		$fileCache = $cache->get($fileId);
		if (!$fileCache) {
			$output->writeln("<warning>File cache entry not found for file: \"$path\"</warning>");
			return true;
		}

		if ($storage->instanceOfStorage('OCA\Files_Sharing\ISharedStorage')) {
			$output->writeln("<info>The file: \"$path\" is a share. Please also run the script for the owner of the share</info>");
			return true;
		}

		// Save original encrypted version so we can restore it if decryption fails with all version
		$originalEncryptedVersion = $encryptedVersion;
		if ($encryptedVersion >= 0) {
			if ($includeZero) {
				// try with zero first
				$cacheInfo = ['encryptedVersion' => 0, 'encrypted' => 0];
				$cache->put($fileCache->getPath(), $cacheInfo);
				$output->writeln('<info>Set the encrypted version to 0 (unencrypted)</info>');
				if ($this->verifyFileContent($path, $output, false) === true) {
					$output->writeln("<info>Fixed the file: \"$path\" with version 0 (unencrypted)</info>");
					return true;
				}
			}

			// Test by decrementing the value till 1 and if nothing works try incrementing
			$encryptedVersion--;
			while ($encryptedVersion > 0) {
				$cacheInfo = ['encryptedVersion' => $encryptedVersion, 'encrypted' => $encryptedVersion];
				$cache->put($fileCache->getPath(), $cacheInfo);
				$output->writeln("<info>Decrement the encrypted version to $encryptedVersion</info>");
				if ($this->verifyFileContent($path, $output, false) === true) {
					$output->writeln("<info>Fixed the file: \"$path\" with version " . $encryptedVersion . '</info>');
					return true;
				}
				$encryptedVersion--;
			}

			// So decrementing did not work. Now lets increment. Max increment is till 5
			$increment = 1;
			while ($increment <= 5) {
				/**
				 * The wrongEncryptedVersion would not be incremented so nothing to worry about here.
				 * Only the newEncryptedVersion is incremented.
				 * For example if the wrong encrypted version is 4 then
				 * cycle1 -> newEncryptedVersion = 5 ( 4 + 1)
				 * cycle2 -> newEncryptedVersion = 6 ( 4 + 2)
				 * cycle3 -> newEncryptedVersion = 7 ( 4 + 3)
				 */
				$newEncryptedVersion = $wrongEncryptedVersion + $increment;

				$cacheInfo = ['encryptedVersion' => $newEncryptedVersion, 'encrypted' => $newEncryptedVersion];
				$cache->put($fileCache->getPath(), $cacheInfo);
				$output->writeln("<info>Increment the encrypted version to $newEncryptedVersion</info>");
				if ($this->verifyFileContent($path, $output, false) === true) {
					$output->writeln("<info>Fixed the file: \"$path\" with version " . $newEncryptedVersion . '</info>');
					return true;
				}
				$increment++;
			}
		}

		$cacheInfo = ['encryptedVersion' => $originalEncryptedVersion, 'encrypted' => $originalEncryptedVersion];
		$cache->put($fileCache->getPath(), $cacheInfo);
		$output->writeln("<info>No fix found for \"$path\", restored version to original: $originalEncryptedVersion</info>");

		return false;
	}

	/**
	 * Setup user file system
	 */
	private function setupUserFileSystem(IUser $user): void {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);
	}
}
