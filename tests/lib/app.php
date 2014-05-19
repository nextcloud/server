<?php
/**
 * Copyright (c) 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_App extends PHPUnit_Framework_TestCase {

	
	public function testIsAppVersionCompatibleSingleOCNumber(){
		$oc = array(4);
		$app = '4.0';

		$this->assertTrue(OC_App::isAppVersionCompatible($oc, $app));
	}

	
	public function testIsAppVersionCompatibleMultipleOCNumber(){
		$oc = array(4, 3, 1);
		$app = '4.3';

		$this->assertTrue(OC_App::isAppVersionCompatible($oc, $app));
	}


	public function testIsAppVersionCompatibleSingleNumber(){
		$oc = array(4);
		$app = '4';

		$this->assertTrue(OC_App::isAppVersionCompatible($oc, $app));
	}


	public function testIsAppVersionCompatibleSingleAppNumber(){
		$oc = array(4, 3);
		$app = '4';

		$this->assertTrue(OC_App::isAppVersionCompatible($oc, $app));
	}


	public function testIsAppVersionCompatibleComplex(){
		$oc = array(5, 0, 0);
		$app = '4.5.1';

		$this->assertTrue(OC_App::isAppVersionCompatible($oc, $app));
	}

	
	public function testIsAppVersionCompatibleShouldFail(){
		$oc = array(4, 3, 1);
		$app = '4.3.2';

		$this->assertFalse(OC_App::isAppVersionCompatible($oc, $app));
	}

	public function testIsAppVersionCompatibleShouldFailTwoVersionNumbers(){
		$oc = array(4, 3, 1);
		$app = '4.4';

		$this->assertFalse(OC_App::isAppVersionCompatible($oc, $app));
	}


	public function testIsAppVersionCompatibleShouldWorkForPreAlpha(){
		$oc = array(5, 0, 3);
		$app = '4.93';

		$this->assertTrue(OC_App::isAppVersionCompatible($oc, $app));
	}


	public function testIsAppVersionCompatibleShouldFailOneVersionNumbers(){
		$oc = array(4, 3, 1);
		$app = '5';

		$this->assertFalse(OC_App::isAppVersionCompatible($oc, $app));
	}

	/**
	 * Tests that the app order is correct
	 */
	public function testGetEnabledAppsIsSorted() {
		$apps = \OC_App::getEnabledApps(true);
		// copy array
		$sortedApps = $apps;
		sort($sortedApps);
		// 'files' is always on top
		unset($sortedApps[array_search('files', $sortedApps)]);
		array_unshift($sortedApps, 'files');
		$this->assertEquals($sortedApps, $apps);
	}
}
