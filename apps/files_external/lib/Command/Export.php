<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Export extends ListCommand {
	protected function configure(): void {
		$this
			->setName('files_external:export')
			->setDescription('Export mount configurations')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL,
				'user id to export the personal mounts for, if no user is provided admin mounts will be exported'
			)->addOption(
				'all',
				'a',
				InputOption::VALUE_NONE,
				'show both system wide mounts and all personal mounts'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$listCommand = new ListCommand($this->globalService, $this->userService, $this->userSession, $this->userManager);
		$listInput = new ArrayInput([], $listCommand->getDefinition());
		$listInput->setArgument('user_id', $input->getArgument('user_id'));
		$listInput->setOption('all', $input->getOption('all'));
		$listInput->setOption('output', 'json_pretty');
		$listInput->setOption('show-password', true);
		$listInput->setOption('full', true);
		$listCommand->execute($listInput, $output);
		return self::SUCCESS;
	}
}
