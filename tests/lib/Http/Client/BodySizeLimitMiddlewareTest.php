<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\Client;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OC\Http\Client\BodySizeLimitMiddleware;
use OCP\Http\Client\ResponseTooLargeException;
use OCP\ITempManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Test\TestCase;

class BodySizeLimitMiddlewareTest extends TestCase {
	private const URL = 'https://server.test/file';
	private const LIMIT = ['nextcloud' => ['max_body_size' => 4]];

	/** Writes queued responses into the sink without checking for refused writes */
	private MockHandler $handler;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->handler = new MockHandler();
	}

	private function send(array $options, string $method = 'GET'): PromiseInterface {
		return (new BodySizeLimitMiddleware())($this->handler)(new Request($method, self::URL), $options);
	}

	public function testRequestWithoutLimitIsPassedOnUnchanged(): void {
		$options = ['timeout' => 5, 'stream' => true, 'nextcloud' => ['allow_local_address' => false]];
		$this->handler->append(new Response(200));

		$this->send($options)->wait();

		$this->assertSame($options, $this->handler->getLastOptions());
	}

	public function testBodyUpToTheLimitIsReturned(): void {
		$this->handler->append(new Response(200, [], 'abcd'));

		$response = $this->send(self::LIMIT)->wait();

		$this->assertSame('abcd', (string)$response->getBody());
	}

	public function testRefusedWriteFailsTheRequest(): void {
		$this->handler->append(static function (RequestInterface $request, array $options): RequestException {
			$options['sink']->write('abcde');
			return new RequestException('cURL error 23: Failure writing output to destination', $request);
		});

		$this->expectException(ResponseTooLargeException::class);
		$this->expectExceptionMessage('The response body exceeds the maximum size of 4 bytes');

		$this->send(self::LIMIT)->wait();
	}

	public function testCutOffBodyFailsTheRequest(): void {
		$this->handler->append(new Response(200, [], 'abcde'));

		$this->expectException(ResponseTooLargeException::class);
		$this->expectExceptionMessage('The response body exceeds the maximum size of 4 bytes');

		$this->send(self::LIMIT)->wait();
	}

	public function testAnnouncedLargerBodyFailsBeforeItArrives(): void {
		$this->handler->append(new Response(404, ['Content-Length' => '5']));

		try {
			$this->send(self::LIMIT)->wait();
			$this->fail('The request did not fail');
		} catch (ResponseTooLargeException $e) {
			$this->assertSame('The response body exceeds the maximum size of 4 bytes', $e->getMessage());
			$this->assertInstanceOf(RequestException::class, $e->getPrevious());
			$this->assertSame(404, $e->getPrevious()->getResponse()?->getStatusCode());
		}
	}

	#[DataProvider('dataAnnouncedSizeIsIgnoredWithoutMatchingBody')]
	public function testAnnouncedSizeIsIgnoredWithoutMatchingBody(string $method, int $status, array $headers): void {
		$this->handler->append(new Response($status, $headers));

		$response = $this->send(self::LIMIT, $method)->wait();

		$this->assertSame($status, $response->getStatusCode());
	}

	public static function dataAnnouncedSizeIsIgnoredWithoutMatchingBody(): array {
		return [
			'HEAD request' => ['HEAD', 200, ['Content-Length' => '5']],
			'not modified' => ['GET', 304, ['Content-Length' => '5']],
			'decoded by Guzzle' => ['GET', 200, ['Content-Length' => '5', 'X-Encoded-Content-Encoding' => 'gzip']],
			'invalid' => ['GET', 200, ['Content-Length' => '5e3']],
		];
	}

	public function testCallersOnHeadersIsCalled(): void {
		$response = new Response(200, ['Content-Length' => '4'], 'abcd');
		$this->handler->append($response);
		$headersResponse = null;

		$this->send(self::LIMIT + ['on_headers' => static function (ResponseInterface $response) use (&$headersResponse): void {
			$headersResponse = $response;
		}])->wait();

		$this->assertSame($response, $headersResponse);
	}

	public function testOtherErrorsArePassedOn(): void {
		$error = new ConnectException('Connection refused', new Request('GET', self::URL));
		$this->handler->append($error);

		$this->expectExceptionObject($error);

		$this->send(self::LIMIT)->wait();
	}

	public function testStreamOptionIsRemoved(): void {
		$this->handler->append(new Response(200));

		$this->send(self::LIMIT + ['stream' => true])->wait();

		$this->assertArrayNotHasKey('stream', $this->handler->getLastOptions());
	}

	public function testCallersSinkReceivesTheBody(): void {
		$sink = fopen('php://temp', 'r+');
		$this->handler->append(new Response(200, [], 'abcd'));

		$this->send(self::LIMIT + ['sink' => $sink])->wait();

		rewind($sink);
		$this->assertSame('abcd', stream_get_contents($sink));
	}

	public function testCallersSinkPathReceivesTheBody(): void {
		$path = Server::get(ITempManager::class)->getTemporaryFile();
		$this->handler->append(new Response(200, [], 'abcd'));

		$this->send(self::LIMIT + ['sink' => $path])->wait();

		$this->assertSame('abcd', file_get_contents($path));
	}

	public function testSinkPathInMissingDirectoryFails(): void {
		$path = Server::get(ITempManager::class)->getTemporaryFolder() . '/missing/body';

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('does not exist for sink value');

		$this->send(self::LIMIT + ['sink' => $path]);
	}

	public function testInvalidLimitFails(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->send(['nextcloud' => ['max_body_size' => -1]]);
	}
}
