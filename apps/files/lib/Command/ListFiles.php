<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Blaok <i@blaok.me>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Joel S <joel.devbox@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author martin.mattel@diemattels.at <martin.mattel@diemattels.at>
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Kareem <yemkareems@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files\Command;

use OC\Core\Command\Base;
use OC\Core\Command\InterruptedException;
use OC\Files\Node\Node;
use OC\FilesMetadata\FilesMetadataManager;
use OC\ForbiddenException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\Table;
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
			->setDescription("List filesystem")
			->addArgument(
				"path",
				InputArgument::REQUIRED,
				'Limit list to this path, eg. path="/alice/files/Music", the user_id is determined by the path parameter'
			)
			->addOption("type", "", InputArgument::OPTIONAL, "Filter by type like application, image, video etc")
			->addOption(
				"minSize",
				'0',
				InputArgument::OPTIONAL,
				"Filter by min size"
			)
			->addOption(
				"maxSize",
				'0',
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
		return [
			"name" => $node->getName(),
			"size" => $node->getSize() . " bytes",
			"perm" => $node->getPermissions(),
			"owner" => $node->getOwner()->getDisplayName(),
			"created-at" => $node->getCreationTime(),
			"type" => $node->getMimePart(),
		];
	}

	protected function listFiles(
		string $user,
		string $path,
		OutputInterface $output,
		?string $type = "",
		?int $minSize = 0,
		?int $maxSize = 0
	): void {
		try {
			$userFolder = $this->rootFolder->get($path);
			$files = $userFolder->getDirectoryListing();
			foreach ($files as $file) {
				$includeType = $includeMin = $includeMax = true;
				if ($type != "" && $type != $file->getMimePart()) {
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
						$this->fileInfo[] = $this->getNodeInfo($file);
					}
				} elseif ($file instanceof Folder) {
					if ($includeType && $includeMin && $includeMax) {
						$this->dirInfo[] = $this->getNodeInfo($file);
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
		$inputPath = $input->getArgument("path");
		
		$users = [];
		if ($inputPath) {
			$inputPath = ltrim($inputPath, "path=");
			[, $user] = explode("/", rtrim($inputPath, "/").'/', 4);
			$users = [$user];
		}

		# check quantity of users to be process and show it on the command line
		$users_total = count($users);
		if ($users_total === 0) {
			$output->writeln(
				"<error>Please specify the path to list, path=...</error>"
			);
			return self::FAILURE;
		}

		$this->initTools($output);

		$user_count = 0;
		foreach ($users as $user) {
			if (is_object($user)) {
				$user = $user->getUID();
			}
			$path = $inputPath ?: "/" . $user;
			++$user_count;
			if ($this->userManager->userExists($user)) {
				$output->writeln(
					"Starting list for user $user_count out of $users_total ($user)"
				);
				$this->listFiles(
					$user,
					$path,
					$output,
					$input->getOption("type"),
					$input->getOption("minSize"),
					$input->getOption("maxSize")
				);
			} else {
				$output->writeln(
					"<error>Unknown user $user_count $user</error>"
				);
				$output->writeln("", OutputInterface::VERBOSITY_VERBOSE);
			}

			try {
				$this->abortIfInterrupted();
			} catch (InterruptedException $e) {
				break;
			}
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
		$headers = [
			"Permission",
			"Size",
			"Owner",
			"Created at",
			"Filename",
			"Type",
		];
		$rows = [];
		$fileInfo = $this->fileInfo[0] ?? [];
		$sortKey = array_key_exists($input->getOption("sort"), $fileInfo)
			? $input->getOption("sort")
			: "name";
		$order = $input->getOption("order") == "ASC" ? SORT_ASC : SORT_DESC;
		array_multisort(
			array_column($this->fileInfo, $sortKey),
			$order,
			$this->fileInfo
		);
		array_multisort(
			array_column($this->dirInfo, $sortKey),
			$order,
			$this->dirInfo
		);

		foreach ($this->fileInfo as $k => $item) {
			$rows[$k] = [
				$item["perm"],
				$item["size"],
				$item["owner"],
				$item["created-at"],
				$item["name"],
				$item["type"],
			];
		}
		foreach ($this->dirInfo as $k => $item) {
			$rows[] = [
				$item["perm"],
				$item["size"],
				$item["owner"],
				$item["created-at"],
				$item["name"],
				$item["type"],
			];
		}

		$table = new Table($output);
		$table->setHeaders($headers)->setRows($rows);
		$table->render();
	}
}
