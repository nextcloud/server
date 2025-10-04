<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Command;

use OC\Files\SetupManager;
use OC\Files\View;
use OCA\Encryption\Util;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanLegacyFormat extends Command {
	private View $rootView;

	public function __construct(
		protected readonly Util $util,
		protected readonly IConfig $config,
		protected readonly QuestionHelper $questionHelper,
		private readonly IUserManager $userManager,
		private readonly SetupManager $setupManager,
	) {
		parent::__construct();

		$this->rootView = new View();
	}

	protected function configure(): void {
		$this
			->setName('encryption:scan:legacy-format')
			->setDescription('Scan the files for the legacy format');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$result = true;

		$output->writeln('Scanning all files for legacy encryption');

		foreach ($this->userManager->getSeenUsers() as $user) {
			$output->writeln('Scanning all files for ' . $user->getUID());
			$this->setupUserFileSystem($user);
			$result = $result && $this->scanFolder($output, '/' . $user->getUID());
		}

		if ($result) {
			$output->writeln('All scanned files are properly encrypted. You can disable the legacy compatibility mode.');
			return self::SUCCESS;
		}

		return self::FAILURE;
	}

	private function scanFolder(OutputInterface $output, string $folder): bool {
		$clean = true;

		foreach ($this->rootView->getDirectoryContent($folder) as $item) {
			$path = $folder . '/' . $item['name'];
			if ($this->rootView->is_dir($path)) {
				if ($this->scanFolder($output, $path) === false) {
					$clean = false;
				}
			} else {
				if (!$item->isEncrypted()) {
					// ignore
					continue;
				}

				$stats = $this->rootView->stat($path);
				if (!isset($stats['hasHeader']) || $stats['hasHeader'] === false) {
					$clean = false;
					$output->writeln($path . ' does not have a proper header');
				}
			}
		}

		return $clean;
	}

	/**
	 * setup user file system
	 */
	protected function setupUserFileSystem(IUser $user): void {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);
	}
}
