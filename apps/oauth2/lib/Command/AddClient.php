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

class AddClient extends Base {
	private const ARGUMENT_CLIENT_NAME = 'client-name';
	private const ARGUMENT_CLIENT_REDIRECT_URI = 'client-redirect-uri';

	public function __construct(
		private readonly ClientService $clientService,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		parent::configure();

		$this->setName('oauth2:add-client');
		$this->setDescription('This command adds a new oauth2 client.');
		$this->addArgument(
			self::ARGUMENT_CLIENT_NAME,
			InputArgument::REQUIRED,
			'Name of the oauth2 client',
		);
		$this->addArgument(
			self::ARGUMENT_CLIENT_REDIRECT_URI,
			InputArgument::REQUIRED,
			'Redirection uri of the oauth2 client ',
		);
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string $name */
		$name = $input->getArgument(self::ARGUMENT_CLIENT_NAME);

		/** @var string $redirectUri */
		$redirectUri = $input->getArgument(self::ARGUMENT_CLIENT_REDIRECT_URI);

		// Should not happen but just to be sure
		if (empty($redirectUri) || empty($name)) {
			$output->writeln('<error>Redirect uri or name is empty</error>');
			return Command::FAILURE;
		}

		if (filter_var($redirectUri, FILTER_VALIDATE_URL) === false) {
			$output->writeln('<error>Your redirect URL needs to be a full URL for example: https://yourdomain.com/path</error>');
			return Command::FAILURE;
		}

		$result = $this->clientService->addClient($name, $redirectUri);
		$this->writeArrayInOutputFormat($input, $output, $result);
		return Command::SUCCESS;
	}
}
