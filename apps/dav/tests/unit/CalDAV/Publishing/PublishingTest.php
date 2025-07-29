<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Publishing;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\Publishing\PublishPlugin;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class PublishingTest extends TestCase {
	private PublishPlugin $plugin;
	private Server $server;
	private Calendar&MockObject $book;
	private IConfig&MockObject $config;
	private IURLGenerator&MockObject $urlGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->config->expects($this->any())->method('getSystemValue')
			->with($this->equalTo('secret'))
			->willReturn('mysecret');

		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		/** @var IRequest $request */
		$this->plugin = new PublishPlugin($this->config, $this->urlGenerator);

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
		$this->book->expects($this->once())->method('setPublishStatus')->with(true);

		// setup request
		$request = new Request('POST', 'cal1');
		$request->addHeader('Content-Type', 'application/xml');
		$request->setBody('<o:publish-calendar xmlns:o="http://calendarserver.org/ns/"/>');
		$response = new Response();
		$this->plugin->httpPost($request, $response);
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
}
