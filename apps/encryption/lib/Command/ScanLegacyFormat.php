<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Command;

use OC\Files\View;
use OCA\Encryption\Util;
use OCP\IConfig;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanLegacyFormat extends Command {
	private View $rootView;

	public function __construct(
		protected Util $util,
		protected IConfig $config,
		protected QuestionHelper $questionHelper,
		private IUserManager $userManager,
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

		foreach ($this->userManager->getBackends() as $backend) {
			$limit = 500;
			$offset = 0;
			do {
				$users = $backend->getUsers('', $limit, $offset);
				foreach ($users as $user) {
					$output->writeln('Scanning all files for ' . $user);
					$this->setupUserFS($user);
					$result = $result && $this->scanFolder($output, '/' . $user);
				}
				$offset += $limit;
			} while (count($users) >= $limit);
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
	protected function setupUserFS(string $uid): void {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
	}
}
