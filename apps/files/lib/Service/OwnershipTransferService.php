<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Service;

use Closure;
use OC\Files\Filesystem;
use OC\Files\View;
use OC\User\NoUserException;
use OCA\Encryption\Util;
use OCA\Files\Exception\TransferOwnershipException;
use OCP\Encryption\IManager as IEncryptionManager;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\FileInfo;
use OCP\Files\IHomeStorage;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use function array_merge;
use function basename;
use function count;
use function date;
use function is_dir;
use function rtrim;

class OwnershipTransferService {

	public function __construct(
		private IEncryptionManager $encryptionManager,
		private IShareManager $shareManager,
		private IMountManager $mountManager,
		private IUserMountCache $userMountCache,
		private IUserManager $userManager,
		private IFactory $l10nFactory,
		private IRootFolder $rootFolder,
	) {
	}

	/**
	 * @param IUser $sourceUser
	 * @param IUser $destinationUser
	 * @param string $path
	 *
	 * @param OutputInterface|null $output
	 * @param bool $move
	 * @throws TransferOwnershipException
	 * @throws NoUserException
	 */
	public function transfer(
		IUser $sourceUser,
		IUser $destinationUser,
		string $path,
		?OutputInterface $output = null,
		bool $move = false,
		bool $firstLogin = false,
		bool $transferIncomingShares = false,
	): void {
		$output = $output ?? new NullOutput();
		$sourceUid = $sourceUser->getUID();
		$destinationUid = $destinationUser->getUID();
		$sourcePath = rtrim($sourceUid . '/files/' . $path, '/');

		// If encryption is on we have to ensure the user has logged in before and that all encryption modules are ready
		if (($this->encryptionManager->isEnabled() && $destinationUser->getLastLogin() === 0)
			|| !$this->encryptionManager->isReadyForUser($destinationUid)) {
			throw new TransferOwnershipException('The target user is not ready to accept files. The user has at least to have logged in once.', 2);
		}

		// setup filesystem
		// Requesting the user folder will set it up if the user hasn't logged in before
		// We need a setupFS for the full filesystem setup before as otherwise we will just return
		// a lazy root folder which does not create the destination users folder
		\OC_Util::setupFS($sourceUser->getUID());
		\OC_Util::setupFS($destinationUser->getUID());
		$this->rootFolder->getUserFolder($sourceUser->getUID());
		$this->rootFolder->getUserFolder($destinationUser->getUID());
		Filesystem::initMountPoints($sourceUid);
		Filesystem::initMountPoints($destinationUid);

		$view = new View();

		if ($move) {
			$finalTarget = "$destinationUid/files/";
		} else {
			$l = $this->l10nFactory->get('files', $this->l10nFactory->getUserLanguage($destinationUser));
			$date = date('Y-m-d H-i-s');

			$cleanUserName = $this->sanitizeFolderName($sourceUser->getDisplayName()) ?: $sourceUid;
			$finalTarget = "$destinationUid/files/" . $this->sanitizeFolderName($l->t('Transferred from %1$s on %2$s', [$cleanUserName, $date]));
			try {
				$view->verifyPath(dirname($finalTarget), basename($finalTarget));
			} catch (InvalidPathException $e) {
				$finalTarget = "$destinationUid/files/" . $this->sanitizeFolderName($l->t('Transferred from %1$s on %2$s', [$sourceUid, $date]));
			}
		}

		if (!($view->is_dir($sourcePath) || $view->is_file($sourcePath))) {
			throw new TransferOwnershipException("Unknown path provided: $path", 1);
		}

		if ($move && !$view->is_dir($finalTarget)) {
			// Initialize storage
			\OC_Util::setupFS($destinationUser->getUID());
		}

		if ($move && !$firstLogin && count($view->getDirectoryContent($finalTarget)) > 0) {
			throw new TransferOwnershipException('Destination path does not exists or is not empty', 1);
		}


		// analyse source folder
		$this->analyse(
			$sourceUid,
			$destinationUid,
			$sourcePath,
			$view,
			$output
		);

		// collect all the shares
		$shares = $this->collectUsersShares(
			$sourceUid,
			$output,
			$view,
			$sourcePath
		);

		// transfer the files
		$this->transferFiles(
			$sourceUid,
			$sourcePath,
			$finalTarget,
			$view,
			$output
		);

		// transfer the incoming shares
		if ($transferIncomingShares === true) {
			$sourceShares = $this->collectIncomingShares(
				$sourceUid,
				$output,
				$view
			);
			$destinationShares = $this->collectIncomingShares(
				$destinationUid,
				$output,
				$view,
				true
			);
			$this->transferIncomingShares(
				$sourceUid,
				$destinationUid,
				$sourceShares,
				$destinationShares,
				$output,
				$path,
				$finalTarget,
				$move
			);
		}

		$destinationPath = $finalTarget . '/' . $path;
		// restore the shares
		$this->restoreShares(
			$sourceUid,
			$destinationUid,
			$destinationPath,
			$shares,
			$output
		);
	}

