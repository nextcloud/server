<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use Exception;
use OC\Core\Command\Base;
use OCP\Sharing\Share;
use OCP\Sharing\Source\IShareSourceType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GetShares extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:get-shares')
			->setDescription('Get multiple shares.')
			->addOption('filter-source-type-class', '', InputOption::VALUE_REQUIRED, 'Source type class to filter by')
			->addOption('filter-source-type-value', '', InputOption::VALUE_REQUIRED, 'Source type value to filter by')
			->addOption('last-share-id', '', InputOption::VALUE_REQUIRED, 'Share ID to use as an offset')
			->addOption('limit', '', InputOption::VALUE_REQUIRED, 'Maximum number of shares to return');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var ?class-string<IShareSourceType> $filterSourceTypeClass */
		$filterSourceTypeClass = $input->getOption('filter-source-type-class');
		/** @var ?class-string<IShareSourceType> $filterSourceTypeValue */
		$filterSourceTypeValue = $input->getOption('filter-source-type-value');
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
			$this->dbConnection->beginTransaction();

			$shares = $this->manager->getShares($this->accessContext, $filterSourceTypeClass, $filterSourceTypeValue, $lastShareID, $limit);
			$this->dbConnection->commit();
			$output->writeln(json_encode(Share::formatMultiple($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager, $shares), JSON_THROW_ON_ERROR));
			return Base::SUCCESS;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw $exception;
		}
	}
}
