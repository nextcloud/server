<?php
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
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class PublishingTest extends TestCase {

	/** @var PublishPlugin */
	private $plugin;
	/** @var Server */
	private $server;
	/** @var Calendar | \PHPUnit\Framework\MockObject\MockObject */
	private $book;
	/** @var IConfig | \PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IURLGenerator | \PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->getMockBuilder(IConfig::class)->
			disableOriginalConstructor()->
			getMock();
		$this->config->expects($this->any())->method('getSystemValue')
			->with($this->equalTo('secret'))
			->willReturn('mysecret');

		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->
			disableOriginalConstructor()->
			getMock();

		/** @var IRequest $request */
		$this->plugin = new PublishPlugin($this->config, $this->urlGenerator);

		$root = new SimpleCollection('calendars');
		$this->server = new Server($root);
		/** @var SimpleCollection $node */
		$this->book = $this->getMockBuilder(Calendar::class)->
			disableOriginalConstructor()->
			getMock();
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
