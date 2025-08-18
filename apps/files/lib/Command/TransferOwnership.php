<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files\Command;

use OCA\Files\Exception\TransferOwnershipException;
use OCA\Files\Service\OwnershipTransferService;
use OCA\Files_External\Config\ConfigAdapter;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class TransferOwnership extends Command {
	public function __construct(
		private IUserManager $userManager,
		private OwnershipTransferService $transferService,
		private IConfig $config,
		private IMountManager $mountManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:transfer-ownership')
			->setDescription('All files and folders are moved to another user - outgoing shares and incoming user file shares (optionally) are moved as well.')
			->addArgument(
				'source-user',
				InputArgument::REQUIRED,
				'owner of files which shall be moved'
			)
			->addArgument(
				'destination-user',
				InputArgument::REQUIRED,
				'user who will be the new owner of the files'
			)
			->addOption(
				'path',
				null,
				InputOption::VALUE_REQUIRED,
				'selectively provide the path to transfer. For example --path="folder_name"',
				''
			)->addOption(
				'move',
				null,
				InputOption::VALUE_NONE,
				'move data from source user to root directory of destination user, which must be empty'
			)->addOption(
				'transfer-incoming-shares',
				null,
				InputOption::VALUE_OPTIONAL,
				'Incoming shares are always transferred now, so this option does not affect the ownership transfer anymore',
				'2'
			)->addOption(
				'include-external-storage',
				null,
				InputOption::VALUE_NONE,
				'include files on external storages, this will _not_ setup an external storage for the target user, but instead moves all the files from the external storages into the target users home directory',
			)->addOption(
				'force-include-external-storage',
				null,
				InputOption::VALUE_NONE,
				'don\'t ask for confirmation for transferring external storages',
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		/**
		 * Check if source and destination users are same. If they are same then just ignore the transfer.
		 */

		if ($input->getArgument(('source-user')) === $input->getArgument('destination-user')) {
			$output->writeln("<error>Ownership can't be transferred when Source and Destination users are the same user. Please check your input.</error>");
			return self::FAILURE;
		}

		$sourceUserObject = $this->userManager->get($input->getArgument('source-user'));
		$destinationUserObject = $this->userManager->get($input->getArgument('destination-user'));

		if (!$sourceUserObject instanceof IUser) {
			$output->writeln('<error>Unknown source user ' . $input->getArgument('source-user') . '</error>');
			return self::FAILURE;
		}

		if (!$destinationUserObject instanceof IUser) {
			$output->writeln('<error>Unknown destination user ' . $input->getArgument('destination-user') . '</error>');
			return self::FAILURE;
		}

		$path = ltrim($input->getOption('path'), '/');
		$includeExternalStorage = $input->getOption('include-external-storage');
		if ($includeExternalStorage) {
			$mounts = $this->mountManager->findIn('/' . rtrim($sourceUserObject->getUID() . '/files/' . $path, '/'));
			/** @var IMountPoint[] $mounts */
			$mounts = array_filter($mounts, fn ($mount) => $mount->getMountProvider() === ConfigAdapter::class);
			if (count($mounts) > 0) {
				$output->writeln(count($mounts) . ' external storages will be transferred:');
				foreach ($mounts as $mount) {
					$output->writeln('  - <info>' . $mount->getMountPoint() . '</info>');
				}
				$output->writeln('');
				$output->writeln('<comment>Any other users with access to these external storages will lose access to the files.</comment>');
				$output->writeln('');
				if (!$input->getOption('force-include-external-storage')) {
					/** @var QuestionHelper $helper */
					$helper = $this->getHelper('question');
					$question = new ConfirmationQuestion('Are you sure you want to transfer external storages? (y/N) ', false);
					if (!$helper->ask($input, $output, $question)) {
						return self::FAILURE;
					}
				}
			}
		}

		try {
			$this->transferService->transfer(
				$sourceUserObject,
				$destinationUserObject,
				$path,
				$output,
				$input->getOption('move') === true,
				false,
				$includeExternalStorage,
			);
		} catch (TransferOwnershipException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return $e->getCode() !== 0 ? $e->getCode() : self::FAILURE;
		}

		return self::SUCCESS;
	}
}
