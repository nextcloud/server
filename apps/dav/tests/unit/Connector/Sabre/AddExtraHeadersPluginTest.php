<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace unit\Connector\Sabre;

use LogicException;
use OCA\DAV\Connector\Sabre\AddExtraHeadersPlugin;
use OCA\DAV\Connector\Sabre\Node;
use OCA\DAV\Connector\Sabre\Server;
use OCP\IUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class AddExtraHeadersPluginTest extends TestCase {

	private AddExtraHeadersPlugin $plugin;
	private Server&MockObject $server;
	private LoggerInterface&MockObject $logger;
	private RequestInterface&MockObject $request;
	private ResponseInterface&MockObject $response;
	private Tree&MockObject $tree;

	public static function afterPutData(): array {
		return [
			'owner and permissions present' => [
				'user', true, 'PERMISSIONS', true, 2
			],
			'owner id only' => [
				'user', true, null, false, 1
			],
			'permissions only' => [
				null, false, 'PERMISSIONS', true, 1
			],
			'no owner id and permissions present' => [
				null, false, null, false, 0
			]
		];
	}

	public function testAfterPutNotFoundException(): void {
		$afterPut = null;
		$this->server->expects($this->once())
			->method('on')
			->willReturnCallback(
				function ($method, $callback) use (&$afterPut) {
					$this->assertSame('afterMethod:PUT', $method);
					$afterPut = $callback;
				});

		$this->plugin->initialize($this->server);
		$node = $this->createMock(Node::class);
		$this->tree->expects($this->once())->method('getNodeForPath')
			->willThrowException(new NotFound());

		$this->logger->expects($this->once())->method('error');

		$afterPut($this->request, $this->response);
	}

	#[DataProvider('afterPutData')]
	public function testAfterPut(?string $ownerId, bool $expectOwnerIdHeader,
		?string $permissions, bool $expectPermissionsHeader,
		int $expectedInvocations): void {
		$afterPut = null;
		$this->server->expects($this->once())
			->method('on')
			->willReturnCallback(
				function ($method, $callback) use (&$afterPut) {
					$this->assertSame('afterMethod:PUT', $method);
					$afterPut = $callback;
				});

		$this->plugin->initialize($this->server);
		$node = $this->createMock(Node::class);
		$this->tree->expects($this->once())->method('getNodeForPath')
			->willReturn($node);

		$user = $this->createMock(IUser::class);
		$node->expects($this->once())->method('getOwner')->willReturn($user);
		$user->expects($this->once())->method('getUID')->willReturn($ownerId);
		$node->expects($this->once())->method('getDavPermissions')->willReturn($permissions);

		$matcher = $this->exactly($expectedInvocations);
		$this->response->expects($matcher)->method('setHeader')
			->willReturnCallback(function ($name, $value) use (
				$expectedInvocations,
				$expectPermissionsHeader,
				$expectOwnerIdHeader,
				$matcher,
				$ownerId, $permissions) {
				$invocationNumber = $matcher->numberOfInvocations();
				if ($invocationNumber === 0) {
					throw new LogicException('No invocations were expected');
				}

				if (($expectOwnerIdHeader && $expectedInvocations === 1)
					|| ($expectedInvocations
						=== 2 && $invocationNumber === 1)) {
					$this->assertEquals('X-NC-OwnerId', $name);
					$this->assertEquals($ownerId, $value);
				}

				if (($expectPermissionsHeader && $expectedInvocations === 1)
					|| ($expectedInvocations
						=== 2 && $invocationNumber === 2)) {
					$this->assertEquals('X-NC-Permissions', $name);
					$this->assertEquals($permissions, $value);
				}
			});

		$afterPut($this->request, $this->response);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);
		$this->server->tree = $this->tree;
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->plugin = new AddExtraHeadersPlugin($this->logger);
		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
	}
}
