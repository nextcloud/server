<?php

namespace OCA\DAV\Tests\unit\CalDAV\Publishing;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\Publishing\PublishPlugin;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IConfig;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class PluginTest extends TestCase {

	/** @var PublishPlugin */
	private $plugin;
	/** @var Server */
	private $server;
	/** @var Calendar | \PHPUnit_Framework_MockObject_MockObject */
	private $book;
	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;

	public function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')->
			disableOriginalConstructor()->
			getMock();
		$this->config->expects($this->any())->method('getSystemValue')
			->with($this->equalTo('secret'))
			->willReturn('mysecret');

		$this->urlGenerator = $this->getMockBuilder('OCP\IURLGenerator')->
			disableOriginalConstructor()->
			getMock();

		/** @var IRequest $request */
		$this->plugin = new PublishPlugin($this->config, $this->urlGenerator);

		$root = new SimpleCollection('calendars');
		$this->server = new Server($root);
		/** @var SimpleCollection $node */
		$this->book = $this->getMockBuilder('OCA\DAV\CalDAV\Calendar')->
			disableOriginalConstructor()->
			getMock();
		$this->book->method('getName')->willReturn('cal1');
		$root->addChild($this->book);
		$this->plugin->initialize($this->server);
	}

	public function testPublishing() {

		$this->book->expects($this->once())->method('setPublishStatus')->with(true);

		// setup request
		$request = new Request();
		$request->addHeader('Content-Type', 'application/xml');
		$request->setUrl('cal1');
		$request->setBody('<o:publish-calendar xmlns:o="http://calendarserver.org/ns/"/>');
		$response = new Response();
		$this->plugin->httpPost($request, $response);
	}

	public function testUnPublishing() {

		$this->book->expects($this->once())->method('setPublishStatus')->with(false);

		// setup request
		$request = new Request();
		$request->addHeader('Content-Type', 'application/xml');
		$request->setUrl('cal1');
		$request->setBody('<o:unpublish-calendar xmlns:o="http://calendarserver.org/ns/"/>');
		$response = new Response();
		$this->plugin->httpPost($request, $response);
	}
}
