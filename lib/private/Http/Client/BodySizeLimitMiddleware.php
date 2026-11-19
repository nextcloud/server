<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Http\Client;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use OCP\Http\Client\ResponseTooLargeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle middleware for the "max_body_size" option of the "nextcloud" request options
 *
 * A response that announces a larger body fails right after its headers. Otherwise the
 * body is written into a SizeLimitedSink. cURL writes decompressed data unless
 * "decode_content" is false, so the limit also holds for compressed responses.
 */
class BodySizeLimitMiddleware {
	public function __invoke(callable $handler): callable {
		return static function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
			$maxSize = $options['nextcloud']['max_body_size'] ?? null;
			if ($maxSize === null) {
				return $handler($request, $options);
			}
			if (!is_int($maxSize) || $maxSize < 0) {
				throw new \InvalidArgumentException('max_body_size must be a non-negative integer');
			}

			$target = $options['sink'] ?? null;
			if (is_string($target)) {
				if (!is_dir(dirname($target))) {
					throw new \RuntimeException(sprintf('Directory %s does not exist for sink value of %s', dirname($target), $target));
				}
				$target = new LazyOpenStream($target, 'w+');
			}
			$sink = new SizeLimitedSink(Utils::streamFor($target), $maxSize);
			$options['sink'] = $sink;
			// Handlers only write into the sink without "stream"
			unset($options['stream']);

			$tooLarge = static fn (?\Throwable $previous = null): ResponseTooLargeException
				=> new ResponseTooLargeException(sprintf('The response body exceeds the maximum size of %d bytes', $maxSize), 0, $previous);

			$onHeaders = $options['on_headers'] ?? null;
			// Guzzle's MockHandler also passes queued exceptions and callables
			$options['on_headers'] = static function (mixed $response) use ($request, $maxSize, $onHeaders, $tooLarge): void {
				if ($response instanceof ResponseInterface && self::announcesLargerBody($request, $response, $maxSize)) {
					throw $tooLarge();
				}
				if ($onHeaders !== null) {
					$onHeaders($response);
				}
			};

			return $handler($request, $options)->then(
				static function (ResponseInterface $response) use ($sink, $tooLarge): ResponseInterface {
					// Handlers that ignore refused writes return a cut-off body
					if ($sink->isExceeded()) {
						throw $tooLarge();
					}
					return $response;
				},
				static function (mixed $reason) use ($sink, $tooLarge): PromiseInterface {
					// Handlers wrap exceptions thrown by on_headers
					if ($reason instanceof RequestException && $reason->getPrevious() instanceof ResponseTooLargeException) {
						throw $tooLarge($reason);
					}
					if ($sink->isExceeded()) {
						throw $tooLarge($reason instanceof \Throwable ? $reason : null);
					}
					return Create::rejectionFor($reason);
				},
			);
		};
	}

	/**
	 * Responses to HEAD requests and 304 responses announce a body they don't have, and
	 * Guzzle replaces the Content-Length of responses it decodes
	 */
	private static function announcesLargerBody(RequestInterface $request, ResponseInterface $response, int $maxSize): bool {
		if ($request->getMethod() === 'HEAD' || $response->getStatusCode() === 304 || $response->hasHeader('X-Encoded-Content-Encoding')) {
			return false;
		}
		$contentLength = $response->getHeaderLine('Content-Length');
		return ctype_digit($contentLength) && (int)$contentLength > $maxSize;
	}
}
