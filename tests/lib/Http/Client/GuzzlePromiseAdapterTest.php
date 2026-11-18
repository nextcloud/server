<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\Client;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OC\Http\Client\GuzzlePromiseAdapter;
use OCP\Http\Client\IResponse;
use OCP\Http\Client\ResponseTooLargeException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class GuzzlePromiseAdapterTest extends TestCase {
	private Promise $promise;
	private GuzzlePromiseAdapter $adapter;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->promise = new Promise();
		$this->adapter = new GuzzlePromiseAdapter($this->promise, $this->createMock(LoggerInterface::class));
	}

	public function testThenPassesTheResponse(): void {
		$received = null;
		$this->adapter->then(static function (IResponse $response) use (&$received): void {
			$received = $response;
		});

		$this->promise->resolve(new Response(201));
		Utils::queue()->run();

		$this->assertSame(201, $received?->getStatusCode());
	}

	#[DataProvider('dataThenPassesTheError')]
	public function testThenPassesTheError(\Exception $error): void {
		$received = null;
		$this->adapter->then(null, static function (\Exception $e) use (&$received): void {
			$received = $e;
		});

		$this->promise->reject($error);
		Utils::queue()->run();

		$this->assertSame($error, $received);
	}

	public static function dataThenPassesTheError(): array {
		$request = new Request('GET', 'https://server.test/');
		return [
			'request error' => [new RequestException('Not found', $request)],
			'connection error' => [new ConnectException('Connection refused', $request)],
			'response too large' => [new ResponseTooLargeException('The response body exceeds the maximum size of 4 bytes')],
		];
	}
}
