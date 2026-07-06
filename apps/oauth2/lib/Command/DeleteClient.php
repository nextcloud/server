<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Command;

use OC\Core\Command\Base;
use OCA\OAuth2\Service\ClientService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteClient extends Base {
	private const ARGUMENT_CLIENT_ID = 'client-id';

	public function __construct(
		private readonly ClientService $clientService,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		parent::configure();

		$this->setName('oauth2:delete-client');
		$this->setDescription('This command removes an existing oauth2 client.');
		$this->addArgument(
			self::ARGUMENT_CLIENT_ID,
			InputArgument::REQUIRED,
			'Id of the oauth2 client',
		);
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$id = (int)$input->getArgument(self::ARGUMENT_CLIENT_ID);
		if ($id === 0) {
			$output->writeln('<error>The given id is not a valid positive integer.</error>');
			return Command::FAILURE;
		}

		try {
			$this->clientService->deleteClient($id);
		} catch (\Exception $exception) {
			$output->writeln('<error>' . $exception->getMessage() . '</error>');
			return Command::FAILURE;
		}
		return Command::SUCCESS;
	}
}
