<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\Client;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\Is;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OC\Http\Client\CurlStreamHandler;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Test\TestCase;

class CurlStreamHandlerTest extends TestCase {
	private Request $request;
	private CurlHandler&MockObject $handler;
	private Promise $transfer;
	/** Options the handler passed to cURL */
	private array $curlOptions = [];
	/** @var list<\Closure(): void> what cURL does on each tick */
	private array $ticks = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->request = new Request('GET', 'https://server.test/file');
		$this->handler = $this->createMock(CurlHandler::class);
		$this->transfer = new Promise();
	}

	private function send(array $options = []): PromiseInterface {
		$curl = $this->createMock(CurlMultiHandler::class);
		$curl->method('__invoke')
			->willReturnCallback(function (RequestInterface $request, array $options): PromiseInterface {
				$this->curlOptions = $options;
				return $this->transfer;
			});
		$curl->method('tick')
			->willReturnCallback(function (): void {
				$tick = array_shift($this->ticks);
				if ($tick === null) {
					throw new \LogicException('cURL was ticked more often than the test expects');
				}
				$tick();
				// Like CurlMultiHandler::tick(), settle the promises
				Utils::queue()->run();
			});
		$handler = new CurlStreamHandler($this->handler, static fn (): CurlMultiHandler => $curl);

		return $handler($this->request, $options + ['stream' => true]);
	}

	private function receiveHeaders(int $status = 200): \Closure {
		return fn () => $this->curlOptions['on_headers'](new Response($status));
	}

	private function receive(string $data): \Closure {
		return fn () => $this->curlOptions['sink']->write($data);
	}

	private function complete(): \Closure {
		return fn () => $this->transfer->resolve(new Response(200));
	}

	/**
	 * Returns the ticks of a successful response with the given body chunks
	 *
	 * @return list<\Closure(): void>
	 */
	private function respond(string ...$chunks): array {
		return [$this->receiveHeaders(), ...array_map($this->receive(...), $chunks), $this->complete()];
	}

	/**
	 * Returns ticks of a server that sends nothing for 500 milliseconds
	 *
	 * @return list<\Closure(): void>
	 */
	private function stall(): array {
		return array_fill(0, 10, static fn () => usleep(50000));
	}

	/**
	 * Like CurlFactory::finish(), cURL errors fulfil the transfer with a rejected promise
	 */
	private function failTransfer(\Throwable $error): \Closure {
		return fn () => $this->transfer->resolve(Create::rejectionFor($error));
	}

	#[TestWith([['stream' => false]])]
	#[TestWith([['sink' => 'php://temp']])]
	public function testRequestIsPassedToTheHandler(array $options): void {
		$response = Create::promiseFor(new Response(200));
		$this->handler->expects($this->once())
			->method('__invoke')
			->with($this->request, $options + ['stream' => true])
			->willReturn($response);

		$result = $this->send($options);

		$this->assertSame($response, $result);
	}

	public function testResponseIsReturnedWhenTheBodyStartsToArrive(): void {
		$this->ticks = $this->respond('chunk0', 'chunk1');

		$response = $this->send()->wait();
		$ticksLeft = count($this->ticks);
		$body = $response->getBody()->getContents();

		$this->assertSame(2, $ticksLeft);
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('chunk0chunk1', $body);
	}

	public function testResponseOfTheLastHeaderBlockIsReturned(): void {
		$this->ticks = [$this->receiveHeaders(100), $this->receiveHeaders(201), $this->receive('created'), $this->complete()];

		$response = $this->send()->wait();

		$this->assertSame(201, $response->getStatusCode());
		$this->assertSame('created', (string)$response->getBody());
	}

	public function testCallersHeaderCallbackIsCalled(): void {
		$this->ticks = $this->respond();
		$statuses = [];

		$this->send(['on_headers' => static function (ResponseInterface $response) use (&$statuses): void {
			$statuses[] = $response->getStatusCode();
		}])->wait();

		$this->assertSame([200], $statuses);
	}

	public function testRequestOptionsArePassedToCurl(): void {
		$this->ticks = $this->respond();

		$response = $this->send(['curl' => [CURLOPT_RESOLVE => ['server.test:443:192.0.2.1']], 'timeout' => 5])->wait();

		$this->assertSame([CURLOPT_RESOLVE => ['server.test:443:192.0.2.1']], $this->curlOptions['curl']);
		$this->assertArrayNotHasKey('timeout', $this->curlOptions);
		$this->assertSame('php://temp', $this->curlOptions['sink']->getMetadata('uri'));
		$this->assertSame(200, $response->getStatusCode());
	}

	public function testBodyIsSeekable(): void {
		$this->ticks = $this->respond('chunk0', 'chunk1');
		$body = $this->send()->wait()->getBody();

		$body->seek(0, SEEK_END);
		$size = $body->tell();
		$body->rewind();

		$this->assertSame(12, $size);
		$this->assertSame(12, $body->getSize());
		$this->assertSame('chunk0chunk1', $body->getContents());
	}

	public function testSeekRelativeToTheCurrentPosition(): void {
		$this->ticks = $this->respond('chunk0', 'chunk1');
		$body = $this->send()->wait()->getBody();
		$body->read(2);

		$body->seek(4, SEEK_CUR);

		$this->assertSame('chunk1', $body->getContents());
	}

	#[TestWith([-1, SEEK_SET, \RuntimeException::class])]
	#[TestWith([0, 99, \InvalidArgumentException::class])]
	public function testInvalidSeekFails(int $offset, int $whence, string $exception): void {
		$this->ticks = $this->respond('chunk0');
		$body = $this->send()->wait()->getBody();

		$this->expectException($exception);

		$body->seek($offset, $whence);
	}

	public function testBodyIsReadOnlyWithoutResource(): void {
		$this->ticks = $this->respond('chunk0');
		$body = $this->send()->wait()->getBody();

		$resource = $body->detach();

		$this->assertNull($resource);
		$this->assertFalse($body->isWritable());
		$this->expectException(\RuntimeException::class);
		$body->write('data');
	}

	public function testReadingAfterSeekingAheadWaitsForTheData(): void {
		$this->ticks = $this->respond('chunk0', 'chunk1', 'chunk2');
		$body = $this->send()->wait()->getBody();

		$body->seek(12);

		$this->assertSame('chunk2', $body->getContents());
	}

	public function testSeekingPastTheEndIsAllowed(): void {
		$this->ticks = $this->respond('chunk0');
		$body = $this->send()->wait()->getBody();

		$body->seek(100);

		$this->assertSame(100, $body->tell());
		$this->assertSame('', $body->read(10));
		$this->assertTrue($body->eof());
	}

	public function testClosingTheBodyCancelsTheTransfer(): void {
		$this->ticks = [$this->receiveHeaders(), $this->receive('chunk0')];
		$body = $this->send()->wait()->getBody();

		$body->close();

		$this->assertTrue(Is::rejected($this->transfer));
	}

	public function testDroppingAnUnfinishedBodyEndsTheTransfer(): void {
		$this->ticks = [$this->receiveHeaders(), $this->receive('chunk0')];
		$response = $this->send()->wait();
		$sink = $this->curlOptions['sink'];

		unset($response);

		$this->assertTrue(Is::rejected($this->transfer));
		$this->assertFalse($sink->isReadable());
	}

	public function testClosingAClonedBodyKeepsTheTransfer(): void {
		$this->ticks = $this->respond('chunk0', 'chunk1');
		$body = $this->send()->wait()->getBody();
		$clone = clone $body;

		$clone->close();
		unset($clone);

		$this->assertTrue(Is::pending($this->transfer));
		$this->assertSame('chunk0chunk1', $body->getContents());
	}

	public function testTimeoutIgnoresTimeSpentBetweenReads(): void {
		$this->ticks = $this->respond('chunk0', 'chunk1');
		$body = $this->send(['timeout' => 0.1])->wait()->getBody();
		$first = $body->read(6);
		usleep(200000);

		$rest = $body->getContents();

		$this->assertSame('chunk0', $first);
		$this->assertSame('chunk1', $rest);
	}

	public function testTimeoutWhileWaitingForTheServer(): void {
		$this->ticks = [$this->receiveHeaders(), $this->receive('chunk0'), $this->receive('chunk1'), ...$this->stall()];
		$body = $this->send(['timeout' => 0.1])->wait()->getBody();
		$body->seek(20);

		try {
			$body->read(1);
			$this->fail('Expected a timeout');
		} catch (ConnectException $e) {
			$this->assertStringStartsWith('cURL error 28: Operation timed out', $e->getMessage());
			$this->assertStringEndsWith('for https://server.test/file', $e->getMessage());
			$this->assertSame(CURLE_OPERATION_TIMEDOUT, $e->getHandlerContext()['errno']);
			$this->assertSame('https://server.test/file', $e->getHandlerContext()['url']);
		}

		// Data that arrived before the timeout stays readable
		$this->assertTrue(Is::rejected($this->transfer));
		$body->rewind();
		$this->assertSame('chunk0chunk1', $body->read(12));
		$this->expectExceptionMessageMatches('/^cURL error 28: Operation timed out/');
		$body->read(1);
	}

	public function testTimeoutBeforeTheBodyRejectsTheRequest(): void {
		$this->ticks = [$this->receiveHeaders(), ...$this->stall()];

		$this->expectException(ConnectException::class);
		$this->expectExceptionMessageMatches('/^cURL error 28: Operation timed out/');

		$this->send(['timeout' => 0.1])->wait();
	}

	public function testErrorBeforeTheBodyRejectsTheRequest(): void {
		$error = new ConnectException('Connection refused', $this->request);
		$this->ticks = [$this->failTransfer($error)];

		$this->expectExceptionObject($error);

		$this->send()->wait();
	}

	public function testErrorAfterTheHeadersRejectsTheRequest(): void {
		$error = new ConnectException('Connection reset', $this->request);
		$this->ticks = [$this->receiveHeaders(), $this->failTransfer($error)];

		$this->expectExceptionObject($error);

		$this->send()->wait();
	}

	public function testResponseOfARejectedRequestKeepsItsBody(): void {
		$this->ticks = [$this->receiveHeaders(), function (): void {
			$response = new Response(200, [], $this->curlOptions['sink']);
			$this->transfer->resolve(Create::rejectionFor(new RequestException('Connection reset', $this->request, $response)));
		}];

		try {
			$this->send()->wait();
			$this->fail('Expected the transfer error');
		} catch (RequestException $e) {
			$body = $e->getResponse()->getBody()->getContents();
		}

		$this->assertSame('', $body);
	}

	public function testErrorDuringTheBodyIsThrownWhenReading(): void {
		$error = new ConnectException('Connection reset', $this->request);
		$this->ticks = [$this->receiveHeaders(), $this->receive('chunk0'), $this->failTransfer($error)];
		$body = $this->send()->wait()->getBody();
		$first = $body->read(6);

		try {
			$body->getContents();
			$this->fail('Expected the transfer error');
		} catch (ConnectException $e) {
			$this->assertSame($error, $e);
		}

		$this->assertSame('chunk0', $first);
		$this->assertNull($body->getSize());
	}

	public function testEndIsReachedOnceTheErrorWasThrown(): void {
		$error = new ConnectException('Connection reset', $this->request);
		$this->ticks = [$this->receiveHeaders(), $this->receive('chunk0'), $this->failTransfer($error)];
		$body = $this->send()->wait()->getBody();
		$body->read(6);
		$body->waitFor(PHP_INT_MAX);
		$endBeforeTheError = $body->eof();

		try {
			$body->read(1);
			$this->fail('Expected the transfer error');
		} catch (ConnectException $e) {
			$this->assertSame($error, $e);
		}

		$this->assertFalse($endBeforeTheError);
		$this->assertTrue($body->eof());
	}

	/**
	 * Like CurlFactory::retryFailedRewind(), a retry fulfils the transfer with the retried transfer
	 */
	private function retry(Promise $retry): \Closure {
		return fn () => $this->transfer->resolve($retry);
	}

	public function testResponseOfARetriedTransferIsReturned(): void {
		$retry = new Promise();
		$this->ticks = [$this->retry($retry), $this->receiveHeaders(), $this->receive('retried'), static fn () => $retry->resolve(new Response(200))];

		$response = $this->send()->wait();

		$this->assertSame('retried', (string)$response->getBody());
	}

	public function testTimeoutCoversARetriedTransfer(): void {
		$this->ticks = [$this->retry(new Promise()), ...$this->stall()];

		$this->expectException(ConnectException::class);
		$this->expectExceptionMessage('Operation timed out after');

		$this->send(['timeout' => 0.2])->wait();
	}

	public function testDelayIsAppliedBeforeTheTransfer(): void {
		$this->ticks = $this->respond();
		$start = hrtime(true);

		$this->send(['delay' => 100])->wait();
		$seconds = (hrtime(true) - $start) / 1e9;

		$this->assertGreaterThanOrEqual(0.1, $seconds);
		$this->assertArrayNotHasKey('delay', $this->curlOptions);
	}
}
