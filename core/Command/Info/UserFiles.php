<?php

declare(strict_types=1);

namespace OC\Core\Command\Info;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OCA\Circles\MountManager\CircleMount;
use OCA\Files_External\Config\ExternalMountPoint;
use OCA\Files_Sharing\SharedMount;
use OCA\GroupFolders\Mount\GroupMountPoint;
use OCP\Constants;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Share\IShare;
use OCP\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserFiles extends Command {
	private IRootFolder $rootFolder;
	private IUserManager $userManager;
	private IL10N $l10n;
	private FileUtils $fileUtils;

	public function __construct(
		IRootFolder $rootFolder,
		IUserManager $userManager,
		IFactory $l10nFactory,
		FileUtils $fileUtils
	) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->l10n = $l10nFactory->get("core");
		$this->fileUtils = $fileUtils;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('info:file:user')
			->setDescription('get file information for a user')
			->addArgument('user_id', InputArgument::REQUIRED, "User id")
			->addOption('large-files', 'l', InputOption::VALUE_REQUIRED, "Number of large files and folders to show", 25);
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user_id');
		$user = $this->userManager->get($userId);
		if (!$user) {
			$output->writeln("<error>usefr $userId not found</error>");
			return 1;
		}

		$output->writeln($user->getUID());

		$mounts = $this->fileUtils->getMountsForUser($user);
		$output->writeln("");
		$output->writeln("  available storages:");
		foreach ($mounts as $mount) {
			$storage = $mount->getStorage();
			if (!$storage) {
				continue;
			}
			$cache = $storage->getCache();
			$free = Util::humanFileSize($storage->free_space(""));
			$output->write("  - " . $mount->getMountPoint() . ": " . $this->fileUtils->formatMountType($mount));
			if ($storage->instanceOfStorage(IHomeStorage::class)) {
				$filesInfo = $cache->get("files");
				$trashInfo = $cache->get("files_trashbin");
				$versionsInfo = $cache->get("files_versions");
				$used = Util::humanFileSize($filesInfo ? $filesInfo->getSize() : 0);
				$trashUsed = Util::humanFileSize($trashInfo ? $trashInfo->getSize() : 0);
				$versionUsed = Util::humanFileSize($versionsInfo ? $versionsInfo->getSize() : 0);
				$output->writeln(" ($used in files, $trashUsed in trash, $versionUsed in versions, $free free)");
			} else {
				$rootInfo = $cache->get("");
				$used = Util::humanFileSize($rootInfo ? $rootInfo->getSize(): -1);
				$output->writeln(" ($used used, $free free)");
			}
		}
		$output->writeln("");
		$output->writeln("  use <info>occ info:file:space <path></info> to get more details about used space");

		return 0;
	}

}
