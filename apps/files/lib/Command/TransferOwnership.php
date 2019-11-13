<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Carla Schroder <carla@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sujith H <sharidasan@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

namespace OCA\Files\Command;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\Files\FileInfo;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TransferOwnership extends Command {

	/** @var IUserManager $userManager */
	private $userManager;

	/** @var IManager */
	private $shareManager;

	/** @var IMountManager */
	private $mountManager;

	/** @var FileInfo[] */
	private $allFiles = [];

	/** @var FileInfo[] */
	private $encryptedFiles = [];

	/** @var IShare[] */
	private $shares = [];

	/** @var string */
	private $sourceUser;

	/** @var string */
	private $destinationUser;

	/** @var string */
	private $sourcePath;

	/** @var string */
	private $finalTarget;

	public function __construct(IUserManager $userManager,
								IManager $shareManager,
								IMountManager $mountManager,
								IRootFolder $rootFolder) {
		$this->userManager = $userManager;
		$this->shareManager = $shareManager;
		$this->mountManager = $mountManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('files:transfer-ownership')
			->setDescription('All files and folders are moved to another user - shares are moved as well.')
			->addArgument(
				'source-user',
				InputArgument::REQUIRED,
				'owner of files which shall be moved'
			)
			->addArgument(
				'destination-user',
				InputArgument::REQUIRED,
				'user who will be the new owner of the files'
			)
			->addOption(
				'path',
				null,
				InputOption::VALUE_REQUIRED,
				'selectively provide the path to transfer. For example --path="folder_name"',
				''
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$sourceUserObject = $this->userManager->get($input->getArgument('source-user'));
		$destinationUserObject = $this->userManager->get($input->getArgument('destination-user'));

		if (!$sourceUserObject instanceof IUser) {
			$output->writeln("<error>Unknown source user $this->sourceUser</error>");
			return 1;
		}

		if (!$destinationUserObject instanceof IUser) {
			$output->writeln("<error>Unknown destination user $this->destinationUser</error>");
			return 1;
		}

		$this->sourceUser = $sourceUserObject->getUID();
		$this->destinationUser = $destinationUserObject->getUID();
		$sourcePathOption = ltrim($input->getOption('path'), '/');
		$this->sourcePath = rtrim($this->sourceUser . '/files/' . $sourcePathOption, '/');

		// target user has to be ready
		if (!\OC::$server->getEncryptionManager()->isReadyForUser($this->destinationUser)) {
			$output->writeln("<error>The target user is not ready to accept files. The user has at least to be logged in once.</error>");
			return 2;
		}

		$date = date('Y-m-d H-i-s');
		$this->finalTarget = "$this->destinationUser/files/transferred from $this->sourceUser on $date";

		// setup filesystem
		Filesystem::initMountPoints($this->sourceUser);
		Filesystem::initMountPoints($this->destinationUser);

		$view = new View();
		if (!$view->is_dir($this->sourcePath)) {
			$output->writeln("<error>Unknown path provided: $sourcePathOption</error>");
			return 1;
		}

		// analyse source folder
		$this->analyse($output);

		// collect all the shares
		$this->collectUsersShares($output);

		// transfer the files
		$this->transfer($output);

		// restore the shares
		$this->restoreShares($output);
	}

	private function walkFiles(View $view, $path, \Closure $callBack) {
		foreach ($view->getDirectoryContent($path) as $fileInfo) {
			if (!$callBack($fileInfo)) {
				return;
			}
			if ($fileInfo->getType() === FileInfo::TYPE_FOLDER) {
				$this->walkFiles($view, $fileInfo->getPath(), $callBack);
			}
		}
	}

	/**
	 * @param OutputInterface $output
	 * @throws \Exception
	 */
	protected function analyse(OutputInterface $output) {
		$view = new View();

		$output->writeln('Validating quota');
		$size = $view->getFileInfo($this->sourcePath, false)->getSize(false);
		$freeSpace = $view->free_space($this->destinationUser . '/files/');
		if ($size > $freeSpace) {
			$output->writeln('<error>Target user does not have enough free space available</error>');
			throw new \Exception('Execution terminated');
		}

		$output->writeln("Analysing files of $this->sourceUser ...");
		$progress = new ProgressBar($output);
		$progress->start();
		$self = $this;

		$this->walkFiles($view, $this->sourcePath,
				function (FileInfo $fileInfo) use ($progress, $self) {
					if ($fileInfo->getType() === FileInfo::TYPE_FOLDER) {
						// only analyze into folders from main storage,
						if (!$fileInfo->getStorage()->instanceOfStorage(IHomeStorage::class)) {
							return false;
						}
						return true;
					}
					$progress->advance();
					$this->allFiles[] = $fileInfo;
					if ($fileInfo->isEncrypted()) {
						$this->encryptedFiles[] = $fileInfo;
					}
					return true;
				});
		$progress->finish();
		$output->writeln('');

		// no file is allowed to be encrypted
		if (!empty($this->encryptedFiles)) {
			$output->writeln("<error>Some files are encrypted - please decrypt them first</error>");
			foreach($this->encryptedFiles as $encryptedFile) {
				/** @var FileInfo $encryptedFile */
				$output->writeln("  " . $encryptedFile->getPath());
			}
			throw new \Exception('Execution terminated.');
		}
	}

	/**
	 * @param OutputInterface $output
	 */
	private function collectUsersShares(OutputInterface $output) {
		$output->writeln("Collecting all share information for files and folder of $this->sourceUser ...");

		$progress = new ProgressBar($output, count($this->shares));
		foreach([\OCP\Share::SHARE_TYPE_GROUP, \OCP\Share::SHARE_TYPE_USER, \OCP\Share::SHARE_TYPE_LINK, \OCP\Share::SHARE_TYPE_REMOTE, \OCP\Share::SHARE_TYPE_ROOM] as $shareType) {
		$offset = 0;
			while (true) {
				$sharePage = $this->shareManager->getSharesBy($this->sourceUser, $shareType, null, true, 50, $offset);
				$progress->advance(count($sharePage));
				if (empty($sharePage)) {
					break;
				}
				$this->shares = array_merge($this->shares, $sharePage);
				$offset += 50;
			}
		}

		$progress->finish();
		$output->writeln('');
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function transfer(OutputInterface $output) {
		$view = new View();
		$output->writeln("Transferring files to $this->finalTarget ...");

		// This change will help user to transfer the folder specified using --path option.
		// Else only the content inside folder is transferred which is not correct.
		if($this->sourcePath !== "$this->sourceUser/files") {
			$view->mkdir($this->finalTarget);
			$this->finalTarget = $this->finalTarget . '/' . basename($this->sourcePath);
		}
		$view->rename($this->sourcePath, $this->finalTarget);
		if (!is_dir("$this->sourceUser/files")) {
			// because the files folder is moved away we need to recreate it
			$view->mkdir("$this->sourceUser/files");
		}
	}

	/**
	 * @param OutputInterface $output
	 */
	private function restoreShares(OutputInterface $output) {
		$output->writeln("Restoring shares ...");
		$progress = new ProgressBar($output, count($this->shares));

		foreach($this->shares as $share) {
			try {
				if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER &&
						$share->getSharedWith() === $this->destinationUser) {
					// Unmount the shares before deleting, so we don't try to get the storage later on.
					$shareMountPoint = $this->mountManager->find('/' . $this->destinationUser . '/files' . $share->getTarget());
					if ($shareMountPoint) {
						$this->mountManager->removeMount($shareMountPoint->getMountPoint());
					}
					$this->shareManager->deleteShare($share);
				} else {
					if ($share->getShareOwner() === $this->sourceUser) {
						$share->setShareOwner($this->destinationUser);
					}
					if ($share->getSharedBy() === $this->sourceUser) {
						$share->setSharedBy($this->destinationUser);
					}

					$this->shareManager->updateShare($share);
				}
			} catch (\OCP\Files\NotFoundException $e) {
				$output->writeln('<error>Share with id ' . $share->getId() . ' points at deleted file, skipping</error>');
			} catch (\Exception $e) {
				$output->writeln('<error>Could not restore share with id ' . $share->getId() . ':' . $e->getTraceAsString() . '</error>');
			}
			$progress->advance();
		}
		$progress->finish();
		$output->writeln('');
	}
}
