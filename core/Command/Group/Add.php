<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Group;

use OC\Core\Command\Base;
use OCP\IGroup;
use OCP\IGroupManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Add extends Base {
	public function __construct(
		protected IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('group:add')
			->setDescription('Add a group')
			->addArgument(
				'groupid',
				InputArgument::REQUIRED,
				'Group id'
			)
			->addOption(
				'display-name',
				null,
				InputOption::VALUE_REQUIRED,
				'Group name used in the web UI (can contain any characters)'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$gid = $input->getArgument('groupid');
		$group = $this->groupManager->get($gid);
		if ($group) {
			$output->writeln('<error>Group "' . $gid . '" already exists.</error>');
			return 1;
		} else {
			$group = $this->groupManager->createGroup($gid);
			if (!$group instanceof IGroup) {
				$output->writeln('<error>Could not create group</error>');
				return 2;
			}
			$output->writeln('Created group "' . $group->getGID() . '"');

			$displayName = trim((string)$input->getOption('display-name'));
			if ($displayName !== '') {
				$group->setDisplayName($displayName);
			}
		}
		return 0;
	}
}
