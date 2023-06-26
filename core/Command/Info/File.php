<?php

declare(strict_types=1);

namespace OC\Core\Command\Info;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OCA\Files_External\Config\ExternalMountPoint;
use OCA\GroupFolders\Mount\GroupMountPoint;
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

	public function __construct(
		IFactory $l10nFactory,
		private FileUtils $fileUtils,
	) {
		$this->l10n = $l10nFactory->get("core");
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('info:file')
			->setDescription('get information for a file')
			->addArgument('file', InputArgument::REQUIRED, "File id or path")
			->addOption('children', 'c', InputOption::VALUE_NONE, "List children of folders");
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
		$output->writeln("  fileid: " . $node->getId());
		$output->writeln("  mimetype: " . $node->getMimetype());
		$output->writeln("  modified: " . (string)$this->l10n->l("datetime", $node->getMTime()));
		$output->writeln("  " . ($node->isEncrypted() ? "encrypted" : "not encrypted"));
		$output->writeln("  size: " . Util::humanFileSize($node->getSize()));
		$output->writeln("  etag: " . $node->getEtag());
		if ($node instanceof Folder) {
			$children = $node->getDirectoryListing();
			$childSize = array_sum(array_map(function (Node $node) {
				return $node->getSize();
			}, $children));
			if ($childSize != $node->getSize()) {
				$output->writeln("    <error>warning: folder has a size of " . Util::humanFileSize($node->getSize()) ." but it's children sum up to " . Util::humanFileSize($childSize) . "</error>.");
				$output->writeln("    Run <info>occ files:scan --path " . $node->getPath() . "</info> to attempt to resolve this.");
			}
			if ($showChildren) {
				$output->writeln("  children: " . count($children) . ":");
				foreach ($children as $child) {
					$output->writeln("  - " . $child->getName());
				}
			} else {
				$output->writeln("  children: " . count($children) . " (use <info>--children</info> option to list)");
			}
		}
		$this->outputStorageDetails($node->getMountPoint(), $node, $output);

		$filesPerUser = $this->fileUtils->getFilesByUser($node);
		$output->writeln("");
		$output->writeln("The following users have access to the file");
		$output->writeln("");
		foreach ($filesPerUser as $user => $files) {
			$output->writeln("$user:");
			foreach ($files as $userFile) {
				$output->writeln("  " . $userFile->getPath() . ": " . $this->fileUtils->formatPermissions($userFile->getType(), $userFile->getPermissions()));
				$mount = $userFile->getMountPoint();
				$output->writeln("    " . $this->fileUtils->formatMountType($mount));
			}
		}

		return 0;
	}

	/**
	 * @psalm-suppress UndefinedClass
	 * @psalm-suppress UndefinedInterfaceMethod
	 */
	private function outputStorageDetails(IMountPoint $mountPoint, Node $node, OutputInterface $output): void {
		$storage = $mountPoint->getStorage();
		if (!$storage) {
			return;
		}
		if (!$storage->instanceOfStorage(IHomeStorage::class)) {
			$output->writeln("  mounted at: " . $mountPoint->getMountPoint());
		}
		if ($storage->instanceOfStorage(ObjectStoreStorage::class)) {
			/** @var ObjectStoreStorage $storage */
			$objectStoreId = $storage->getObjectStore()->getStorageId();
			$parts = explode(':', $objectStoreId);
			/** @var string $bucket */
			$bucket = array_pop($parts);
			$output->writeln("  bucket: " . $bucket);
			if ($node instanceof \OC\Files\Node\File) {
				$output->writeln("  object id: " . $storage->getURN($node->getId()));
				try {
					$fh = $node->fopen('r');
					if (!$fh) {
						throw new NotFoundException();
					}
					$stat = fstat($fh);
					fclose($fh);
					if ($stat['size'] !== $node->getSize()) {
						$output->writeln("  <error>warning: object had a size of " . $stat['size'] . " but cache entry has a size of " . $node->getSize() . "</error>. This should have been automatically repaired");
					}
				} catch (\Exception $e) {
					$output->writeln("  <error>warning: object not found in bucket</error>");
				}
			}
		} else {
			if (!$storage->file_exists($node->getInternalPath())) {
				$output->writeln("  <error>warning: file not found in storage</error>");
			}
		}
		if ($mountPoint instanceof ExternalMountPoint) {
			$storageConfig = $mountPoint->getStorageConfig();
			$output->writeln("  external storage id: " . $storageConfig->getId());
			$output->writeln("  external type: " . $storageConfig->getBackend()->getText());
		} elseif ($mountPoint instanceof GroupMountPoint) {
			$output->writeln("  groupfolder id: " . $mountPoint->getFolderId());
		}
	}
}
