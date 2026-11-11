<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\SharingPlugin;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\CalDAV\Xml\Property\AllowedSharingModes;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\DAVACL\Plugin as AclPlugin;
use Test\TestCase;

class SharingPluginTest extends TestCase {
	private const ALLOWED_SHARING_MODES = '{http://calendarserver.org/ns/}allowed-sharing-modes';

	private IShareManager&MockObject $shareManager;
	private SharingPlugin $plugin;
	private Server $server;

	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(IShareManager::class);
		$this->plugin = new SharingPlugin($this->shareManager);
		$this->server = new Server(new SimpleCollection('root'));
		$this->server->addPlugin($this->plugin);
	}

	public function testFeatures(): void {
		$this->assertSame(['calendarserver-sharing'], $this->plugin->getFeatures());
		$this->assertSame('oc-calendar-sharing', $this->plugin->getPluginName());
	}

	public static function dataAllowedSharingModes(): array {
		return [
			'owner with link sharing' => [false, true, true, true, true],
			'owner without link sharing' => [false, true, false, true, false],
			'no write-acl privilege' => [false, false, true, false, false],
			'subscription' => [true, true, true, false, false],
		];
	}

	#[DataProvider(methodName: 'dataAllowedSharingModes')]
	public function testAllowedSharingModes(bool $isSubscription, bool $canWriteAcl, bool $allowLinks, bool $canBeShared, bool $canBePublished): void {
		$acl = $this->createMock(AclPlugin::class);
		$acl->method('getPluginName')->willReturn('acl');
		$acl->method('checkPrivileges')
			->with('calendars/user/personal', '{DAV:}write-acl', AclPlugin::R_PARENT, false)
			->willReturn($canWriteAcl);
		$this->server->addPlugin($acl);

		$this->shareManager->method('shareApiAllowLinks')->willReturn($allowLinks);

		$calendar = $this->createMock(Calendar::class);
		$calendar->method('isSubscription')->willReturn($isSubscription);

		$propFind = new PropFind('calendars/user/personal', [self::ALLOWED_SHARING_MODES]);
		$this->plugin->propFind($propFind, $calendar);

		$this->assertEquals(
			new AllowedSharingModes($canBeShared, $canBePublished),
			$propFind->get(self::ALLOWED_SHARING_MODES),
		);
	}

	public function testAllowedSharingModesWithoutAcl(): void {
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);

		$calendar = $this->createMock(Calendar::class);
		$calendar->method('isSubscription')->willReturn(false);

		$propFind = new PropFind('calendars/user/personal', [self::ALLOWED_SHARING_MODES]);
		$this->plugin->propFind($propFind, $calendar);

		$this->assertEquals(new AllowedSharingModes(true, true), $propFind->get(self::ALLOWED_SHARING_MODES));
	}

	public function testIgnoresOtherNodes(): void {
		$this->shareManager->expects($this->never())->method('shareApiAllowLinks');

		$propFind = new PropFind('files/user', [self::ALLOWED_SHARING_MODES]);
		$this->plugin->propFind($propFind, $this->createMock(INode::class));

		$this->assertNull($propFind->get(self::ALLOWED_SHARING_MODES));
	}
}
