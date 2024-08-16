<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	public function __construct(
		protected IGroupManager $groupManager,
	) {
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
