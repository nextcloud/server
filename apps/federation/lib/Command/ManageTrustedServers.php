<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Federation\Command;

use OCA\Federation\TrustedServers;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

class ManageTrustedServers {
	private const STATUS_LABELS = [
		TrustedServers::STATUS_OK => 'ok',
		TrustedServers::STATUS_PENDING => 'pending',
		TrustedServers::STATUS_FAILURE => 'failure',
		TrustedServers::STATUS_ACCESS_REVOKED => 'access revoked',
	];

	public function __construct(
		private TrustedServers $trustedServersManager,
	) {
	}

	#[AsCommand(
		name: 'federation:trusted-servers:add',
		description: 'Add trusted servers',
	)]
	public function add(
		IOutput $output,
		#[Argument(description: 'space-separated list of server URLs to add to the trusted servers list')]
		array $servers = [],
	): ExitCode {
		if (empty($servers)) {
			$output->writeln('<error>You must provide at least one server URL.</error>');
			return ExitCode::Invalid;
		}

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

		return $hasFailure ? ExitCode::Failure : ExitCode::Success;
	}

	#[AsCommand(
		name: 'federation:trusted-servers:remove',
		description: 'Remove trusted servers',
	)]
	public function remove(
		IOutput $output,
		#[Argument(description: 'space-separated list of server URLs to remove from the trusted servers list')]
		array $servers = [],
	): ExitCode {
		if (empty($servers)) {
			$output->writeln('<error>You must provide at least one server URL.</error>');
			return ExitCode::Invalid;
		}

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

		return $hasFailure ? ExitCode::Failure : ExitCode::Success;
	}

	#[AsCommand(
		name: 'federation:trusted-servers:list',
		description: 'List trusted servers',
		supportsOutputFormat: true,
	)]
	public function list(
		IOutput $output,
	): ExitCode {
		$servers = $this->trustedServersManager->getServers();

		if (empty($servers)) {
			$output->writeln('No trusted servers configured.');
			return ExitCode::Success;
		}

		$output->writeTableInOutputFormat(
			array_map(fn (array $server) => [
				'id' => $server['id'],
				'url' => $server['url'],
				'status' => self::STATUS_LABELS[$server['status']] ?? (string)$server['status'],
			], $servers),
		);

		return ExitCode::Success;
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
