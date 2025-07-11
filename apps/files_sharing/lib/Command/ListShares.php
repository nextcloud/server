<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Command;

use OC\Core\Command\Base;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListShares extends Base {
	/** @var array<string, Node> */
	private array $fileCache = [];

	private const SHARE_TYPE_NAMES = [
		IShare::TYPE_USER => 'user',
		IShare::TYPE_GROUP => 'group',
		IShare::TYPE_LINK => 'link',
		IShare::TYPE_EMAIL => 'email',
		IShare::TYPE_REMOTE => 'remote',
		IShare::TYPE_REMOTE_GROUP => 'group',
		IShare::TYPE_ROOM => 'room',
		IShare::TYPE_DECK => 'deck',
	];

	public function __construct(
		private readonly IManager $shareManager,
		private readonly IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('share:list')
			->setDescription('List available shares')
			->addOption('owner', null, InputOption::VALUE_REQUIRED, 'only show shares owned by a specific user')
			->addOption('recipient', null, InputOption::VALUE_REQUIRED, 'only show shares with a specific recipient')
			->addOption('by', null, InputOption::VALUE_REQUIRED, 'only show shares with by as specific user')
			->addOption('file', null, InputOption::VALUE_REQUIRED, 'only show shares of a specific file')
			->addOption('parent', null, InputOption::VALUE_REQUIRED, 'only show shares of files inside a specific folder')
			->addOption('recursive', null, InputOption::VALUE_NONE, 'also show shares nested deep inside the specified parent folder')
			->addOption('type', null, InputOption::VALUE_REQUIRED, 'only show shares of a specific type')
			->addOption('status', null, InputOption::VALUE_REQUIRED, 'only show shares with a specific status');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('recursive') && !$input->getOption('parent')) {
			$output->writeln("<error>recursive option can't be used without parent option</error>");
			return 1;
		}

		// todo: do some pre-filtering instead of first querying all shares
		/** @var \Iterator<IShare> $allShares */
		$allShares = $this->shareManager->getAllShares();
		$shares = new \CallbackFilterIterator($allShares, function (IShare $share) use ($input) {
			return $this->shouldShowShare($input, $share);
		});
		$shares = iterator_to_array($shares);
		$data = array_map(function (IShare $share) {
			return [
				'id' => $share->getId(),
				'file' => $share->getNodeId(),
				'target-path' => $share->getTarget(),
				'source-path' => $share->getNode()->getPath(),
				'owner' => $share->getShareOwner(),
				'recipient' => $share->getSharedWith(),
				'by' => $share->getSharedBy(),
				'type' => self::SHARE_TYPE_NAMES[$share->getShareType()] ?? 'unknown',
			];
		}, $shares);

		$this->writeTableInOutputFormat($input, $output, $data);
		return 0;
	}

	private function getFileId(string $file): int {
		if (is_numeric($file)) {
			return (int)$file;
		}
		return $this->getFile($file)->getId();
	}

	private function getFile(string $file): Node {
		if (isset($this->fileCache[$file])) {
			return $this->fileCache[$file];
		}

		if (is_numeric($file)) {
			$node = $this->rootFolder->getFirstNodeById((int)$file);
			if (!$node) {
				throw new NotFoundException("File with id $file not found");
			}
		} else {
			$node = $this->rootFolder->get($file);
		}
		$this->fileCache[$file] = $node;
		return $node;
	}

	private function getShareType(string $type): int {
		foreach (self::SHARE_TYPE_NAMES as $shareType => $shareTypeName) {
			if ($shareTypeName === $type) {
				return $shareType;
			}
		}
		throw new \Exception("Unknown share type $type");
	}

	private function shouldShowShare(InputInterface $input, IShare $share): bool {
		if ($input->getOption('owner') && $share->getShareOwner() !== $input->getOption('owner')) {
			return false;
		}
		if ($input->getOption('recipient') && $share->getSharedWith() !== $input->getOption('recipient')) {
			return false;
		}
		if ($input->getOption('by') && $share->getSharedBy() !== $input->getOption('by')) {
			return false;
		}
		if ($input->getOption('file') && $share->getNodeId() !== $this->getFileId($input->getOption('file'))) {
			return false;
		}
		if ($input->getOption('parent')) {
			$parent = $this->getFile($input->getOption('parent'));
			if (!$parent instanceof Folder) {
				throw new \Exception("Parent {$parent->getPath()} is not a folder");
			}
			$recursive = $input->getOption('recursive');
			if (!$recursive) {
				$shareCacheEntry = $share->getNodeCacheEntry();
				if (!$shareCacheEntry) {
					$shareCacheEntry = $share->getNode();
				}
				if ($shareCacheEntry->getParentId() !== $parent->getId()) {
					return false;
				}
			} else {
				$shareNode = $share->getNode();
				if ($parent->getRelativePath($shareNode->getPath()) === null) {
					return false;
				}
			}
		}
		if ($input->getOption('type') && $share->getShareType() !== $this->getShareType($input->getOption('type'))) {
			return false;
		}
		return true;
	}
}
