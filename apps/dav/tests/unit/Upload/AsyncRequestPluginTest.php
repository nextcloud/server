<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Upload\AsyncRequestPlugin;
use OCA\DAV\Upload\AsyncRequestService;
use OCA\DAV\Upload\FutureFile;
use OCA\DAV\Upload\UploadFolder;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;
use Test\TestCase;

/**
 * Records responses instead of writing them to the SAPI.
 */
class RecordingSapi extends Sapi {
	/** @var list<ResponseInterface> */
	public static array $sent = [];

	public static function sendResponse(ResponseInterface $response): void {
		self::$sent[] = $response;
	}
}

class AsyncRequestPluginTest extends TestCase {
	private const SOURCE = 'uploads/sharetoken/web-file-upload-123/.file';

	private Server&MockObject $server;
	private Tree&MockObject $tree;
	private AsyncRequestService&MockObject $service;
	private RequestInterface&MockObject $request;
	private ResponseInterface&MockObject $response;
	private AsyncRequestPlugin $plugin;

	/** @var array<string, string> */
	private array $responseHeaders = [];

	protected function setUp(): void {
		parent::setUp();

		RecordingSapi::$sent = [];
		$this->server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);
		$this->server->tree = $this->tree;
		$this->server->sapi = new RecordingSapi();
		$this->service = $this->createMock(AsyncRequestService::class);

		$this->request = $this->createMock(RequestInterface::class);
		$this->request->method('getPath')->willReturn(self::SOURCE);
		$this->response = $this->createMock(ResponseInterface::class);
		$this->response->method('setHeader')->willReturnCallback(function (string $name, string $value): void {
			$this->responseHeaders[$name] = $value;
		});

		$this->plugin = new AsyncRequestPlugin($this->service);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @param array<string, string> $headers
	 */
	private function setRequestHeaders(array $headers): void {
		$this->request->method('getHeader')
			->willReturnCallback(fn (string $name): ?string => $headers[$name] ?? null);
	}

	private function setAsyncHeaders(string $token = 'session-token'): void {
		$this->setRequestHeaders([
			AsyncRequestPlugin::REQUEST_HEADER => 'true',
			AsyncRequestPlugin::ID_HEADER => $token,
		]);
	}

	private function setSourceNode(?object $node): void {
		if ($node === null) {
			$this->tree->method('getNodeForPath')
				->with(self::SOURCE)
				->willThrowException(new NotFound());
			return;
		}
		$this->tree->method('getNodeForPath')
			->with(self::SOURCE)
			->willReturn($node);
	}

	public function testBeforeMoveNotRequested(): void {
		$this->setRequestHeaders([]);
		$this->service->expects($this->never())->method('isAvailable');

		$this->assertTrue($this->plugin->beforeMove($this->request, $this->response));
		$this->assertCount(0, RecordingSapi::$sent);
	}

	public function testBeforeMoveSourceIsNoChunkedUpload(): void {
		$this->setAsyncHeaders();
		$this->setSourceNode($this->createMock(Directory::class));
		$this->service->expects($this->never())->method('resolveSessionId');

		$this->assertTrue($this->plugin->beforeMove($this->request, $this->response));
	}

	public function testBeforeMoveSourceNotFound(): void {
		$this->setAsyncHeaders();
		$this->setSourceNode(null);
		$this->service->expects($this->never())->method('resolveSessionId');

		$this->assertTrue($this->plugin->beforeMove($this->request, $this->response));
	}

	public function testBeforeMoveNotAvailable(): void {
		$this->setAsyncHeaders();
		$this->setSourceNode($this->createMock(FutureFile::class));
		$this->service->method('isAvailable')->willReturn(false);
		$this->service->expects($this->never())->method('resolveSessionId');

		$this->assertTrue($this->plugin->beforeMove($this->request, $this->response));
	}

	public function testBeforeMoveInvalidToken(): void {
		$this->setAsyncHeaders('forged');
		$this->setSourceNode($this->createMock(FutureFile::class));
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->method('resolveSessionId')->with('forged')->willReturn(null);
		$this->service->expects($this->never())->method('finishRequest');

		$this->assertTrue($this->plugin->beforeMove($this->request, $this->response));
		$this->assertCount(0, RecordingSapi::$sent);
	}

	public function testBeforeMoveInvalidDestinationIsReportedSynchronously(): void {
		$this->setAsyncHeaders();
		$this->setSourceNode($this->createMock(FutureFile::class));
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->method('resolveSessionId')->willReturn('dav:session');
		$this->server->method('getCopyAndMoveInfo')
			->willThrowException(new BadRequest('The destination header was not supplied'));
		$this->service->expects($this->never())->method('finishRequest');
		$this->service->expects($this->never())->method('send');

		$this->expectException(BadRequest::class);
		$this->plugin->beforeMove($this->request, $this->response);
	}

	public function testBeforeMoveFailedPreconditions(): void {
		$this->setAsyncHeaders();
		$this->setSourceNode($this->createMock(FutureFile::class));
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->method('resolveSessionId')->willReturn('dav:session');
		$this->server->method('getCopyAndMoveInfo')->willReturn([]);
		$this->server->method('checkPreconditions')->willReturn(false);
		$this->service->expects($this->never())->method('finishRequest');
		$this->service->expects($this->never())->method('send');
		$this->response->expects($this->never())->method('setStatus');

		$this->assertFalse($this->plugin->beforeMove($this->request, $this->response));
		$this->assertSame([$this->response], RecordingSapi::$sent);
	}

