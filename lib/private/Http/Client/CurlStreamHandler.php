<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Http\Client;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle handler that streams the responses of requests with the "stream" option
 * and passes all other requests, including those with a "sink", to $handler
 *
 * The transfer runs on cURL, so DNS pinning and all other cURL options apply.
 * The response is returned as soon as its body starts to arrive, and the rest
 * of the body is downloaded while it is read.
 */
class CurlStreamHandler {
	/** @var \Closure(): CurlMultiHandler */
	private \Closure $createCurl;

	/**
	 * @param ?\Closure(): CurlMultiHandler $createCurl creates the cURL handler for each streamed request
	 */
	public function __construct(
		private CurlHandler $handler,
		?\Closure $createCurl = null,
	) {
		$this->createCurl = $createCurl ?? static fn (): CurlMultiHandler => new CurlMultiHandler(['select_timeout' => 0.1]);
	}

	public function __invoke(RequestInterface $request, array $options): PromiseInterface {
		if (empty($options['stream']) || isset($options['sink'])) {
			return ($this->handler)($request, $options);
		}

		if (isset($options['delay'])) {
			usleep((int)($options['delay'] * 1000));
			unset($options['delay']);
		}

		$timeout = (float)($options['timeout'] ?? 0);
		unset($options['timeout']);

		$response = null;
		$onHeaders = $options['on_headers'] ?? null;
		$options['on_headers'] = static function (ResponseInterface $headerResponse) use ($onHeaders, &$response): void {
			if ($onHeaders !== null) {
				$onHeaders($headerResponse);
			}
			$response = $headerResponse;
		};
		$sink = Utils::streamFor();
		$options['sink'] = $sink;

		$curl = ($this->createCurl)();
		// CurlFactory fulfils the transfer with another promise for errors and retries, which then() follows
		$transfer = $curl($request, $options)->then();
		$body = new CurlStreamBody($request, $curl, $transfer, $sink, $timeout);
		try {
			$body->waitFor(0);
		} catch (\Throwable $e) {
			return Create::rejectionFor($e);
		}

		if ($response === null || $body->failure() !== null) {
			return $transfer;
		}
		return Create::promiseFor($response->withBody($body));
	}
}
