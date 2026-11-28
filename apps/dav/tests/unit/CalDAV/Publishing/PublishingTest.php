<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Publishing;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\Publishing\PublishPlugin;
use OCP\IURLGenerator;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class PublishingTest extends TestCase {
	private PublishPlugin $plugin;
	private Server $server;
	private Calendar&MockObject $book;
	private IURLGenerator&MockObject $urlGenerator;
	private IShareManager&MockObject $shareManager;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->shareManager = $this->createMock(IShareManager::class);

		$this->plugin = new PublishPlugin($this->urlGenerator, $this->shareManager);

		$root = new SimpleCollection('calendars');
		$this->server = new Server($root);
		/** @var SimpleCollection $node */
		$this->book = $this->getMockBuilder(Calendar::class)
			->disableOriginalConstructor()
			->getMock();
		$this->book->method('getName')->willReturn('cal1');
		$root->addChild($this->book);
		$this->plugin->initialize($this->server);
	}

	public function testPublishing(): void {
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);
		$this->book->expects($this->once())->method('setPublishStatus')->with(true);

		// setup request
		$request = new Request('POST', 'cal1');
		$request->addHeader('Content-Type', 'application/xml');
		$request->setBody('<o:publish-calendar xmlns:o="http://calendarserver.org/ns/"/>');
		$response = new Response();
		$this->plugin->httpPost($request, $response);
	}

	public function testPublishingIsForbiddenWithoutLinkSharing(): void {
		$this->shareManager->method('shareApiAllowLinks')->willReturn(false);
		$this->book->expects($this->never())->method('setPublishStatus');

		$request = new Request('POST', 'cal1');
		$request->addHeader('Content-Type', 'application/xml');
		$request->setBody('<o:publish-calendar xmlns:o="http://calendarserver.org/ns/"/>');

		$this->expectException(Forbidden::class);
		$this->plugin->httpPost($request, new Response());
	}

	public function testUnPublishing(): void {
		$this->book->expects($this->once())->method('setPublishStatus')->with(false);

		// setup request
		$request = new Request('POST', 'cal1');
		$request->addHeader('Content-Type', 'application/xml');
		$request->setBody('<o:unpublish-calendar xmlns:o="http://calendarserver.org/ns/"/>');
		$response = new Response();
		$this->plugin->httpPost($request, $response);
	}

	public function testUnPublishingWithoutLinkSharing(): void {
		$this->shareManager->expects($this->never())->method('shareApiAllowLinks');
		$this->book->expects($this->once())->method('setPublishStatus')->with(false);

		$request = new Request('POST', 'cal1');
		$request->addHeader('Content-Type', 'application/xml');
		$request->setBody('<o:unpublish-calendar xmlns:o="http://calendarserver.org/ns/"/>');
		$this->plugin->httpPost($request, new Response());
	}
}
