<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NextcloudIntegration;

use RuntimeException;

/**
 * Runs occ commands against the server under test.
 */
final class Occ {
	/**
	 * @param string $serverRoot Filesystem path of the Nextcloud checkout
	 * @param ApiClient $client Used to flush the opcode cache of the web server after a command
	 */
	public function __construct(
		private readonly string $serverRoot,
		private readonly ApiClient $client,
	) {
	}

	/**
	 * @param string[] $args Everything behind "occ", e.g. ['app:enable', '--force', 'testing']
	 */
	public function run(array $args, string $input = ''): OccResult {
		$command = 'php console.php ' . implode(' ', array_map(escapeshellarg(...), $args)) . ' --no-ansi';

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptors, $pipes, $this->serverRoot);
		if ($process === false) {
			throw new RuntimeException('Could not start occ: ' . $command);
		}

		if ($input !== '') {
			fwrite($pipes[0], $input . "\n");
		}
		fclose($pipes[0]);

		$stdOut = stream_get_contents($pipes[1]);
		$stdErr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);

		// The built-in PHP web server keeps its own opcode cache, so config
		// changes made through occ are otherwise not visible to requests.
		$this->client->request('GET', '/apps/testing/clean_opcode_cache.php');

		return new OccResult($exitCode, (string)$stdOut, (string)$stdErr);
	}

	/**
	 * Runs a command and fails loudly instead of letting a later assertion fail
	 * with an unrelated message.
	 *
	 * @param string[] $args
	 */
	public function mustRun(array $args, string $input = ''): OccResult {
		$result = $this->run($args, $input);
		if (!$result->succeeded()) {
			throw new RuntimeException(
				'occ ' . implode(' ', $args) . " failed:\n" . $result->describe()
			);
		}

		return $result;
	}

	public function setSystemConfig(string $key, string $value, string $type = 'string'): void {
		$this->mustRun(['config:system:set', $key, '--value', $value, '--type', $type]);
	}

	public function isAppEnabled(string $appId): bool {
		$result = $this->mustRun(['app:list', '--output=json']);
		$apps = json_decode($result->stdOut, true, flags: JSON_THROW_ON_ERROR);

		return isset($apps['enabled'][$appId]);
	}

	public function enableApp(string $appId): void {
		$this->mustRun(['app:enable', '--force', $appId]);
	}

	public function disableApp(string $appId): void {
		$this->mustRun(['app:disable', $appId]);
	}
}
