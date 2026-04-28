<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Command;

use OC\User\NoUserException;
use OCA\WorkflowEngine\Helper\ScopeContext;
use OCA\WorkflowEngine\Manager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\WorkflowEngine\IManager;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Runtime extends Command {

	public function __construct(
		private Manager $manager,
		private IUserManager $userManager,
		private IUserSession $userSession,
	) {
		parent::__construct();
	}

	#[Override]
	protected function configure() {
		$this
			->setName('workflows:runtime:list')
			->setDescription('Lists configured runtime workflows')
			// need to add an optional filtering by app
			->addArgument(
				'appId',
				InputArgument::OPTIONAL,
				'Filter runtime workflows by appId',
				null
			)
			->addArgument(
				'scope',
				InputArgument::OPTIONAL,
				'Lists workflows for "admin", "user"',
				'admin'
			)
			->addArgument(
				'userId',
				InputArgument::OPTIONAL,
				'User ID used for user scope and session',
				null
			);
	}

	protected function mappedScope(string $scope): int {
		return match($scope) {
			'admin' => IManager::SCOPE_ADMIN,
			'user' => IManager::SCOPE_USER,
			default => -1,
		};
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appId = $input->getArgument('appId');
		$userId = $input->getArgument('userId');

		if ($userId !== null) {
			$user = $this->userManager->get($userId);
			if (is_null($user)) {
				throw new NoUserException("user $userId not found");
			}
			$this->userSession->setUser($user);
			$this->manager->reloadRuntimeOperations();
		}

		$opsByClass = $this->manager->getAllRuntimeOperations(
			new ScopeContext(
				$this->mappedScope($input->getArgument('scope')),
				$input->getArgument('userId')
			),
			$appId,
		);

		foreach ($opsByClass as &$operations) {
			foreach ($operations as &$operation) {
				$checks = $operation['checks'];
				$appId = $operation['appId'];
				$decodedChecks = json_decode($checks, true);
				$operation['checks'] = $this->manager->getRuntimeChecks($decodedChecks, $appId);
			}
			unset($operation);
		}
		unset($operations);

		$output->writeln(\json_encode($opsByClass, JSON_PRETTY_PRINT));
		return 0;
	}
}
