<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\User;

use OC\Avatar\AvatarManager;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearGeneratedAvatarCacheCommand extends Base {
	public function __construct(
		protected AvatarManager $avatarManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setDescription('clear avatar cache')
			->setName('user:clear-avatar-cache');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$output->writeln('Clearing avatar cache has started');
		$this->avatarManager->clearCachedAvatars();
		$output->writeln('Cleared avatar cache successfully');
		return 0;
	}
}
