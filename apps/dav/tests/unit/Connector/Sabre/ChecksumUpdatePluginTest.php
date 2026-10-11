<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\ChecksumUpdatePlugin;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\Forbidden as DAVForbiddenException;
use OCA\DAV\Connector\Sabre\File;
use OCP\AppFramework\Http;
use OCP\Files\FileInfo;
use OCP\Files\ForbiddenException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class ChecksumUpdatePluginTest extends TestCase {
	private Tree&MockObject $tree;
	private RequestInterface&MockObject $request;
	private ResponseInterface&MockObject $response;
	private ChecksumUpdatePlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->tree = $this->createMock(Tree::class);
		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);

		$server = $this->createMock(Server::class);
		$server->tree = $this->tree;

		$this->plugin = new ChecksumUpdatePlugin();
		$this->plugin->initialize($server);

		$this->request->method('getPath')->willReturn('files/admin/foobar.txt');
	}

	private function createNode(bool $readable = true, bool $updateable = true): File&MockObject {
		$info = $this->createMock(FileInfo::class);
		$info->method('isReadable')->willReturn($readable);
		$info->method('isUpdateable')->willReturn($updateable);

		$node = $this->createMock(File::class);
		$node->method('getFileInfo')->willReturn($info);

		return $node;
	}

	public function testRecalculatesTheChecksum(): void {
		$node = $this->createNode();
		$node->expects($this->once())
			->method('hash')
			->with('md5')
			->willReturn('d41d8cd98f00b204e9800998ecf8427e');
		$node->expects($this->once())
			->method('setChecksum')
			->with('MD5:d41d8cd98f00b204e9800998ecf8427e');

		$this->tree->method('getNodeForPath')->willReturn($node);
		$this->request->method('getHeader')->with('X-Recalculate-Hash')->willReturn('md5');

		$this->response->expects($this->once())
			->method('addHeader')
			->with('OC-Checksum', 'MD5:d41d8cd98f00b204e9800998ecf8427e');
		$this->response->expects($this->once())
			->method('setStatus')
			->with(Http::STATUS_NO_CONTENT);

		$this->assertFalse($this->plugin->httpPatch($this->request, $this->response));
	}

	public static function dataMissingPermissions(): array {
		return [
			'not readable' => [false, true],
			'not updateable' => [true, false],
			'neither' => [false, false],
		];
	}

	#[DataProvider('dataMissingPermissions')]
	public function testDeniesWithoutPermissions(bool $readable, bool $updateable): void {
		$node = $this->createNode($readable, $updateable);
		$node->expects($this->never())->method('hash');
		$node->expects($this->never())->method('setChecksum');

		$this->tree->method('getNodeForPath')->willReturn($node);
		$this->request->method('getHeader')->with('X-Recalculate-Hash')->willReturn('md5');

		$this->expectException(DAVForbiddenException::class);
		$this->plugin->httpPatch($this->request, $this->response);
	}

	public function testMapsStorageForbiddenExceptionToDav(): void {
		$node = $this->createNode();
		$node->method('hash')->willThrowException(new ForbiddenException('Access denied', false));
		$node->expects($this->never())->method('setChecksum');

		$this->tree->method('getNodeForPath')->willReturn($node);
		$this->request->method('getHeader')->with('X-Recalculate-Hash')->willReturn('md5');

		$this->expectException(DAVForbiddenException::class);
		$this->plugin->httpPatch($this->request, $this->response);
	}

	public function testFailedHashDoesNotFallThrough(): void {
		$node = $this->createNode();
		$node->method('hash')->willReturn(false);
		$node->expects($this->never())->method('setChecksum');

		$this->tree->method('getNodeForPath')->willReturn($node);
		$this->request->method('getHeader')->with('X-Recalculate-Hash')->willReturn('md5');

		$this->expectException(Exception::class);
		$this->plugin->httpPatch($this->request, $this->response);
	}

	public function testRejectsUnknownHashAlgorithm(): void {
		$node = $this->createNode();
		$node->expects($this->never())->method('hash');

		$this->tree->method('getNodeForPath')->willReturn($node);
		$this->request->method('getHeader')->with('X-Recalculate-Hash')->willReturn('notahash');

		$this->expectException(BadRequest::class);
		$this->plugin->httpPatch($this->request, $this->response);
	}

	public function testIgnoresRequestWithoutHeader(): void {
		$node = $this->createNode();
		$node->expects($this->never())->method('hash');

		$this->tree->method('getNodeForPath')->willReturn($node);
		$this->request->method('getHeader')->with('X-Recalculate-Hash')->willReturn(null);

		$this->assertNull($this->plugin->httpPatch($this->request, $this->response));
	}

	public function testIgnoresNonFileNode(): void {
		$this->tree->method('getNodeForPath')->willReturn($this->createMock(Directory::class));
		$this->request->method('getHeader')->willReturn('md5');

		$this->assertNull($this->plugin->httpPatch($this->request, $this->response));
	}
}
