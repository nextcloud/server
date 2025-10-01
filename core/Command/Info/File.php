<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Info;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\Storage\Wrapper\Wrapper;
use OC\Files\View;
use OCA\Files_External\Config\ExternalMountPoint;
use OCA\GroupFolders\Mount\GroupMountPoint;
use OCP\Files\File as OCPFile;
use OCP\Files\Folder;
use OCP\Files\IHomeStorage;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class File extends Command {
	private IL10N $l10n;
	private View $rootView;

	public function __construct(
		IFactory $l10nFactory,
		private FileUtils $fileUtils,
		private \OC\Encryption\Util $encryptionUtil,
		private PrimaryObjectStoreConfig $objectStoreConfig,
	) {
		$this->l10n = $l10nFactory->get('core');
		parent::__construct();
		$this->rootView = new View();
	}

	protected function configure(): void {
		$this
			->setName('info:file')
			->setDescription('get information for a file')
			->addArgument('file', InputArgument::REQUIRED, 'File id or path')
			->addOption('children', 'c', InputOption::VALUE_NONE, 'List children of folders')
			->addOption('storage-tree', null, InputOption::VALUE_NONE, 'Show storage and cache wrapping tree');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileInput = $input->getArgument('file');
		$showChildren = $input->getOption('children');
		$node = $this->fileUtils->getNode($fileInput);
		if (!$node) {
			$output->writeln("<error>file $fileInput not found</error>");
			return 1;
		}

		$output->writeln($node->getName());
		$output->writeln('  fileid: ' . $node->getId());
		$output->writeln('  mimetype: ' . $node->getMimetype());
		$output->writeln('  modified: ' . (string)$this->l10n->l('datetime', $node->getMTime()));

		if ($node instanceof OCPFile && $node->isEncrypted()) {
			$output->writeln('  ' . 'server-side encrypted: yes');
			$keyPath = $this->encryptionUtil->getFileKeyDir('', $node->getPath());
			if ($this->rootView->file_exists($keyPath)) {
				$output->writeln('    encryption key at: ' . $keyPath);
			} else {
				$output->writeln('    <error>encryption key not found</error> should be located at: ' . $keyPath);
			}
			$storage = $node->getStorage();
			if ($storage->instanceOfStorage(Encryption::class)) {
				/** @var Encryption $storage */
				if (!$storage->hasValidHeader($node->getInternalPath())) {
					$output->writeln('    <error>file doesn\'t have a valid encryption header</error>');
				}
			} else {
				$output->writeln('    <error>file is marked as encrypted, but encryption doesn\'t seem to be setup</error>');
			}
		}

		if ($node instanceof Folder && $node->isEncrypted() || $node instanceof OCPFile && $node->getParent()->isEncrypted()) {
			$output->writeln('  ' . 'end-to-end encrypted: yes');
		}

		$output->writeln('  size: ' . Util::humanFileSize($node->getSize()));
		$output->writeln('  etag: ' . $node->getEtag());
		$output->writeln('  permissions: ' . $this->fileUtils->formatPermissions($node->getType(), $node->getPermissions()));
		if ($node instanceof Folder) {
			$children = $node->getDirectoryListing();
			$childSize = array_sum(array_map(function (Node $node) {
				return $node->getSize();
			}, $children));
			if ($childSize != $node->getSize()) {
				$output->writeln('    <error>warning: folder has a size of ' . Util::humanFileSize($node->getSize()) . " but it's children sum up to " . Util::humanFileSize($childSize) . '</error>.');
				if (!$node->getStorage()->instanceOfStorage(ObjectStoreStorage::class)) {
					$output->writeln('    Run <info>occ files:scan --path ' . $node->getPath() . '</info> to attempt to resolve this.');
				}
			}
			if ($showChildren) {
				$output->writeln('  children: ' . count($children) . ':');
				foreach ($children as $child) {
					$output->writeln('  - ' . $child->getName());
				}
			} else {
				$output->writeln('  children: ' . count($children) . ' (use <info>--children</info> option to list)');
			}
		}
		$this->outputStorageDetails($node->getMountPoint(), $node, $input, $output);

		$filesPerUser = $this->fileUtils->getFilesByUser($node);
		$output->writeln('');
		$output->writeln('The following users have access to the file');
		$output->writeln('');
		foreach ($filesPerUser as $user => $files) {
			$output->writeln("$user:");
			foreach ($files as $userFile) {
				$output->writeln('  ' . $userFile->getPath() . ': ' . $this->fileUtils->formatPermissions($userFile->getType(), $userFile->getPermissions()));
				$mount = $userFile->getMountPoint();
				$output->writeln('    ' . $this->fileUtils->formatMountType($mount));
			}
		}

		return 0;
	}

	/**
	 * @psalm-suppress UndefinedClass
	 * @psalm-suppress UndefinedInterfaceMethod
	 */
	private function outputStorageDetails(IMountPoint $mountPoint, Node $node, InputInterface $input, OutputInterface $output): void {
		$storage = $mountPoint->getStorage();
		if (!$storage) {
			return;
		}
		if (!$storage->instanceOfStorage(IHomeStorage::class)) {
			$output->writeln('  mounted at: ' . $mountPoint->getMountPoint());
		}
		if ($storage->instanceOfStorage(ObjectStoreStorage::class)) {
			/** @var ObjectStoreStorage $storage */
			$objectStoreId = $storage->getObjectStore()->getStorageId();
			$parts = explode(':', $objectStoreId);
			/** @var string $bucket */
			$bucket = array_pop($parts);
			if ($this->objectStoreConfig->hasMultipleObjectStorages()) {
				$configs = $this->objectStoreConfig->getObjectStoreConfigs();
				foreach ($configs as $instance => $config) {
					if (is_array($config)) {
						if ($config['arguments']['multibucket']) {
							if (str_starts_with($bucket, $config['arguments']['bucket'])) {
								$postfix = substr($bucket, strlen($config['arguments']['bucket']));
								if (is_numeric($postfix)) {
									$output->writeln('  object store instance: ' . $instance);
								}
							}
						} else {
							if ($config['arguments']['bucket'] === $bucket) {
								$output->writeln('  object store instance: ' . $instance);
							}
						}
					}
				}
			}
			$output->writeln('  bucket: ' . $bucket);
			if ($node instanceof \OC\Files\Node\File) {
				$output->writeln('  object id: ' . $storage->getURN($node->getId()));
				try {
					$fh = $node->fopen('r');
					if (!$fh) {
						throw new NotFoundException();
					}
					$stat = fstat($fh);
					fclose($fh);
					if (isset($stat['size']) && $stat['size'] !== $node->getSize()) {
						$output->writeln('  <error>warning: object had a size of ' . $stat['size'] . ' but cache entry has a size of ' . $node->getSize() . '</error>. This should have been automatically repaired');
					}
				} catch (\Exception $e) {
					$output->writeln('  <error>warning: object not found in bucket</error>');
				}
			}
		} else {
			if (!$storage->file_exists($node->getInternalPath())) {
				$output->writeln('  <error>warning: file not found in storage</error>');
			}
		}
		if ($mountPoint instanceof ExternalMountPoint) {
			$storageConfig = $mountPoint->getStorageConfig();
			$output->writeln('  external storage id: ' . $storageConfig->getId());
			$output->writeln('  external type: ' . $storageConfig->getBackend()->getText());
		} elseif ($mountPoint instanceof GroupMountPoint) {
			$output->writeln('  groupfolder id: ' . $mountPoint->getFolderId());
		}
		if ($input->getOption('storage-tree')) {
			$storageTmp = $storage;
			$storageClass = get_class($storageTmp) . ' (cache:' . get_class($storageTmp->getCache()) . ')';
			while ($storageTmp instanceof Wrapper) {
				$storageTmp = $storageTmp->getWrapperStorage();
				$storageClass .= "\n\t" . '> ' . get_class($storageTmp) . ' (cache:' . get_class($storageTmp->getCache()) . ')';
			}
			$output->writeln('  storage wrapping: ' . $storageClass);
		}

	}
}
