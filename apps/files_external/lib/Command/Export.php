<?php

declare(strict_types=1);

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
	#[\Override]
	protected function configure(): void {
		$this
			->setName('files_external:export')
			->setDescription('Export external storage mounts as JSON')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL,
				'user ID whose personal mounts should be exported; omit to export global mounts'
			)->addOption(
				'all',
				'a',
				InputOption::VALUE_NONE,
				'export global mounts and personal mounts for all users'
			)->setHelp(<<<'HELP'
Exports external storage mount configurations as pretty-printed JSON to
standard output. The output is intended for backup or migration and can be
passed to files_external:import.

Exported configuration includes full values and sensitive credentials such as
passwords, keys, and tokens. Protect the output appropriately.

Without a user ID, global mounts are exported. With a user ID, that user's
personal mounts are exported. Use --all to export global mounts and personal
mounts for all users.

Examples:
  occ files_external:export > mounts.json
  occ files_external:export alice > alice-mounts.json
  occ files_external:export --all > all-mounts.json
  occ files_external:export | occ files_external:import -
HELP
			);
	}

	#[\Override]
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
