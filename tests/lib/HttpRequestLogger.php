<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use OC\Http\Client\ClientService;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Server;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Records the outgoing HTTP requests of a test run for tests/http-analyzer.php.
 * Enable by setting TEST_LOG_HTTP to the log file path.
 *
 * Covers IClientService only: a library building its own client or calling curl
 * directly stays invisible, as does any test that mocks IClientService.
 */
final class HttpRequestLogger implements IClientService {
	private const RUN_MARKER = 'TEST_LOG_HTTP_RUN';

	private function __construct(
		private IClientService $inner,
		private string $logFile,
	) {
	}

	public static function install(string $logFile): void {
		// Resolving ClientService rather than IClientService avoids recursing into
		// this decorator, and keeps the service lazy.
		/** @psalm-suppress InternalMethod */
		\OC::$server->registerService(IClientService::class, static fn (): IClientService
			=> new self(Server::get(ClientService::class), $logFile));

		// A test running in a separate process re-runs the bootstrap, so only the
		// process that owns the run may truncate. Children inherit the marker.
		if (getenv(self::RUN_MARKER) === false) {
			putenv(self::RUN_MARKER . '=' . getmypid());
			file_put_contents($logFile, '');
		}
	}

	#[\Override]
	public function newClient(?callable $handler = null): IClient {
		$next = $handler ?? new CurlHandler();

		return $this->inner->newClient(
			function (RequestInterface $request, array $options) use ($next): PromiseInterface {
				$start = microtime(true);

				return $next($request, $options)->then(
					function (ResponseInterface $response) use ($request, $start): ResponseInterface {
						$this->record($request, $start, (string)$response->getStatusCode());
						return $response;
					},
					function (mixed $reason) use ($request, $start): PromiseInterface {
						$this->record($request, $start, 'error');
						return Create::rejectionFor($reason);
					},
				);
			},
		);
	}

	private function record(RequestInterface $request, float $start, string $outcome): void {
		$line = json_encode([
			'test' => self::currentTest(),
			'method' => $request->getMethod(),
			'uri' => (string)$request->getUri(),
			'outcome' => $outcome,
			'duration' => round(microtime(true) - $start, 6),
		], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES);

		if ($line !== false) {
			file_put_contents($this->logFile, $line . "\n", FILE_APPEND);
		}
	}

	/** PHPUnit exposes no global for the running test, so walk the stack for it. */
	private static function currentTest(): string {
		$test = '(unknown)';

		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
			$class = $frame['class'] ?? '';
			$function = $frame['function'] ?? '';

			if ($class !== ''
				&& str_starts_with($function, 'test')
				&& is_subclass_of($class, \PHPUnit\Framework\TestCase::class)
			) {
				$test = $class . '::' . $function;
			}
		}

		return $test;
	}
}
