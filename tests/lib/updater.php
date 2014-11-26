<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

class UpdaterTest extends \Test\TestCase {

	public function versionCompatibilityTestData() {
		return array(
			array('1.0.0.0', '2.2.0', true),
			array('1.1.1.1', '2.0.0', true),
			array('5.0.3', '4.0.3', false),
			array('12.0.3', '13.4.5', true),
			array('1', '2', true),
			array('2', '2', true),
			array('6.0.5', '6.0.6', true),
			array('5.0.6', '7.0.4', false)
		);
	}

	/**
	 * @dataProvider versionCompatibilityTestData
	 */
	public function testIsUpgradePossible($oldVersion, $newVersion, $result) {
		$updater = new Updater(\OC::$server->getHTTPHelper());
		$this->assertSame($result, $updater->isUpgradePossible($oldVersion, $newVersion));
	}
	
	
	public function testCheck(){
		$httpHelper = $this->getMockBuilder('\OC\HTTPHelper')
				->getMock();
		
		$httpHelper->method('getUrlContent')
				->willReturn(
					'<?xml version="1.0"?><owncloud><version></version><versionstring></versionstring><url></url><web></web></owncloud>'
				)
		;
		
		$updater = new Updater($httpHelper);
		// Invalidate cache
		\OC_Appconfig::setValue('core', 'lastupdatedat', 0);
		$result = $updater->check();
		$this->assertContains('version', $result);
		$this->assertContains('versionstring', $result);
		$this->assertContains('url', $result);
		$this->assertContains('web', $result);
		$this->assertEmpty($result['version']);
		$this->assertEmpty($result['versionstring']);
		$this->assertEmpty($result['url']);
		$this->assertEmpty($result['web']);
		
		// Invalidate cache
		\OC_Appconfig::setValue('core', 'lastupdatedat', 0);
		$httpHelper->method('getUrlContent')
				->willReturn('')
		;
		
		$emptyResult = $updater->check();
		$this->assertEmpty($emptyResult);

		// Invalidate cache
		\OC_Appconfig::setValue('core', 'lastupdatedat', 0);
		$httpHelper->method('getUrlContent')
				->willReturn('<?xml version="1.0"?>
<owncloud>
  <version>7.0.3.4</version>
  <versionstring>ownCloud 7.0.3</versionstring>
  <url>http://download.owncloud.org/community/owncloud-7.0.3.zip</url>
  <web>http://owncloud.org/</web>
</owncloud>')
		;
		
		$newResult = $updater->check();
		$this->assertContains('version', $newResult);
		$this->assertContains('versionstring', $newResult);
		$this->assertContains('url', $newResult);
		$this->assertContains('web', $newResult);
		$this->assertEqual('7.0.3.4', $newResult['version']);
		$this->assertEqual('ownCloud 7.0.3', $newResult['versionstring']);
		$this->assertEqual('http://download.owncloud.org/community/owncloud-7.0.3.zip', $newResult['url']);
		$this->assertEqual('http://owncloud.org/', $newResult['web']);
	}

}