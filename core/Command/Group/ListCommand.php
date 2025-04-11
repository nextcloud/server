<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
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

class ListCommand extends Base {
	public function __construct(
		protected IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('group:list')
			->setDescription('list configured groups')
			->addArgument(
				'searchstring',
				InputArgument::OPTIONAL,
				'Filter the groups to only those matching the search string',
				''
			)
			->addOption(
				'limit',
				'l',
				InputOption::VALUE_OPTIONAL,
				'Number of groups to retrieve',
				'500'
			)->addOption(
				'offset',
				'o',
				InputOption::VALUE_OPTIONAL,
				'Offset for retrieving groups',
				'0'
			)->addOption(
				'info',
				'i',
				InputOption::VALUE_NONE,
				'Show additional info (backend)'
			)->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$groups = $this->groupManager->search((string)$input->getArgument('searchstring'), (int)$input->getOption('limit'), (int)$input->getOption('offset'));
		$this->writeArrayInOutputFormat($input, $output, $this->formatGroups($groups, (bool)$input->getOption('info')));
		return 0;
	}

	/**
	 * @param IGroup $group
	 * @return string[]
	 */
	public function usersForGroup(IGroup $group) {
		$users = array_keys($group->getUsers());
		return array_map(function ($userId) {
			return (string)$userId;
		}, $users);
	}

	/**
	 * @param IGroup[] $groups
	 */
	private function formatGroups(array $groups, bool $addInfo = false): \Generator {
		foreach ($groups as $group) {
			if ($addInfo) {
				$value = [
					'displayName' => $group->getDisplayName(),
					'backends' => $group->getBackendNames(),
					'users' => $this->usersForGroup($group),
				];
			} else {
				$value = $this->usersForGroup($group);
			}
			yield $group->getGID() => $value;
		}
	}
}
