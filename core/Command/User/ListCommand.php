<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	public function __construct(
		protected IUserManager $userManager,
		protected IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:list')
			->setDescription('list configured users')
			->addOption(
				'disabled',
				'd',
				InputOption::VALUE_NONE,
				'List disabled users only'
			)->addOption(
				'limit',
				'l',
				InputOption::VALUE_OPTIONAL,
				'Number of users to retrieve',
				'500'
			)->addOption(
				'offset',
				'o',
				InputOption::VALUE_OPTIONAL,
				'Offset for retrieving users',
				'0'
			)->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat
			)->addOption(
				'info',
				'i',
				InputOption::VALUE_NONE,
				'Show detailed info'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('disabled')) {
			$users = $this->userManager->getDisabledUsers((int) $input->getOption('limit'), (int) $input->getOption('offset'));
		} else {
			$users = $this->userManager->searchDisplayName('', (int) $input->getOption('limit'), (int) $input->getOption('offset'));
		}

		$this->writeArrayInOutputFormat($input, $output, $this->formatUsers($users, (bool)$input->getOption('info')));
		return 0;
	}

	/**
	 * @param IUser[] $users
	 * @param bool [$detailed=false]
	 * @return array
	 */
	private function formatUsers(array $users, bool $detailed = false) {
		$keys = array_map(function (IUser $user) {
			return $user->getUID();
		}, $users);

		$values = array_map(function (IUser $user) use ($detailed) {
			if ($detailed) {
				$groups = $this->groupManager->getUserGroupIds($user);
				return [
					'user_id' => $user->getUID(),
					'display_name' => $user->getDisplayName(),
					'email' => (string)$user->getSystemEMailAddress(),
					'cloud_id' => $user->getCloudId(),
					'enabled' => $user->isEnabled(),
					'groups' => $groups,
					'quota' => $user->getQuota(),
					'last_seen' => date(\DateTimeInterface::ATOM, $user->getLastLogin()), // ISO-8601
					'user_directory' => $user->getHome(),
					'backend' => $user->getBackendClassName()
				];
			}
			return $user->getDisplayName();
		}, $users);
		return array_combine($keys, $values);
	}
}
