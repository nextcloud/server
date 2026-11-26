<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\User;

use NCU\User\IUserBackendManager;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(name: 'user:backend:list', description: 'List the registered user backends', supportsOutputFormat: true)]
final readonly class ListUserBackends {
	public function __construct(
		private IUserBackendManager $backendManager,
	) {
	}

	public function __invoke(IOutput $output): ExitCode {
		$backends = [];
		foreach ($this->backendManager->list() as $backend) {
			$nbAccounts = $backend->countUsers();
			$backends[] = [
				'ID' => $backend->getId(),
				'Name' => $backend->getName(),
				'Class' => $backend::class,
				'Nb. accounts' => $nbAccounts === false ? 'N/A' : $nbAccounts,
			];
		}
		$output->writeTableInOutputFormat($backends);

		return ExitCode::Success;
	}
}
