<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit;

use OCA\DAV\RootCollection;
use OCP\IConfig;
use OCP\Server;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAVACL\AbstractPrincipalCollection;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class RootCollectionTest extends TestCase {

	private function createRootCollection(bool $debug): RootCollection {
		$realConfig = Server::get(IConfig::class);
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->willReturnCallback(static fn (string $key, mixed $default = '') => $key === 'debug'
				? $debug
				: $realConfig->getSystemValue($key, $default));
		$this->overwriteService(IConfig::class, $config);

		return new RootCollection();
	}

	private function getPrincipalCollection(RootCollection $root, string $name): AbstractPrincipalCollection {
		$collection = $root->getChild('principals')->getChild($name);
		$this->assertInstanceOf(AbstractPrincipalCollection::class, $collection);
		return $collection;
	}

	public static function dataPrincipalCollectionsWithDisabledListing(): array {
		return [
			['users'],
			['groups'],
			['system'],
			['remote-users'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataPrincipalCollectionsWithDisabledListing')]
	public function testPrincipalListingDisabled(string $name): void {
		$collection = $this->getPrincipalCollection($this->createRootCollection(false), $name);

		$this->expectException(MethodNotAllowed::class);
		$collection->getChildren();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataPrincipalCollectionsWithDisabledListing')]
	public function testPrincipalListingAllowedInDebugMode(string $name): void {
		$collection = $this->getPrincipalCollection($this->createRootCollection(true), $name);

		$this->assertFalse($collection->disableListing);
	}
}
