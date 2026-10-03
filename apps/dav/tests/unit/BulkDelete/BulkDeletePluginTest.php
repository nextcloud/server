<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\BulkDelete;

use OCA\DAV\BulkDelete\BulkDeletePlugin;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Sabre\Xml\Service;

class BulkDeletePluginTest extends TestCase {
	private Server $server;
	private Tree&MockObject $tree;
	private BulkDeletePlugin $plugin;
	private array $nodes = [];
	private array $deleted = [];

	protected function setUp(): void {
		$this->tree = $this->createMock(Tree::class);
		$this->tree->method('getNodeForPath')->willReturnCallback(function (string $path) {
			if (!isset($this->nodes[$path])) {
				throw new NotFound();
			}
			return $this->nodes[$path];
		});
		$this->tree->method('delete')->willReturnCallback(function (string $path): void {
			$this->nodes[$path]->delete();
			$this->deleted[] = $path;
			unset($this->nodes[$path]);
		});
		$this->nodes['files/alice'] = $this->createMock(Directory::class);
		$this->server = new Server($this->tree);
		$this->server->setBaseUri('/nextcloud/remote.php/dav/');
		$this->plugin = new BulkDeletePlugin('alice', new NullLogger());
		$this->server->addPlugin($this->plugin);
	}

	private function addFile(string $path): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getInternalPath')->willReturn('files/' . $path);
		$this->nodes['files/alice/' . $path] = $file;
		return $file;
	}

	/** @param string[] $hrefs */
	private function request(array $hrefs, string $container = 'files/alice'): Response {
		$writer = (new Service())->getWriter();
		$writer->openMemory();
		$writer->startDocument();
		$writer->startElement('{DAV:}delete');
		$writer->startElement('{DAV:}target');
		foreach ($hrefs as $href) {
			$writer->writeElement('{DAV:}href', $href);
		}
		$writer->endElement();
		$writer->endElement();
		$body = $writer->outputMemory();

		$request = new Request('BDELETE', '/nextcloud/remote.php/dav/' . $container . '/', [
			'Content-Type' => 'application/xml; charset=utf-8',
		], $body);
		$request->setBaseUrl($this->server->getBaseUri());
		$response = new Response();
		$this->server->httpRequest = $request;
		$this->server->httpResponse = $response;
		$this->server->invokeMethod($request, $response, false);
		self::assertSame($request, $this->server->httpRequest);
		self::assertSame($response, $this->server->httpResponse);
		return $response;
	}

	/** @return array<string, int> */
	private function failures(Response $response): array {
		self::assertSame(207, $response->getStatus());
		$elements = (new Service())->expect('{DAV:}multistatus', $response->getBodyAsString());
		self::assertIsArray($elements);
		$failures = [];
		foreach ($elements as $item) {
			self::assertSame('{DAV:}response', $item['name']);
			self::assertIsArray($item['value']);
			$href = null;
			$status = null;
			foreach ($item['value'] as $child) {
				if ($child['name'] === '{DAV:}href') {
					$href = $child['value'];
				} elseif ($child['name'] === '{DAV:}status') {
					$status = $child['value'];
				}
			}
			self::assertIsString($href);
			self::assertIsString($status);
			self::assertMatchesRegularExpression('/^HTTP\/1\.1 \d{3} /', $status);
			$failures[$href] = (int)substr($status, 9, 3);
		}
		return $failures;
	}

	public function testUsesSingleRequestAndDavUnbindLifecycle(): void {
		$this->addFile('one.txt');
		$this->addFile('two.txt');
		$before = [];
		$after = [];
		$methodCalls = 0;
		$this->server->on('beforeMethod:BDELETE', function (Request $request) use (&$methodCalls): void {
			self::assertSame($request, $this->server->httpRequest);
			$methodCalls++;
		});
		$this->server->on('beforeUnbind', function (string $path) use (&$before): void {
			$before[] = $path;
		});
		$this->server->on('afterUnbind', function (string $path) use (&$after): void {
			$after[] = $path;
		});

		$response = $this->request(['one.txt', 'two.txt']);
		self::assertSame(204, $response->getStatus());
		self::assertSame(1, $methodCalls);
		self::assertSame(['files/alice/one.txt', 'files/alice/two.txt'], $before);
		self::assertSame($before, $this->deleted);
		self::assertSame($before, $after);
	}

	public function testFailureStopsBatchAndReportsFailedDependency(): void {
		$this->addFile('one');
		$this->addFile('two')->method('delete')->willThrowException(new Forbidden());
		$this->addFile('three')->expects(self::never())->method('delete');

		$response = $this->request(['one', 'two', 'three']);
		self::assertSame([
			'two' => 403,
			'three' => 424,
		], $this->failures($response));
		self::assertSame(['files/alice/one'], $this->deleted);
	}

	public function testMissingFileIsReportedInMultistatus(): void {
		$response = $this->request(['missing']);
		self::assertSame(['missing' => 404], $this->failures($response));
	}

	public function testFoldersAreRejected(): void {
		$this->nodes['files/alice/folder'] = $this->createMock(Directory::class);
		$response = $this->request(['folder']);
		self::assertSame(['folder' => 403], $this->failures($response));
	}

	public function testMountRootFileIsRejected(): void {
		$file = $this->createMock(File::class);
		$file->method('getInternalPath')->willReturn('');
		$file->expects(self::never())->method('delete');
		$this->nodes['files/alice/shared-file'] = $file;
		$response = $this->request(['shared-file']);
		self::assertSame(['shared-file' => 403], $this->failures($response));
	}

	public function testSilentUnbindVetoIsNotReportedAsSuccess(): void {
		$this->addFile('one')->expects(self::never())->method('delete');
		$this->server->on('beforeUnbind', static fn (): bool => false);
		$response = $this->request(['one']);
		self::assertSame(['one' => 403], $this->failures($response));
	}

	public function testThrowableDoesNotLeakDetails(): void {
		$this->addFile('one')->method('delete')->willThrowException(new \RuntimeException('secret-storage-detail'));
		$response = $this->request(['one']);
		self::assertStringNotContainsString('secret-storage-detail', $response->getBodyAsString());
		self::assertSame(['one' => 500], $this->failures($response));
	}

	public function testUnicodeAndLiteralPercentAreDecodedExactlyOnce(): void {
		$names = ['café + #.txt', '日本語.txt', 'literal%2Fname.txt', 'emoji-😀.txt'];
		$hrefs = [];
		foreach ($names as $name) {
			$this->addFile('folder/' . $name);
			$hrefs[] = 'folder/' . rawurlencode($name);
		}
		$response = $this->request($hrefs);
		self::assertSame(204, $response->getStatus());
		self::assertSame(array_map(static fn (string $name): string => 'files/alice/folder/' . $name, $names), $this->deleted);
	}

	public function testFailureResponsePreservesEncodedHref(): void {
		$this->addFile('café + #.txt')->method('delete')->willThrowException(new Forbidden());
		$href = 'caf%C3%A9%20%2B%20%23.txt';
		$response = $this->request([$href]);
		self::assertSame([$href => 403], $this->failures($response));
	}

	public function testTargetIsAlwaysRelativeToRequestCollection(): void {
		$this->nodes['files/alice/folder'] = $this->createMock(Directory::class);
		$this->addFile('folder/files/bob/one');
		$response = $this->request(['files/bob/one'], 'files/alice/folder');
		self::assertSame(204, $response->getStatus());
		self::assertSame(['files/alice/folder/files/bob/one'], $this->deleted);
	}

	#[DataProvider('invalidBodies')]
	public function testInvalidBodyHasNoSideEffects(string $body): void {
		$this->tree->expects(self::never())->method('delete');
		$request = new Request('BDELETE', '/nextcloud/remote.php/dav/files/alice/', [
			'Content-Type' => 'application/xml',
		], $body);
		$request->setBaseUrl($this->server->getBaseUri());
		$this->expectException(BadRequest::class);
		$this->plugin->httpBulkDelete($request, new Response());
	}

	public static function invalidBodies(): array {
		$href = static fn (string $value): string => '<d:href>' . $value . '</d:href>';
		$wrap = static fn (string $value): string => '<d:delete xmlns:d="DAV:"><d:target>' . $value . '</d:target></d:delete>';
		return [
			'empty target' => [$wrap('')],
			'absolute path' => [$wrap($href('/one'))],
			'traversal' => [$wrap($href('../one'))],
			'encoded traversal' => [$wrap($href('%2e%2e/one'))],
			'double slash' => [$wrap($href('folder//one'))],
			'query' => [$wrap($href('one?x=1'))],
			'fragment' => [$wrap($href('one#x'))],
			'bad percent' => [$wrap($href('one%2'))],
			'encoded slash' => [$wrap($href('one%2Ftwo'))],
			'duplicate' => [$wrap($href('one') . $href('one'))],
			'wrong root' => ['<d:remove xmlns:d="DAV:"><d:target>' . $href('one') . '</d:target></d:remove>'],
			'unexpected element' => [$wrap('<d:prop/>' . $href('one'))],
			'nested href element' => [$wrap('<d:href><d:prop/></d:href>')],
			'doctype' => ['<!DOCTYPE d:delete [<!ENTITY x "one">]><d:delete xmlns:d="DAV:"><d:target><d:href>&x;</d:href></d:target></d:delete>'],
		];
	}

	public function testContentTypeIsRequired(): void {
		$request = new Request('BDELETE', '/nextcloud/remote.php/dav/files/alice/', [], '<d:delete xmlns:d="DAV:"/>');
		$request->setBaseUrl($this->server->getBaseUri());
		$this->expectException(UnsupportedMediaType::class);
		$this->plugin->httpBulkDelete($request, new Response());
	}

	public function testOversizedStreamIsRejectedWithoutDeletion(): void {
		$body = fopen('php://temp', 'w+');
		fwrite($body, str_repeat('x', BulkDeletePlugin::MAX_BODY_BYTES + 1));
		rewind($body);
		$request = new Request('BDELETE', '/nextcloud/remote.php/dav/files/alice/', ['Content-Type' => 'application/xml'], $body);
		$request->setBaseUrl($this->server->getBaseUri());
		$response = new Response();
		try {
			self::assertFalse($this->plugin->httpBulkDelete($request, $response));
			self::assertSame(413, $response->getStatus());
			self::assertSame([], $this->deleted);
		} finally {
			fclose($body);
		}
	}

	public function testOptionsAdvertisesBDeleteOnUserFilesTree(): void {
		$request = new Request('OPTIONS', '/nextcloud/remote.php/dav/files/alice/');
		$request->setBaseUrl($this->server->getBaseUri());
		$response = new Response();
		$this->server->invokeMethod($request, $response, false);
		self::assertStringContainsString('BDELETE', $response->getHeader('Allow') ?? '');
	}
}
