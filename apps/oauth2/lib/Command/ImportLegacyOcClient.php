<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Command;

use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCP\IConfig;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportLegacyOcClient extends Command {
	private const ARGUMENT_CLIENT_ID = 'client-id';
	private const ARGUMENT_CLIENT_SECRET = 'client-secret';

	public function __construct(
		private readonly IConfig $config,
		private readonly ICrypto $crypto,
		private readonly ClientMapper $clientMapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('oauth2:import-legacy-oc-client');
		$this->setDescription('This command is only required to be run on instances which were migrated from ownCloud without the oauth2.enable_oc_clients system config! Import a legacy Oauth2 client from an ownCloud instance and migrate it. The data is expected to be straight out of the database table oc_oauth2_clients.');
		$this->addArgument(
			self::ARGUMENT_CLIENT_ID,
			InputArgument::REQUIRED,
			'Value of the "identifier" column',
		);
		$this->addArgument(
			self::ARGUMENT_CLIENT_SECRET,
			InputArgument::REQUIRED,
			'Value of the "secret" column',
		);
	}

	public function isEnabled(): bool {
		return $this->config->getSystemValueBool('oauth2.enable_oc_clients', false);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string $clientId */
		$clientId = $input->getArgument(self::ARGUMENT_CLIENT_ID);

		/** @var string $clientSecret */
		$clientSecret = $input->getArgument(self::ARGUMENT_CLIENT_SECRET);

		// Should not happen but just to be sure
		if (empty($clientId) || empty($clientSecret)) {
			return 1;
		}

		$hashedClientSecret = bin2hex($this->crypto->calculateHMAC($clientSecret));

		$client = new Client();
		$client->setName('ownCloud Desktop Client');
		$client->setRedirectUri('http://localhost:*');
		$client->setClientIdentifier($clientId);
		$client->setSecret($hashedClientSecret);
		$this->clientMapper->insert($client);

		$output->writeln('<info>Client imported successfully</info>');
		return 0;
	}
}
