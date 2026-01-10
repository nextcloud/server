<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Service;

use Closure;
use Exception;
use OC\Files\Filesystem;
use OC\Files\View;
use OC\User\NoUserException;
use OCA\Encryption\Util;
use OCA\Files\Exception\TransferOwnershipException;
use OCA\Files_External\Config\ConfigAdapter;
use OCP\Encryption\IManager as IEncryptionManager;
use OCP\Files\Config\IHomeMountProvider;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\File;
use OCP\Files\FileInfo;
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
		bool $includeExternalStorage = false,
		bool $useUserId = false,
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

			if ($useUserId) {
				$cleanUserName = $sourceUid;
			} else {
				$cleanUserName = $this->sanitizeFolderName($sourceUser->getDisplayName());
				if ($cleanUserName === '') {
					$cleanUserName = $sourceUid;
				}
			}

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

		$sourceSize = $view->getFileInfo($sourcePath)->getSize();

		// transfer the files
		$this->transferFiles(
			$sourceUid,
			$sourcePath,
			$finalTarget,
			$view,
			$output,
			$includeExternalStorage,
		);
		$sizeDifference = $sourceSize - $view->getFileInfo($finalTarget)->getSize();

		// transfer the incoming shares
		$sourceShares = $this->collectIncomingShares(
			$sourceUid,
			$output,
			$sourcePath,
		);
		$destinationShares = $this->collectIncomingShares(
			$destinationUid,
			$output,
			null,
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

		$destinationPath = $finalTarget . '/' . $path;
		// restore the shares
		$this->restoreShares(
			$sourceUid,
			$destinationUid,
			$destinationPath,
			$shares,
			$output
		);
		if ($sizeDifference !== 0) {
			$output->writeln("Transferred folder have a size difference of: $sizeDifference Bytes which means the transfer may be incomplete. Please check the logs if there was any issue during the transfer operation.");
		}
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
	protected function analyse(
		string $sourceUid,
		string $destinationUid,
		string $sourcePath,
		View $view,
		OutputInterface $output,
		bool $includeExternalStorage = false,
	): void {
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
					function (FileInfo $fileInfo) use ($progress, $masterKeyEnabled, &$encryptedFiles, $includeExternalStorage) {
						if ($fileInfo->getType() === FileInfo::TYPE_FOLDER) {
							$mount = $fileInfo->getMountPoint();
							// only analyze into folders from main storage,
							if (
								$mount->getMountProvider() instanceof IHomeMountProvider
								|| ($includeExternalStorage && $mount->getMountProvider() instanceof ConfigAdapter)
							) {
								if ($fileInfo->isEncrypted()) {
									/* Encrypted folder means e2ee encrypted, we cannot transfer it */
									$encryptedFiles[] = $fileInfo;
								}
								return true;
							} else {
								return false;
							}
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
	 * Collects all outgoing shares owned by a user, optionally filtered by a given path.
	 *
	 * @param string $uid The unique Nextcloud user ID.
	 * @param OutputInterface $output Output interface for progress and messages.
	 * @param View $view File view object for resolving paths.
	 * @param ?string $filterPath The path to filter shares, relative to the user's files root.
	 *        If null or the user's files root, collects all outgoing shares.
	 *
	 * @return array<array{share: IShare, suffix: string}>	Indexed array of arrays, each containing:
	 *														- share: the outgoing IShare object
	 *														- suffix: the item's subpath relative to the filter path ('' if not filtered)
	 */
	private function collectUsersShares(
		string $uid,
		OutputInterface $output,
		View $view,
		?string $filterPath,
	): array {
		$output->writeln("Collecting outgoing shares for user $uid...");

		$allShares = [];
		$batchSize = 500;
		$userRootPath = "$uid/files";
		$shouldFilter = $filterPath !== null && $filterPath !== $userRootPath;

		$supportedShareTypes = [
			IShare::TYPE_GROUP		=> 'Group',
			IShare::TYPE_USER		=> 'User',
			IShare::TYPE_LINK		=> 'Public Link',
			IShare::TYPE_REMOTE		=> 'Remote',
			IShare::TYPE_ROOM		=> 'Room',
			IShare::TYPE_EMAIL		=> 'Mail Link',
			IShare::TYPE_CIRCLE		=> 'Team',
			IShare::TYPE_DECK		=> 'Deck',
		];

		$progress = new ProgressBar($output);

		foreach ($supportedShareTypes as $shareType => $label) {
			$output->writeln("Collecting outgoing shares of type: $label ...");
			$offset = 0;
			while (true) {
				$sharePage = $this->shareManager->getSharesBy($uid, $shareType, null, true, $batchSize, $offset, onlyValid: false);
				$progress->advance(count($sharePage));
				if (empty($sharePage)) {
					break;
				}
				foreach ($sharePage as $share) {
					try {
						$nodePath = $view->getRelativePath($share->getNode()->getPath());
						// TODO: Non-filesystem shares like TYPE_DECK do not have paths and will be filtered out here for subfolders.
						// 		This leads to inconsistent handling depending on the $filterPath argument. 
						//		Consider revising logic if non-file shares should always be included.
						if (!$shouldFilter || $this->isShareWithinPath($nodePath, $filterPath)) {
							$normalizedSharePath = Filesystem::normalizePath($nodePath);
							$normalizedFilterPath = Filesystem::normalizePath($filterPath);
							$suffix = $shouldFilter
								? substr($normalizedSharePath, strlen($normalizedFilterPath))
								: '';
							 $allShares[] = [
								 'share' => $share,
								 'suffix' => $suffix,
							];
						}
					} catch (NotFoundException $e) {
						$output->writeln("<error>Failed to find path for shared file {$share->getNodeId()} for user $uid, skipping</error>");
					}
				}
				$offset += $batchSize;
			}
		}
		$progress->finish();
		$output->writeln('');
		return array_values($allShares);
	}

	/**
	 * Collect all incoming shares for a user, optionally filtered by a specific path.
	 *
	 * @param string $uid The unique Nextcloud user ID.
	 * @param OutputInterface $output Output interface for progress and messages.
	 * @param ?string $filterPath The path to filter shares, relative to the user's files root.
	 *        If null or the user's files root, collects all incoming shares.
	 *
	 * @return array<string, IShare> Associative array mapping share nodeId to IShare object.
	 */
	private function collectIncomingShares(
		string $uid,
		OutputInterface $output,
		?string $filterPath,
	): array {
		$output->writeln("Collecting incoming shares for user $uid...");

		$shares = [];
		$batchSize = 500;
		$userRootPath = "$uid/files";
		$shouldFilter = $filterPath !== null && $filterPath !== $userRootPath;

		$supportedShareTypes = [
			IShare::TYPE_USER		=> 'User',
		];

		$progress = new ProgressBar($output);

		foreach ($supportedShareTypes as $shareType => $label) {
			$output->writeln("Collecting incoming shares of type: $label ...");
			$offset = 0;
			while (true) {
				$sharePage = $this->shareManager->getSharedWith($uid, $shareType, null, $batchSize, $offset);
				$progress->advance(count($sharePage));
				if (empty($sharePage)) {
					break;
				}
				foreach ($sharePage as $share) {
					// For incoming: target is relative to user files root
					$shareFullPath = rtrim($userRootPath, '/') . '/' . ltrim($share->getTarget(), '/');
					if (!$shouldFilter || $this->isShareWithinPath($shareFullPath, $filterPath)) {
						$shares[$share->getNodeId()] = $share;
					}
				}
				$offset += $batchSize;
			}
		}

		$progress->finish();
		$output->writeln('');
		return $shares;
	}

	/**
	 * Determine if a share's full user-rooted path is within the given filter path
	 * (normalizing both).
	 * 
	 * @param string $shareFullPath The absolute user-rooted path to the shared item, e.g. 'uid/files/foo/bar.txt'
	 * @param ?string $filterPath The filter path or null to match all.
	 */
	private function isShareWithinPath(
		string $shareFullPath,
		?string $filterPath,
	): bool {
		if ($filterPath === null) {
			return true;
		}
		$normalizedSharePath = Filesystem::normalizePath($shareFullPath);
		$normalizedFilterPath = Filesystem::normalizePath($filterPath);
		return str_starts_with($normalizedSharePath . '/', $normalizedFilterPath . '/');
	}

	/**
	 * @throws TransferOwnershipException
	 */
	protected function transferFiles(
		string $sourceUid,
		string $sourcePath,
		string $finalTarget,
		View $view,
		OutputInterface $output,
		bool $includeExternalStorage,
	): void {
		$output->writeln("Transferring files to $finalTarget ...");

		// This change will help user to transfer the folder specified using --path option.
		// Else only the content inside folder is transferred which is not correct.
		if ($sourcePath !== "$sourceUid/files") {
			$view->mkdir($finalTarget);
			$finalTarget = $finalTarget . '/' . basename($sourcePath);
		}
		$sourceInfo = $view->getFileInfo($sourcePath);

		/// handle the external storages mounted at the root, or the admin specifying an external storage with --path
		if ($sourceInfo->getInternalPath() === '' && $includeExternalStorage) {
			$this->moveMountContents($view, $sourcePath, $finalTarget);
		} else {
			if ($view->rename($sourcePath, $finalTarget, ['checkSubMounts' => false]) === false) {
				throw new TransferOwnershipException('Could not transfer files.', 1);
			}
		}

		if ($includeExternalStorage) {
			$nestedMounts = $this->mountManager->findIn($sourcePath);
			foreach ($nestedMounts as $mount) {
				if ($mount->getMountProvider() === ConfigAdapter::class) {
					$relativePath = substr(trim($mount->getMountPoint(), '/'), strlen($sourcePath));
					$this->moveMountContents($view, $mount->getMountPoint(), $finalTarget . $relativePath);
				}
			}
		}

		if (!is_dir("$sourceUid/files")) {
			// because the files folder is moved away we need to recreate it
			$view->mkdir("$sourceUid/files");
		}
	}

	private function moveMountContents(View $rootView, string $source, string $target) {
		if ($rootView->copy($source, $target)) {
			// just doing `rmdir` on the mountpoint would cause it to try and unmount the storage
			// we need to empty the contents instead
			$content = $rootView->getDirectoryContent($source);
			foreach ($content as $item) {
				if ($item->getType() === FileInfo::TYPE_FOLDER) {
					$rootView->rmdir($item->getPath());
				} else {
					$rootView->unlink($item->getPath());
				}
			}
		} else {
			throw new TransferOwnershipException("Could not transfer $source to $target");
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
				if ($share->getShareType() === IShare::TYPE_USER
					&& $share->getSharedWith() === $destinationUid) {
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

					if ($share->getShareType() === IShare::TYPE_USER
						&& !$this->userManager->userExists($share->getSharedWith())) {
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
				if ($share->getShareType() === IShare::TYPE_USER
					&& $share->getSharedBy() === $destinationUid) {
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
