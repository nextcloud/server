<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\DavAclPlugin;
use PHPUnit\Framework\Attributes\DataProvider;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\DAVACL\IACL;
use Sabre\DAVACL\IPrincipalCollection;
use Test\TestCase;

class DavAclPluginPublicPrincipalTest extends TestCase {
	private const PUBLIC = 'principals/system/public';
	private const ALICE = 'principals/users/alice';

	private function aclNode(string $name, array $acl): INode {
		$node = $this->createMock(IACL::class);
		$node->method('getName')->willReturn($name);
		$node->method('getOwner')->willReturn(self::ALICE);
		$node->method('getACL')->willReturn($acl);
		$node->method('getSupportedPrivilegeSet')->willReturn(null);
		return $node;
	}

	private function plugin(bool $restrict, ?string $principal): DavAclPlugin {
		$principals = $this->createMock(IPrincipalCollection::class);
		$principals->method('getName')->willReturn('principals');
		$principals->method('searchPrincipals')->willReturn(['alice']);
		$principals->method('getChild')->willReturn($this->aclNode('alice', [
			['privilege' => '{DAV:}read', 'principal' => '{DAV:}authenticated', 'protected' => true],
		]));

		$root = new SimpleCollection('', [
			$this->aclNode('authenticated', [
				['privilege' => '{DAV:}read', 'principal' => '{DAV:}authenticated', 'protected' => true],
			]),
			$this->aclNode('published', [
				['privilege' => '{DAV:}read', 'principal' => '{DAV:}authenticated', 'protected' => true],
				['privilege' => '{DAV:}read', 'principal' => self::PUBLIC, 'protected' => true],
			]),
			new SimpleCollection('files'),
			$principals,
		]);
		$server = new Server($root);

		$auth = $this->createMock(AuthPlugin::class);
		$auth->method('getPluginName')->willReturn('auth');
		$auth->method('getCurrentPrincipal')->willReturn($principal);
		$server->addPlugin($auth);

		$plugin = new DavAclPlugin($restrict);
		$plugin->principalCollectionSet = ['principals'];
		$server->addPlugin($plugin);
		return $plugin;
	}

	public function testRestrictedPublicPrincipalOnlyGetsPrivilegesGrantedByName(): void {
		$plugin = $this->plugin(true, self::PUBLIC);

		$this->assertSame(
			[['privilege' => '{DAV:}read', 'principal' => self::PUBLIC, 'protected' => true]],
			$plugin->getAcl('published'),
		);
		$this->assertTrue($plugin->checkPrivileges('published', '{DAV:}read', DavAclPlugin::R_PARENT, false));
		$this->assertFalse($plugin->checkPrivileges('authenticated', '{DAV:}read', DavAclPlugin::R_PARENT, false));
		$this->assertFalse($plugin->checkPrivileges('files', '{DAV:}read', DavAclPlugin::R_PARENT, false));
		$this->assertFalse($plugin->checkPrivileges('files', '{DAV:}bind', DavAclPlugin::R_PARENT, false));
	}

	public static function dataNotRestricted(): array {
		return [
			'restriction disabled, public principal' => [false, self::PUBLIC],
			'restriction enabled, user principal' => [true, self::ALICE],
		];
	}

	#[DataProvider('dataNotRestricted')]
	public function testAuthenticatedPrivilegesApplyWhenNotRestricted(bool $restrict, string $principal): void {
		$plugin = $this->plugin($restrict, $principal);

		$this->assertCount(2, $plugin->getAcl('published'));
		$this->assertTrue($plugin->checkPrivileges('authenticated', '{DAV:}read', DavAclPlugin::R_PARENT, false));
		$this->assertTrue($plugin->checkPrivileges('files', '{DAV:}bind', DavAclPlugin::R_PARENT, false));
	}

	public function testPrincipalSearchIsEmptyForRestrictedPublicPrincipal(): void {
		$this->assertSame([], $this->plugin(true, self::PUBLIC)->principalSearch(['{DAV:}displayname' => 'a'], ['{DAV:}displayname']));
		$this->assertNotEmpty($this->plugin(true, self::ALICE)->principalSearch(['{DAV:}displayname' => 'a'], ['{DAV:}displayname']));
	}

	public function testRefusedNodeWithoutAclIsReportedAsNotFound(): void {
		$this->expectException(NotFound::class);
		$this->plugin(true, self::PUBLIC)->checkPrivileges('files', '{DAV:}bind');
	}
}
