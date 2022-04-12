<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021, hosting.de, Johannes Leuker <developers@hosting.de>
 *
 * @author Johannes Leuker <j.leuker@hosting.de>
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
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Info extends Base {
	protected IGroupManager $groupManager;

	public function __construct(IGroupManager $groupManager) {
		$this->groupManager = $groupManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('group:info')
			->setDescription('Show information about a group')
			->addArgument(
				'groupid',
				InputArgument::REQUIRED,
				'Group id'
			)->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$gid = $input->getArgument('groupid');
		$group = $this->groupManager->get($gid);
		if (!$group instanceof IGroup) {
			$output->writeln('<error>Group "' . $gid . '" does not exist.</error>');
			return 1;
		} else {
			$groupOutput = [
				'groupID' => $gid,
				'displayName' => $group->getDisplayName(),
				'backends' => $group->getBackendNames(),
			];

			$this->writeArrayInOutputFormat($input, $output, $groupOutput);
			return 0;
		}
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
