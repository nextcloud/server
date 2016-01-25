<?php

namespace OCA\DAV\Tests\Unit;

use OCA\DAV\Server;
use OCP\IRequest;

/**
 * Class ServerTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit
 */
class ServerTest extends \Test\TestCase {

	public function test() {
		/** @var IRequest $r */
		$r = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()->getMock();
		$s = new Server($r, '/');
		$this->assertNotNull($s->server);
	}
}