<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use Exception;
use OC\Core\Command\Base;
use OCP\Sharing\Exception\AShareException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DeleteShare extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:delete-share')
			->setDescription('Delete a share.')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string $id */
		$id = $input->getArgument('id');

		try {
			try {
				$this->dbConnection->beginTransaction();

				$this->manager->deleteShare($this->accessContext, $id);
				$this->dbConnection->commit();
				return Base::SUCCESS;
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (AShareException $aShareException) {
			if ($output instanceof ConsoleOutputInterface) {
				$output = $output->getErrorOutput();
			}

			$output->writeln($aShareException->getMessage());
			return Base::FAILURE;
		}
	}
}
