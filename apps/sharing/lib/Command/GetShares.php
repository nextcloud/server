<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use Exception;
use NCU\Sharing\Share;
use NCU\Sharing\ShareState;
use NCU\Sharing\ShareUserStatus;
use NCU\Sharing\Source\IShareSourceType;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnitEnum;
use ValueError;

final class GetShares extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:get-shares')
			->setDescription('Get multiple shares.')
			->addOption('filter-source-type-class', '', InputOption::VALUE_REQUIRED, 'Source type class to filter by')
			->addOption('filter-source-type-value', '', InputOption::VALUE_REQUIRED, 'Source type value to filter by')
			->addOption('filter-state', '', InputOption::VALUE_REQUIRED, 'State to filter by. Possible values: ' . implode(', ', array_map(static fn (UnitEnum $case) => $case->value, ShareState::cases())))
			->addOption('filter-user-status', '', InputOption::VALUE_REQUIRED, 'User status to filter by. Possible values: ' . implode(', ', array_map(static fn (UnitEnum $case) => $case->value, ShareUserStatus::cases())))
			->addOption('last-share-id', '', InputOption::VALUE_REQUIRED, 'Share ID to use as an offset')
			->addOption('limit', '', InputOption::VALUE_REQUIRED, 'Maximum number of shares to return');
		parent::configure();
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		$this->applyActor($input);

		/** @var ?class-string<IShareSourceType> $filterSourceTypeClass */
		$filterSourceTypeClass = $input->getOption('filter-source-type-class');
		/** @var ?class-string<IShareSourceType> $filterSourceTypeValue */
		$filterSourceTypeValue = $input->getOption('filter-source-type-value');
		/** @var ?string $filterState */
		$filterState = $input->getOption('filter-state');
		if ($filterState !== null) {
			try {
				$filterState = ShareState::from($filterState);
			} catch (ValueError $valueError) {
				$output->writeln($valueError->getMessage());
				return Base::FAILURE;
			}
		}

		/** @var ?string $filterUserStatus */
		$filterUserStatus = $input->getOption('filter-user-status');
		if ($filterUserStatus !== null) {
			try {
				$filterUserStatus = ShareUserStatus::from($filterUserStatus);
			} catch (ValueError $valueError) {
				$output->writeln($valueError->getMessage());
				return Base::FAILURE;
			}
		}

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

			$shares = $this->manager->getShares($this->accessContext, $filterSourceTypeClass, $filterSourceTypeValue, $filterState, $filterUserStatus, $lastShareID, $limit);
			$this->dbConnection->commit();

			$data = Share::formatMultiple($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager, $this->accessContext, $shares);
			$this->writeArrayInOutputFormat($input, $output, $data);
			return Base::SUCCESS;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw $exception;
		}
	}
}
