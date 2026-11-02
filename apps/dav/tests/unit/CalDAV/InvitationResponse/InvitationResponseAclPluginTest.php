<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\InvitationResponse;

use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseAclPlugin;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\DAVACL\IPrincipalCollection;
use Test\TestCase;

class InvitationResponseAclPluginTest extends TestCase {
	private IUserManager&MockObject $userManager;
	private IPrincipalCollection&MockObject $principalCollection;
	private InvitationResponseAclPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->principalCollection = $this->createMock(IPrincipalCollection::class);

		$tree = $this->createMock(Tree::class);
		$tree->method('getNodeForPath')
			->with('principals/users')
			->willReturn($this->principalCollection);
		$server = $this->createMock(Server::class);
		$server->tree = $tree;

		$this->plugin = new InvitationResponseAclPlugin($this->userManager);
		$this->plugin->principalCollectionSet = ['principals/users'];
		$reflectionProperty = new \ReflectionProperty($this->plugin, 'server');
		$reflectionProperty->setValue($this->plugin, $server);
	}

	public function testPrincipalFoundByCollection(): void {
		$this->principalCollection->expects(self::once())
			->method('findByUri')
			->with('mailto:user@example.com')
			->willReturn('principals/users/user');
		$this->userManager->expects(self::never())
			->method('getByEmail');

		$this->assertSame('principals/users/user', $this->plugin->getPrincipalByUri('mailto:user@example.com'));
	}

	public function testPrincipalFoundByEmail(): void {
		$this->principalCollection->method('findByUri')
			->willReturn(null);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');
		$this->userManager->expects(self::once())
			->method('getByEmail')
			->with('user@example.com')
			->willReturn([$user]);

		$this->assertSame('principals/users/user', $this->plugin->getPrincipalByUri('mailto:user@example.com'));
	}

	public function testPrincipalNotFoundForAmbiguousEmail(): void {
		$this->principalCollection->method('findByUri')
			->willReturn(null);
		$this->userManager->expects(self::once())
			->method('getByEmail')
			->with('user@example.com')
			->willReturn([$this->createMock(IUser::class), $this->createMock(IUser::class)]);

		$this->assertNull($this->plugin->getPrincipalByUri('mailto:user@example.com'));
	}

	public function testPrincipalNotFoundForUnknownEmail(): void {
		$this->principalCollection->method('findByUri')
			->willReturn(null);
		$this->userManager->expects(self::once())
			->method('getByEmail')
			->with('user@example.com')
			->willReturn([]);

		$this->assertNull($this->plugin->getPrincipalByUri('mailto:user@example.com'));
	}

	public function testPrincipalNotFoundForNonMailtoUri(): void {
		$this->principalCollection->method('findByUri')
			->willReturn(null);
		$this->userManager->expects(self::never())
			->method('getByEmail');

		$this->assertNull($this->plugin->getPrincipalByUri('principal:principals/users/user'));
	}
}
