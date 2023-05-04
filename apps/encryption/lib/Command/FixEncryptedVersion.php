<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\Command;

use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OC\ServerNotAvailableException;
use OCA\Encryption\Util;
use OCP\Files\IRootFolder;
use OCP\HintException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixEncryptedVersion extends Command {
	/** @var IConfig */
	private $config;

	/** @var ILogger */
	private $logger;

	/** @var IRootFolder  */
	private $rootFolder;

	/** @var IUserManager  */
	private $userManager;

	/** @var Util */
	private $util;

	/** @var View  */
	private $view;

	/** @var bool */
	private $supportLegacy;

	public function __construct(
		IConfig $config,
		ILogger $logger,
		IRootFolder $rootFolder,
		IUserManager $userManager,
		Util $util,
		View $view
	) {
		$this->config = $config;
		$this->logger = $logger;
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->util = $util;
		$this->view = $view;
		$this->supportLegacy = false;

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
			return 1;
		}

		if (!$this->util->isMasterKeyEnabled()) {
			$output->writeln("<error>Repairing only works with master key encryption.</error>\n");
			return 1;
		}

		$user = $input->getArgument('user');
		$all = $input->getOption('all');
		$pathOption = \trim(($input->getOption('path') ?? ''), '/');

		if ($user) {
			if ($all) {
				$output->writeln("Specifying a user id and --all are mutually exclusive");
				return 1;
			}

			if ($this->userManager->get($user) === null) {
				$output->writeln("<error>User id $user does not exist. Please provide a valid user id</error>");
				return 1;
			}

			return $this->runForUser($user, $pathOption, $output);
		} elseif ($all) {
			$result = 0;
			$this->userManager->callForSeenUsers(function(IUser $user) use ($pathOption, $output, &$result) {
				$output->writeln("Processing files for " . $user->getUID());
				$result = $this->runForUser($user->getUID(), $pathOption, $output);
				return $result === 0;
			});
			return $result;
		} else {
			$output->writeln("Either a user id or --all needs to be provided");
			return 1;
		}
	}

	private function runForUser(string $user, string $pathOption, OutputInterface $output): int {
		$pathToWalk = "/$user/files";
		if ($pathOption !== "") {
			$pathToWalk = "$pathToWalk/$pathOption";
		}
		return $this->walkPathOfUser($user, $pathToWalk, $output);
	}

	/**
	 * @return int 0 for success, 1 for error
	 */
	private function walkPathOfUser(string $user, string $path, OutputInterface $output): int {
		$this->setupUserFs($user);
		if (!$this->view->file_exists($path)) {
			$output->writeln("<error>Path \"$path\" does not exist. Please provide a valid path.</error>");
			return 1;
		}

		if ($this->view->is_file($path)) {
			$output->writeln("Verifying the content of file \"$path\"");
			$this->verifyFileContent($path, $output);
			return 0;
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
		return 0;
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
		} catch (ServerNotAvailableException $e) {
			// not a "bad signature" error and likely "legacy cipher" exception
			// this could mean that the file is maybe not encrypted but the encrypted version is set
			if (!$this->supportLegacy && $ignoreCorrectEncVersionCall === true) {
				$output->writeln("<info>Attempting to fix the path: \"$path\"</info>");
				return $this->correctEncryptedVersion($path, $output, true);
			}
			return false;
		} catch (HintException $e) {
			$this->logger->warning("Issue: " . $e->getMessage());
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
		if ($fileId === null) {
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
				$output->writeln("<info>Set the encrypted version to 0 (unencrypted)</info>");
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
					$output->writeln("<info>Fixed the file: \"$path\" with version " . $encryptedVersion . "</info>");
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
					$output->writeln("<info>Fixed the file: \"$path\" with version " . $newEncryptedVersion . "</info>");
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
	private function setupUserFs(string $uid): void {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
	}
}
