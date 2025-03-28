<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Command;

use OC\Core\Command\Base;
use OC\Core\Command\InterruptedException;
use OC\Files\Node\Node;
use OC\FilesMetadata\FilesMetadataManager;
use OC\ForbiddenException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListFiles extends Base {
	protected array $fileInfo = [];
	protected array $dirInfo = [];
	public function __construct(
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private FilesMetadataManager $filesMetadataManager,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this->setName("files:list")
			->setDescription("List files of the user and filter by path, type, size optionally")
			->addArgument(
				"user_id",
				InputArgument::REQUIRED,
				'List the files and folder belonging to the user, eg occ files:list admin, the user_id being a required argument'
			)
			->addOption("path", "", InputArgument::OPTIONAL, "List files inside a particular path of the user, if not mentioned list from user's root directory")
			->addOption("type", "", InputArgument::OPTIONAL, "Filter by type like application, image, video etc")
			->addOption(
				"minSize",
				"0",
				InputArgument::OPTIONAL,
				"Filter by min size"
			)
			->addOption(
				"maxSize",
				"0",
				InputArgument::OPTIONAL,
				"Filter by max size"
			)
			->addOption(
				"sort",
				"name",
				InputArgument::OPTIONAL,
				"Sort by name, path, size, owner, type, perm, created-at"
			)
			->addOption("order", "ASC", InputArgument::OPTIONAL, "Order is either ASC or DESC");
	}

	private function getNodeInfo(Node $node): array {
		$nodeInfo = [
			"name" => $node->getName(),
			"size" => \OCP\Util::humanFileSize($node->getSize()),
			"realSize" => $node->getSize(),
			"perm" => $node->getPermissions(),
			"owner" => $node->getOwner()?->getDisplayName(),
			"created-at" => $node->getCreationTime(),
			"type" => $node->getMimePart(),
		];
		if($node->getMimetype() == FileInfo::MIMETYPE_FOLDER) {
			$nodeInfo['type'] = 'directory';
		}

		return $nodeInfo;
	}

	protected function listFiles(
		string $user,
		OutputInterface $output,
		?string $path = "",
		?string $type = "",
		?int $minSize = 0,
		?int $maxSize = 0
	): void {
		try {
			$userFolder = $this->rootFolder->getUserFolder($user);
			/** @var Folder $pathList **/
			$pathList = $userFolder->get('/' . $path);

			$files = $pathList->getDirectoryListing();
			foreach ($files as $file) {
				/** @var Node $fileNode */
				$fileNode = $file;
				$includeType = $includeMin = $includeMax = true;
				$nodeInfo = $this->getNodeInfo($fileNode);
				if ($type != "" && $type !== $nodeInfo['type']) {
					$includeType = false;
				}
				if ($minSize > 0) {
					$includeMin = $file->getSize() >= $minSize;
				}
				if ($maxSize > 0) {
					$includeMax = $file->getSize() <= $maxSize;
				}
				if ($file instanceof File) {
					if ($includeType && $includeMin && $includeMax) {
						$this->fileInfo[] = $nodeInfo;
					}
				} elseif ($file instanceof Folder) {
					if ($includeType && $includeMin && $includeMax) {
						$this->dirInfo[] = $nodeInfo;
					}
				}
			}
		} catch (ForbiddenException $e) {
			$output->writeln(
				"<error>Home storage for user $user not writable or 'files' subdirectory missing</error>"
			);
			$output->writeln("  " . $e->getMessage());
			$output->writeln(
				'Make sure you\'re running the list command only as the user the web server runs as'
			);
		} catch (InterruptedException $e) {
			# exit the function if ctrl-c has been pressed
			$output->writeln("Interrupted by user");
		} catch (NotFoundException $e) {
			$output->writeln(
				"<error>Path not found: " . $e->getMessage() . "</error>"
			);
		} catch (\Exception $e) {
			$output->writeln(
				"<error>Exception during list: " . $e->getMessage() . "</error>"
			);
			$output->writeln("<error>" . $e->getTraceAsString() . "</error>");
		}
	}

	protected function execute(
		InputInterface $input,
		OutputInterface $output
	): int {
		$user = $input->getArgument("user_id");
		$this->initTools($output);

		if ($this->userManager->userExists($user)) {
			$output->writeln(
				"Starting list for user ($user)"
			);
			$this->listFiles(
				$user,
				$output,
				(string) $input->getOption("path") ? $input->getOption("path") : '',
				$input->getOption("type"),
				(int) $input->getOption("minSize"),
				(int) $input->getOption("maxSize")
			);
		} else {
			$output->writeln(
				"<error>Unknown user $user</error>"
			);
			$output->writeln("", OutputInterface::VERBOSITY_VERBOSE);
			return self::FAILURE;
		}

		$this->presentStats($input, $output);
		return self::SUCCESS;
	}

	/**
	 * Initialises some useful tools for the Command
	 */
	protected function initTools(OutputInterface $output): void {
		// Convert PHP errors to exceptions
		set_error_handler(
			fn (
				int $severity,
				string $message,
				string $file,
				int $line
			): bool => $this->exceptionErrorHandler(
				$output,
				$severity,
				$message,
				$file,
				$line
			),
			E_ALL
		);
	}

	/**
	 * Processes PHP errors in order to be able to show them in the output
	 *
	 * @see https://www.php.net/manual/en/function.set-error-handler.php
	 *
	 * @param int $severity the level of the error raised
	 * @param string $message
	 * @param string $file the filename that the error was raised in
	 * @param int $line the line number the error was raised
	 */
	public function exceptionErrorHandler(
		OutputInterface $output,
		int $severity,
		string $message,
		string $file,
		int $line
	): bool {
		if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
			// Do not show deprecation warnings
			return false;
		}
		$e = new \ErrorException($message, 0, $severity, $file, $line);
		$output->writeln(
			"<error>Error during list: " . $e->getMessage() . "</error>"
		);
		$output->writeln(
			"<error>" . $e->getTraceAsString() . "</error>",
			OutputInterface::VERBOSITY_VERY_VERBOSE
		);
		return true;
	}

	protected function presentStats(
		InputInterface $input,
		OutputInterface $output
	): void {
		$rows = [];
		$fileInfo = $this->fileInfo[0] ?? [];
		$sortKey = array_key_exists($input->getOption("sort"), $fileInfo)
			? $input->getOption("sort")
			: "";
		if($sortKey == 'size') {
			$sortKey = 'realSize';
		}
		$order = $input->getOption("order") == "ASC" ? SORT_ASC : SORT_DESC;
		$fileArr = array_column($this->fileInfo, $sortKey);
		$dirArr = array_column($this->dirInfo, $sortKey);
		if($sortKey != '') {
			array_multisort(
				$fileArr,
				$order,
				SORT_NATURAL | SORT_FLAG_CASE,
				$this->fileInfo
			);
			array_multisort(
				$dirArr,
				$order,
				SORT_NATURAL | SORT_FLAG_CASE,
				$this->dirInfo
			);
		}
		foreach ($this->fileInfo as $k => $item) {
			$rows[$k] = [
				"Permission" => $item["perm"],
				"Size" => $item["size"],
				"Owner" => $item["owner"],
				"Created at" => $item["created-at"],
				"Filename" => $item["name"],
				"Type" => $item["type"],
			];
		}
		foreach ($this->dirInfo as $k => $item) {
			$rows[] = [
				"Permission" => $item["perm"],
				"Size" => $item["size"],
				"Owner" => $item["owner"],
				"Created at" => $item["created-at"],
				"Filename" => $item["name"],
				"Type" => $item["type"],
			];
		}

		$this->writeTableInOutputFormat($input, $output, $rows);
	}
}