	private function sanitizeFolderName(string $name): string {
		// Remove some characters which are prone to cause errors
		$name = str_replace(['\\', '/', ':', '.', '?', '#', '\'', '"'], '-', $name);
		// Replace multiple dashes with one dash
		return preg_replace('/-{2,}/s', '-', $name);
	}

	private function walkFiles(View $view, $path, Closure $callBack) {
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
	 *
	 * @throws TransferOwnershipException
	 */
	protected function analyse(string $sourceUid,
		string $destinationUid,
		string $sourcePath,
		View $view,
		OutputInterface $output): void {
		$output->writeln('Validating quota');
		$sourceFileInfo = $view->getFileInfo($sourcePath, false);
		if ($sourceFileInfo === false) {
			throw new TransferOwnershipException("Unknown path provided: $sourcePath", 1);
		}
		$size = $sourceFileInfo->getSize(false);
		$freeSpace = $view->free_space($destinationUid . '/files/');
		if ($size > $freeSpace && $freeSpace !== FileInfo::SPACE_UNKNOWN) {
			throw new TransferOwnershipException('Target user does not have enough free space available.', 1);
		}

		$output->writeln("Analysing files of $sourceUid ...");
		$progress = new ProgressBar($output);
		$progress->start();

		if ($this->encryptionManager->isEnabled()) {
			$masterKeyEnabled = Server::get(Util::class)->isMasterKeyEnabled();
		} else {
			$masterKeyEnabled = false;
		}
		$encryptedFiles = [];
		if ($sourceFileInfo->getType() === FileInfo::TYPE_FOLDER) {
			if ($sourceFileInfo->isEncrypted()) {
				/* Encrypted folder means e2ee encrypted */
				$encryptedFiles[] = $sourceFileInfo;
			} else {
				$this->walkFiles($view, $sourcePath,
					function (FileInfo $fileInfo) use ($progress, $masterKeyEnabled, &$encryptedFiles) {
						if ($fileInfo->getType() === FileInfo::TYPE_FOLDER) {
							// only analyze into folders from main storage,
							if (!$fileInfo->getStorage()->instanceOfStorage(IHomeStorage::class)) {
								return false;
							}
							if ($fileInfo->isEncrypted()) {
								/* Encrypted folder means e2ee encrypted, we cannot transfer it */
								$encryptedFiles[] = $fileInfo;
							}
							return true;
						}
						$progress->advance();
						if ($fileInfo->isEncrypted() && !$masterKeyEnabled) {
							/* Encrypted file means SSE, we can only transfer it if master key is enabled */
							$encryptedFiles[] = $fileInfo;
						}
						return true;
					});
			}
		} elseif ($sourceFileInfo->isEncrypted() && !$masterKeyEnabled) {
			/* Encrypted file means SSE, we can only transfer it if master key is enabled */
			$encryptedFiles[] = $sourceFileInfo;
		}
		$progress->finish();
		$output->writeln('');

		// no file is allowed to be encrypted
		if (!empty($encryptedFiles)) {
			$output->writeln('<error>Some files are encrypted - please decrypt them first.</error>');
			foreach ($encryptedFiles as $encryptedFile) {
				/** @var FileInfo $encryptedFile */
				$output->writeln('  ' . $encryptedFile->getPath());
			}
			throw new TransferOwnershipException('Some files are encrypted - please decrypt them first.', 1);
		}
	}

	/**
	 * @return array<array{share: IShare, suffix: string}>
	 */
	private function collectUsersShares(
		string $sourceUid,
		OutputInterface $output,
		View $view,
		string $path,
	): array {
		$output->writeln("Collecting all share information for files and folders of $sourceUid ...");

		$shares = [];
		$progress = new ProgressBar($output);

		$normalizedPath = Filesystem::normalizePath($path);

		$supportedShareTypes = [
			IShare::TYPE_GROUP,
			IShare::TYPE_USER,
			IShare::TYPE_LINK,
			IShare::TYPE_REMOTE,
			IShare::TYPE_ROOM,
			IShare::TYPE_EMAIL,
			IShare::TYPE_CIRCLE,
			IShare::TYPE_DECK,
			IShare::TYPE_SCIENCEMESH,
		];

		foreach ($supportedShareTypes as $shareType) {
			$offset = 0;
			while (true) {
				$sharePage = $this->shareManager->getSharesBy($sourceUid, $shareType, null, true, 50, $offset, onlyValid: false);
				$progress->advance(count($sharePage));
				if (empty($sharePage)) {
					break;
				}
				if ($path !== "$sourceUid/files") {
					$sharePage = array_filter($sharePage, function (IShare $share) use ($view, $normalizedPath) {
						try {
							$relativePath = $view->getPath($share->getNodeId());
							$singleFileTranfer = $view->is_file($normalizedPath);
							if ($singleFileTranfer) {
								return Filesystem::normalizePath($relativePath) === $normalizedPath;
							}

							return mb_strpos(
								Filesystem::normalizePath($relativePath . '/', false),
								$normalizedPath . '/') === 0;
						} catch (\Exception $e) {
							return false;
						}
					});
				}
				$shares = array_merge($shares, $sharePage);
				$offset += 50;
			}
		}

		$progress->finish();
		$output->writeln('');

		return array_values(array_filter(array_map(function (IShare $share) use ($view, $normalizedPath, $output, $sourceUid) {
			try {
				$nodePath = $view->getPath($share->getNodeId());
			} catch (NotFoundException $e) {
				$output->writeln("<error>Failed to find path for shared file {$share->getNodeId()} for user $sourceUid, skipping</error>");
				return null;
			}

			return [
				'share' => $share,
				'suffix' => substr(Filesystem::normalizePath($nodePath), strlen($normalizedPath)),
			];
		}, $shares)));
	}

	private function collectIncomingShares(string $sourceUid,
		OutputInterface $output,
		View $view,
		bool $addKeys = false): array {
		$output->writeln("Collecting all incoming share information for files and folders of $sourceUid ...");

		$shares = [];
		$progress = new ProgressBar($output);

		$offset = 0;
		while (true) {
			$sharePage = $this->shareManager->getSharedWith($sourceUid, IShare::TYPE_USER, null, 50, $offset);
			$progress->advance(count($sharePage));
			if (empty($sharePage)) {
				break;
			}
			if ($addKeys) {
				foreach ($sharePage as $singleShare) {
					$shares[$singleShare->getNodeId()] = $singleShare;
				}
			} else {
				foreach ($sharePage as $singleShare) {
					$shares[] = $singleShare;
				}
			}

			$offset += 50;
		}


		$progress->finish();
		$output->writeln('');
		return $shares;
	}

	/**
	 * @throws TransferOwnershipException
	 */
	protected function transferFiles(string $sourceUid,
		string $sourcePath,
		string $finalTarget,
		View $view,
		OutputInterface $output): void {
		$output->writeln("Transferring files to $finalTarget ...");

		// This change will help user to transfer the folder specified using --path option.
		// Else only the content inside folder is transferred which is not correct.
		if ($sourcePath !== "$sourceUid/files") {
			$view->mkdir($finalTarget);
			$finalTarget = $finalTarget . '/' . basename($sourcePath);
		}
		if ($view->rename($sourcePath, $finalTarget, ['checkSubMounts' => false]) === false) {
			throw new TransferOwnershipException('Could not transfer files.', 1);
		}
		if (!is_dir("$sourceUid/files")) {
			// because the files folder is moved away we need to recreate it
			$view->mkdir("$sourceUid/files");
		}
	}

	/**
	 * @param string $targetLocation New location of the transfered node
	 * @param array<array{share: IShare, suffix: string}> $shares previously collected share information
	 */
	private function restoreShares(
		string $sourceUid,
		string $destinationUid,
		string $targetLocation,
		array $shares,
		OutputInterface $output,
	):void {
		$output->writeln('Restoring shares ...');
		$progress = new ProgressBar($output, count($shares));

		foreach ($shares as ['share' => $share, 'suffix' => $suffix]) {
			try {
				$output->writeln('Transfering share ' . $share->getId() . ' of type ' . $share->getShareType(), OutputInterface::VERBOSITY_VERBOSE);
				if ($share->getShareType() === IShare::TYPE_USER &&
					$share->getSharedWith() === $destinationUid) {
					// Unmount the shares before deleting, so we don't try to get the storage later on.
					$shareMountPoint = $this->mountManager->find('/' . $destinationUid . '/files' . $share->getTarget());
					if ($shareMountPoint) {
						$this->mountManager->removeMount($shareMountPoint->getMountPoint());
					}
					$this->shareManager->deleteShare($share);
				} else {
					if ($share->getShareOwner() === $sourceUid) {
						$share->setShareOwner($destinationUid);
					}
					if ($share->getSharedBy() === $sourceUid) {
						$share->setSharedBy($destinationUid);
					}

					if ($share->getShareType() === IShare::TYPE_USER &&
						!$this->userManager->userExists($share->getSharedWith())) {
						// stray share with deleted user
						$output->writeln('<error>Share with id ' . $share->getId() . ' points at deleted user "' . $share->getSharedWith() . '", deleting</error>');
						$this->shareManager->deleteShare($share);
						continue;
					} else {
						// trigger refetching of the node so that the new owner and mountpoint are taken into account
						// otherwise the checks on the share update will fail due to the original node not being available in the new user scope
						$this->userMountCache->clear();

						try {
							// Try to get the "old" id.
							// Normally the ID is preserved,
							// but for transferes between different storages the ID might change
							$newNodeId = $share->getNode()->getId();
						} catch (NotFoundException) {
							// ID has changed due to transfer between different storages
							// Try to get the new ID from the target path and suffix of the share
							$node = $this->rootFolder->get(Filesystem::normalizePath($targetLocation . '/' . $suffix));
							$newNodeId = $node->getId();
							$output->writeln('Had to change node id to ' . $newNodeId, OutputInterface::VERBOSITY_VERY_VERBOSE);
						}
						$share->setNodeId($newNodeId);

						$this->shareManager->updateShare($share, onlyValid: false);
					}
				}
			} catch (NotFoundException $e) {
				$output->writeln('<error>Share with id ' . $share->getId() . ' points at deleted file, skipping</error>');
			} catch (\Throwable $e) {
				$output->writeln('<error>Could not restore share with id ' . $share->getId() . ':' . $e->getMessage() . ' : ' . $e->getTraceAsString() . '</error>');
			}
			$progress->advance();
		}
		$progress->finish();
		$output->writeln('');
	}

	private function transferIncomingShares(string $sourceUid,
		string $destinationUid,
		array $sourceShares,
		array $destinationShares,
		OutputInterface $output,
		string $path,
		string $finalTarget,
		bool $move): void {
		$output->writeln('Restoring incoming shares ...');
		$progress = new ProgressBar($output, count($sourceShares));
		$prefix = "$destinationUid/files";
		$finalShareTarget = '';
		if (str_starts_with($finalTarget, $prefix)) {
			$finalShareTarget = substr($finalTarget, strlen($prefix));
		}
		foreach ($sourceShares as $share) {
			try {
				// Only restore if share is in given path.
				$pathToCheck = '/';
				if (trim($path, '/') !== '') {
					$pathToCheck = '/' . trim($path) . '/';
				}
				if (!str_starts_with($share->getTarget(), $pathToCheck)) {
					continue;
				}
				$shareTarget = $share->getTarget();
				$shareTarget = $finalShareTarget . $shareTarget;
				if ($share->getShareType() === IShare::TYPE_USER &&
					$share->getSharedBy() === $destinationUid) {
					$this->shareManager->deleteShare($share);
				} elseif (isset($destinationShares[$share->getNodeId()])) {
					$destinationShare = $destinationShares[$share->getNodeId()];
					// Keep the share which has the most permissions and discard the other one.
					if ($destinationShare->getPermissions() < $share->getPermissions()) {
						$this->shareManager->deleteShare($destinationShare);
						$share->setSharedWith($destinationUid);
						// trigger refetching of the node so that the new owner and mountpoint are taken into account
						// otherwise the checks on the share update will fail due to the original node not being available in the new user scope
						$this->userMountCache->clear();
						$share->setNodeId($share->getNode()->getId());
						$this->shareManager->updateShare($share);
						// The share is already transferred.
						$progress->advance();
						if ($move) {
							continue;
						}
						$share->setTarget($shareTarget);
						$this->shareManager->moveShare($share, $destinationUid);
						continue;
					}
					$this->shareManager->deleteShare($share);
				} elseif ($share->getShareOwner() === $destinationUid) {
					$this->shareManager->deleteShare($share);
				} else {
					$share->setSharedWith($destinationUid);
					$share->setNodeId($share->getNode()->getId());
					$this->shareManager->updateShare($share);
					// trigger refetching of the node so that the new owner and mountpoint are taken into account
					// otherwise the checks on the share update will fail due to the original node not being available in the new user scope
					$this->userMountCache->clear();
					// The share is already transferred.
					$progress->advance();
					if ($move) {
						continue;
					}
					$share->setTarget($shareTarget);
					$this->shareManager->moveShare($share, $destinationUid);
					continue;
				}
			} catch (NotFoundException $e) {
				$output->writeln('<error>Share with id ' . $share->getId() . ' points at deleted file, skipping</error>');
			} catch (\Throwable $e) {
				$output->writeln('<error>Could not restore share with id ' . $share->getId() . ':' . $e->getTraceAsString() . '</error>');
			}
			$progress->advance();
		}
		$progress->finish();
		$output->writeln('');
	}
}
