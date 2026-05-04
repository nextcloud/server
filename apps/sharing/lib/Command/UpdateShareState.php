<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCP\Sharing\ShareState;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateShareState extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:update-share-state')
			->setDescription('Update the state of a share')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID')
			->addArgument('state', InputArgument::REQUIRED, 'State');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		return $this->wrapExecution($output, function () use ($input): string {
			/** @var string $id */
			$id = $input->getArgument('id');
			/** @var string $state */
			$state = $input->getArgument('state');
			$state = ShareState::from($state);

			$this->manager->updateShareState($this->accessContext, $id, $state);
			return $id;
		});
	}
}
