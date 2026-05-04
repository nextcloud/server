<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OC\Core\Command\Base;
use OCP\Sharing\Exception\AShareException;
use OCP\Sharing\Share;
use OCP\Sharing\Source\IShareSourceType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListShares extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:list-shares')
			->setDescription('List shares')
			->addOption('source-class', '', InputOption::VALUE_REQUIRED, 'Source class to filter by')
			->addOption('last-share-id', '', InputOption::VALUE_REQUIRED, 'Share ID to use as an offset')
			->addOption('limit', '', InputOption::VALUE_REQUIRED, 'Maximum number of shares to return');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var ?class-string<IShareSourceType> $sourceTypeClass */
		$sourceTypeClass = $input->getOption('source-class');
		/** @var ?string $lastShareID */
		$lastShareID = $input->getOption('last-share-id');
		/** @var ?string $limit */
		$limit = $input->getOption('limit');
		if ($limit !== null) {
			$limit = (int)$limit;
			if ($limit < 1) {
				$output->writeln('The limit is too low.');
				return Base::FAILURE;
			}
		}

		try {
			$output->writeln(json_encode(array_map(static fn (Share $share): array => $share->format(), $this->manager->listShares($this->accessContext, $sourceTypeClass, $lastShareID, $limit)), JSON_THROW_ON_ERROR));
			return Base::SUCCESS;
		} catch (AShareException $aShareException) {
			if ($output instanceof ConsoleOutputInterface) {
				$output = $output->getErrorOutput();
			}

			$output->writeln($aShareException->getMessage());
			return Base::FAILURE;
		}
	}
}