	private function prepareAcceptedMove(): void {
		$this->setAsyncHeaders();
		$this->setSourceNode($this->createMock(FutureFile::class));
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->method('resolveSessionId')->with('session-token')->willReturn('dav:session');
		$this->server->method('getCopyAndMoveInfo')->willReturn([]);
		$this->server->method('checkPreconditions')->willReturn(true);
		$this->response->expects($this->once())->method('setStatus')->with(202);
		$this->service->expects($this->once())->method('finishRequest');
	}

	public function testBeforeMoveFinished(): void {
		$this->prepareAcceptedMove();
		$this->response->method('getStatus')->willReturn(201);
		$this->server->expects($this->once())
			->method('emit')
			->with('method:MOVE', [$this->request, $this->response])
			->willReturnCallback(function (): bool {
				// The finalized upload lands under the rewritten destination
				$this->plugin->afterMove(self::SOURCE, 'files/sharetoken/Folder/image (2).jpg');
				return true;
			});
		$this->service->expects($this->once())
			->method('send')
			->with('dav:session', [
				'upload' => 'web-file-upload-123',
				'status' => AsyncRequestPlugin::STATUS_FINISHED,
				'http_status' => 201,
				'path' => 'Folder/image (2).jpg',
			]);

		$this->assertFalse($this->plugin->beforeMove($this->request, $this->response));
		$this->assertSame([$this->response], RecordingSapi::$sent);
		$this->assertSame('session-token', $this->responseHeaders[AsyncRequestPlugin::ID_HEADER]);
		$this->assertSame('0', $this->responseHeaders['Content-Length']);
	}

	public function testBeforeMoveFailedWithDavException(): void {
		$this->prepareAcceptedMove();
		$exception = new BadRequest('Chunks on server do not sum up to 10 but to 5 bytes');
		$emitted = [];
		$this->server->method('emit')
			->willReturnCallback(function (string $event, array $arguments) use (&$emitted, $exception): bool {
				$emitted[] = [$event, $arguments];
				if ($event === 'method:MOVE') {
					throw $exception;
				}
				return true;
			});
		$this->service->expects($this->once())
			->method('send')
			->with('dav:session', [
				'upload' => 'web-file-upload-123',
				'status' => AsyncRequestPlugin::STATUS_FAILED,
				'http_status' => 400,
				'message' => 'Chunks on server do not sum up to 10 but to 5 bytes',
			]);

		$this->assertFalse($this->plugin->beforeMove($this->request, $this->response));
		$this->assertSame([
			['method:MOVE', [$this->request, $this->response]],
			['exception', [$exception]],
		], $emitted);
	}

	public function testBeforeMoveFailedWithUnexpectedException(): void {
		$this->prepareAcceptedMove();
		$this->server->method('emit')
			->willReturnCallback(function (string $event): bool {
				if ($event === 'method:MOVE') {
					throw new \RuntimeException('Storage is gone');
				}
				return true;
			});
		$this->service->expects($this->once())
			->method('send')
			->with('dav:session', [
				'upload' => 'web-file-upload-123',
				'status' => AsyncRequestPlugin::STATUS_FAILED,
				'http_status' => 500,
				'message' => 'Storage is gone',
			]);

		$this->assertFalse($this->plugin->beforeMove($this->request, $this->response));
	}

	public function testAfterMkcolNotRequested(): void {
		$this->setRequestHeaders([]);
		$this->service->expects($this->never())->method('isAvailable');
		$this->response->expects($this->never())->method('setHeader');

		$this->plugin->afterMkcol($this->request, $this->response);
	}

	public function testAfterMkcolNotAnUploadFolder(): void {
		$this->setRequestHeaders([AsyncRequestPlugin::REQUEST_HEADER => 'true']);
		$this->setSourceNode($this->createMock(Directory::class));
		$this->service->expects($this->never())->method('createSessionToken');
		$this->response->expects($this->never())->method('setHeader');

		$this->plugin->afterMkcol($this->request, $this->response);
	}

	public function testAfterMkcolNotAvailable(): void {
		$this->setRequestHeaders([AsyncRequestPlugin::REQUEST_HEADER => 'true']);
		$this->setSourceNode($this->createMock(UploadFolder::class));
		$this->service->method('isAvailable')->willReturn(false);
		$this->service->expects($this->never())->method('createSessionToken');
		$this->response->expects($this->never())->method('setHeader');

		$this->plugin->afterMkcol($this->request, $this->response);
	}

	public function testAfterMkcolCreatesSession(): void {
		$this->setRequestHeaders([AsyncRequestPlugin::REQUEST_HEADER => 'true']);
		$this->setSourceNode($this->createMock(UploadFolder::class));
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->method('resolveSessionId')->with('')->willReturn(null);
		$this->service->expects($this->once())->method('createSessionToken')->willReturn('new-token');

		$this->plugin->afterMkcol($this->request, $this->response);
		$this->assertSame('new-token', $this->responseHeaders[AsyncRequestPlugin::ID_HEADER]);
	}

	public function testAfterMkcolReusesSession(): void {
		$this->setAsyncHeaders('existing-token');
		$this->setSourceNode($this->createMock(UploadFolder::class));
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->method('resolveSessionId')->with('existing-token')->willReturn('dav:session');
		$this->service->expects($this->never())->method('createSessionToken');

		$this->plugin->afterMkcol($this->request, $this->response);
		$this->assertSame('existing-token', $this->responseHeaders[AsyncRequestPlugin::ID_HEADER]);
	}
}
