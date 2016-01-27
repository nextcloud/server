<?php

namespace OCA\Federation\Tests\lib;

use OCA\Federation\DbHandler;
use OCA\Federation\SyncFederationAddressBooks;

class SyncFederationAddressbooksTest extends \Test\TestCase {

	/** @var array */
	private $callBacks = [];

	function testSync() {
		/** @var DbHandler | \PHPUnit_Framework_MockObject_MockObject $dbHandler */
		$dbHandler = $this->getMockBuilder('OCA\Federation\DbHandler')->
			disableOriginalConstructor()->
			getMock();
		$dbHandler->method('getAllServer')->
			willReturn([
			[
				'url' => 'https://cloud.drop.box',
				'shared_secret' => 'iloveowncloud',
				'sync_token' => '0'
			]
		]);
		$dbHandler->expects($this->once())->method('setServerStatus')->
			with('https://cloud.drop.box', 1, '1');
		$syncService = $this->getMockBuilder('OCA\DAV\CardDAV\SyncService')
			->disableOriginalConstructor()
			->getMock();
		$syncService->expects($this->once())->method('syncRemoteAddressBook')
			->willReturn(1);

		$s = new SyncFederationAddressBooks($dbHandler, $syncService);
		$s->syncThemAll(function($url, $ex) {
			$this->callBacks[] = [$url, $ex];
		});
		$this->assertEquals(1, count($this->callBacks));
	}

	function testException() {
		/** @var DbHandler | \PHPUnit_Framework_MockObject_MockObject $dbHandler */
		$dbHandler = $this->getMockBuilder('OCA\Federation\DbHandler')->
		disableOriginalConstructor()->
		getMock();
		$dbHandler->method('getAllServer')->
		willReturn([
			[
				'url' => 'https://cloud.drop.box',
				'shared_secret' => 'iloveowncloud',
				'sync_token' => '0'
			]
		]);
		$syncService = $this->getMockBuilder('OCA\DAV\CardDAV\SyncService')
			->disableOriginalConstructor()
			->getMock();
		$syncService->expects($this->once())->method('syncRemoteAddressBook')
			->willThrowException(new \Exception('something did not work out'));

		$s = new SyncFederationAddressBooks($dbHandler, $syncService);
		$s->syncThemAll(function($url, $ex) {
			$this->callBacks[] = [$url, $ex];
		});
		$this->assertEquals(2, count($this->callBacks));
	}
}
