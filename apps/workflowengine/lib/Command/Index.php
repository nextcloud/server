<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Command;

use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCP\WorkflowEngine\IManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Index extends Command {

	public function __construct(
		private Manager $manager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('workflows:list')
			->setDescription('Lists configured workflows')
			->addArgument(
				'scope',
				InputArgument::OPTIONAL,
				'Lists workflows for "admin", "user"',
				'admin'
			)
			->addArgument(
				'scopeId',
				InputArgument::OPTIONAL,
				'User IDs when the scope is "user"',
				null
			);
	}

	protected function mappedScope(string $scope): int {
		static $scopes = [
			'admin' => IManager::SCOPE_ADMIN,
			'user' => IManager::SCOPE_USER,
		];
		return $scopes[$scope] ?? -1;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$ops = $this->manager->getAllOperations(
			new ScopeContext(
				$this->mappedScope($input->getArgument('scope')),
				$input->getArgument('scopeId')
			)
		);
		$output->writeln(\json_encode($ops));
		return 0;
	}
}
