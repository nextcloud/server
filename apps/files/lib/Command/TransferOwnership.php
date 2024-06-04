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
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TransferOwnership extends Command {
	public function __construct(
		private IUserManager $userManager,
		private OwnershipTransferService $transferService,
		private IConfig $config,
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
				'transfer incoming user file shares to destination user. Usage: --transfer-incoming-shares=1 (value required)',
				'2'
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
			$output->writeln("<error>Unknown source user " . $input->getArgument('source-user') . "</error>");
			return self::FAILURE;
		}

		if (!$destinationUserObject instanceof IUser) {
			$output->writeln("<error>Unknown destination user " . $input->getArgument('destination-user') . "</error>");
			return self::FAILURE;
		}

		try {
			$includeIncomingArgument = $input->getOption('transfer-incoming-shares');

			switch ($includeIncomingArgument) {
				case '0':
					$includeIncoming = false;
					break;
				case '1':
					$includeIncoming = true;
					break;
				case '2':
					$includeIncoming = $this->config->getSystemValue('transferIncomingShares', false);
					if (gettype($includeIncoming) !== 'boolean') {
						$output->writeln("<error> config.php: 'transfer-incoming-shares': wrong usage. Transfer aborted.</error>");
						return self::FAILURE;
					}
					break;
				default:
					$output->writeln("<error>Option --transfer-incoming-shares: wrong usage. Transfer aborted.</error>");
					return self::FAILURE;
			}

			$this->transferService->transfer(
				$sourceUserObject,
				$destinationUserObject,
				ltrim($input->getOption('path'), '/'),
				$output,
				$input->getOption('move') === true,
				false,
				$includeIncoming
			);
		} catch (TransferOwnershipException $e) {
			$output->writeln("<error>" . $e->getMessage() . "</error>");
			return $e->getCode() !== 0 ? $e->getCode() : self::FAILURE;
		}

		return self::SUCCESS;
	}
}
