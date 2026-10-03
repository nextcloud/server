<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Command;

use OCA\Federation\TrustedServers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ManageTrustedServers extends Command {
	private const STATUS_LABELS = [
		TrustedServers::STATUS_OK => 'ok',
		TrustedServers::STATUS_PENDING => 'pending',
		TrustedServers::STATUS_FAILURE => 'failure',
		TrustedServers::STATUS_ACCESS_REVOKED => 'access revoked',
	];

	public function __construct(
		private TrustedServers $trustedServersManager,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure() {
		$this
			->setName('federation:trusted-servers')
			->setDescription('Manage trusted servers')
			->addArgument(
				'servers',
				InputArgument::IS_ARRAY,
				'space-separated list of server URLs, used with --add or --remove'
			)
			->addOption('add', null, InputOption::VALUE_NONE, 'add the given servers to the trusted servers list')
			->addOption('remove', null, InputOption::VALUE_NONE, 'remove the given servers from the trusted servers list')
			->addOption('list', null, InputOption::VALUE_NONE, 'list all trusted servers');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$add = (bool)$input->getOption('add');
		$remove = (bool)$input->getOption('remove');
		$list = (bool)$input->getOption('list');

		if (($add ? 1 : 0) + ($remove ? 1 : 0) + ($list ? 1 : 0) !== 1) {
			$output->writeln('<error>Please specify exactly one of --add, --remove or --list.</error>');
			return self::INVALID;
		}

		if ($list) {
			return $this->listServers($output);
		}

		$servers = $input->getArgument('servers');
		if (empty($servers)) {
			$output->writeln('<error>You must provide at least one server URL.</error>');
			return self::INVALID;
		}

		return $add
			? $this->addServers($servers, $output)
			: $this->removeServers($servers, $output);
	}

	private function listServers(OutputInterface $output): int {
		$servers = $this->trustedServersManager->getServers();

		if (empty($servers)) {
			$output->writeln('No trusted servers configured.');
			return self::SUCCESS;
		}

		$table = new Table($output);
		$table->setHeaders(['id', 'url', 'status']);
		foreach ($servers as $server) {
			$table->addRow([
				$server['id'],
				$server['url'],
				self::STATUS_LABELS[$server['status']] ?? (string)$server['status'],
			]);
		}
		$table->render();

		return self::SUCCESS;
	}

	private function addServers(array $servers, OutputInterface $output): int {
		$output->writeln('Adding trusted servers:');
		$hasFailure = false;
		foreach ($servers as $server) {
			if ($this->trustedServersManager->isTrustedServer($server)) {
				$output->writeln("  - $server already trusted, skipping");
				continue;
			}

			try {
				$this->trustedServersManager->addServer($server);
				$output->writeln("  - $server added");
			} catch (\Exception $e) {
				$output->writeln("  - $server failed: " . $e->getMessage());
				$hasFailure = true;
			}
		}

		return $hasFailure ? self::FAILURE : self::SUCCESS;
	}

	private function removeServers(array $servers, OutputInterface $output): int {
		$output->writeln('Removing trusted servers:');
		$hasFailure = false;
		foreach ($servers as $server) {
			$id = $this->findServerId($server);
			if ($id === null) {
				$output->writeln("  - $server not found, skipping");
				continue;
			}

			try {
				$this->trustedServersManager->removeServer($id);
				$output->writeln("  - $server removed");
			} catch (\Exception $e) {
				$output->writeln("  - $server failed: " . $e->getMessage());
				$hasFailure = true;
			}
		}

		return $hasFailure ? self::FAILURE : self::SUCCESS;
	}

	/**
	 * Trusted servers are stored with a normalized (https:// prefixed,
	 * trailing-slash trimmed) URL, so removal has to look up the id by
	 * matching that same normalization rather than the raw user input.
	 */
	private function findServerId(string $url): ?int {
		$normalized = rtrim(
			(str_starts_with($url, 'http://') || str_starts_with($url, 'https://'))
				? $url
				: 'https://' . $url,
			'/'
		);

		foreach ($this->trustedServersManager->getServers() ?? [] as $server) {
			if (rtrim($server['url'], '/') === $normalized) {
				return $server['id'];
			}
		}

		return null;
	}
}
