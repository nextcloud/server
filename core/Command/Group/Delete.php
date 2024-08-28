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
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Base {
	public function __construct(
		protected IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('group:delete')
			->setDescription('Remove a group')
			->addArgument(
				'groupid',
				InputArgument::REQUIRED,
				'Group name'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$gid = $input->getArgument('groupid');
		if ($gid === 'admin') {
			$output->writeln('<error>Group "' . $gid . '" could not be deleted.</error>');
			return 1;
		}
		if (!$this->groupManager->groupExists($gid)) {
			$output->writeln('<error>Group "' . $gid . '" does not exist.</error>');
			return 1;
		}
		$group = $this->groupManager->get($gid);
		if ($group->delete()) {
			$output->writeln('Group "' . $gid . '" was removed');
		} else {
			$output->writeln('<error>Group "' . $gid . '" could not be deleted. Please check the logs.</error>');
			return 1;
		}
		return 0;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'groupid') {
			return array_map(static fn (IGroup $group) => $group->getGID(), $this->groupManager->search($context->getCurrentWord()));
		}
		return [];
	}
}
