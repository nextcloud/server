<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Leuker <j.leuker@hosting.de>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Command\Group;

use OC\Core\Command\Base;
use OCP\IGroup;
use OCP\IGroupManager;
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
		$groups = $this->groupManager->search('', (int)$input->getOption('limit'), (int)$input->getOption('offset'));
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
	 * @return array
	 */
	private function formatGroups(array $groups, bool $addInfo = false) {
		$keys = array_map(function (IGroup $group) {
			return $group->getGID();
		}, $groups);

		if ($addInfo) {
			$values = array_map(function (IGroup $group) {
				return [
					'backends' => $group->getBackendNames(),
					'users' => $this->usersForGroup($group),
				];
			}, $groups);
		} else {
			$values = array_map(function (IGroup $group) {
				return $this->usersForGroup($group);
			}, $groups);
		}
		return array_combine($keys, $values);
	}
}
