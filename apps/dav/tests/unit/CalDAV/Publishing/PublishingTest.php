<?php

namespace OCA\DAV\Tests\unit\CalDAV\Publishing;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\Publishing\PublishPlugin;
use OCA\DAV\Connector\Sabre\Auth;
use OCP\IRequest;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class PluginTest extends TestCase {

	/** @var Plugin */
	private $plugin;
	/** @var Server */
	private $server;
	/** @var Calendar | \PHPUnit_Framework_MockObject_MockObject */
	private $book;

	public function setUp() {
		parent::setUp();

		/** @var Auth | \PHPUnit_Framework_MockObject_MockObject $authBackend */
		$authBackend = $this->getMockBuilder('OCA\DAV\DAV\PublicAuth')->disableOriginalConstructor()->getMock();
		$authBackend->method('isDavAuthenticated')->willReturn(true);

		/** @var IRequest $request */
		$request = $this->getMockBuilder('OCP\IRequest')->disableOriginalConstructor()->getMock();
		$this->plugin = new PublishPlugin($authBackend, $request);

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

		$this->book->expects($this->once())->method('setPublishStatus')->with(true);

		// setup request
		$request = new Request();
		$request->addHeader('Content-Type', 'application/xml');
		$request->setUrl('cal1');
		$request->setBody('<o:unpublish-calendar xmlns:o="http://calendarserver.org/ns/"/>');
		$response = new Response();
		$this->plugin->httpPost($request, $response);
	}
}
