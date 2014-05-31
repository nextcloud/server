<?php
/**
 * Copyright (c) 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_App extends PHPUnit_Framework_TestCase {

	function appVersionsProvider() {
		return array(
			// exact match
			array(
				'6.0.0.0',
				array(
					'requiremin' => '6.0',
					'requiremax' => '6.0',
				),
				true
			),
			// in-between match
			array(
				'6.0.0.0',
				array(
					'requiremin' => '5.0',
					'requiremax' => '7.0',
				),
				true
			),
			// app too old
			array(
				'6.0.0.0',
				array(
					'requiremin' => '5.0',
					'requiremax' => '5.0',
				),
				false
			),
			// app too new
			array(
				'5.0.0.0',
				array(
					'requiremin' => '6.0',
					'requiremax' => '6.0',
				),
				false
			),
			// only min specified
			array(
				'6.0.0.0',
				array(
					'requiremin' => '6.0',
				),
				true
			),
			// only min specified fail
			array(
				'5.0.0.0',
				array(
					'requiremin' => '6.0',
				),
				false
			),
			// only min specified legacy
			array(
				'6.0.0.0',
				array(
					'require' => '6.0',
				),
				true
			),
			// only min specified legacy fail
			array(
				'4.0.0.0',
				array(
					'require' => '6.0',
				),
				false
			),
			// only max specified
			array(
				'5.0.0.0',
				array(
					'requiremax' => '6.0',
				),
				true
			),
			// only max specified fail
			array(
				'7.0.0.0',
				array(
					'requiremax' => '6.0',
				),
				false
			),
			// variations of versions
			// single OC number
			array(
				'4',
				array(
					'require' => '4.0',
				),
				true
			),
			// multiple OC number 
			array(
				'4.3.1',
				array(
					'require' => '4.3',
				),
				true
			),
			// single app number 
			array(
				'4',
				array(
					'require' => '4',
				),
				true
			),
			// single app number fail
			array(
				'4.3',
				array(
					'require' => '5',
				),
				false
			),
			// complex
			array(
				'5.0.0',
				array(
					'require' => '4.5.1',
				),
				true
			),
			// complex fail
			array(
				'4.3.1',
				array(
					'require' => '4.3.2',
				),
				false
			),
			// two numbers
			array(
				'4.3.1',
				array(
					'require' => '4.4',
				),
				false
			),
			// one number fail
			array(
				'4.3.1',
				array(
					'require' => '5',
				),
				false
			),
			// pre-alpha app
			array(
				'5.0.3',
				array(
					'require' => '4.93',
				),
				true
			),
			// pre-alpha OC
			array(
				'6.90.0.2',
				array(
					'require' => '6.90',
				),
				true
			),
			// pre-alpha OC max
			array(
				'6.90.0.2',
				array(
					'requiremax' => '7',
				),
				true
			),
			// expect same major number match
			array(
				'5.0.3',
				array(
					'require' => '5',
				),
				true
			),
			// expect same major number match
			array(
				'5.0.3',
				array(
					'requiremax' => '5',
				),
				true
			),
		);
	}

	/**
	 * @dataProvider appVersionsProvider
	 */
	public function testIsAppCompatible($ocVersion, $appInfo, $expectedResult) {
		$this->assertEquals($expectedResult, OC_App::isAppCompatible($ocVersion, $appInfo));
	}

	/**
	 * Test that the isAppCompatible method also supports passing an array
	 * as $ocVersion
	 */
	public function testIsAppCompatibleWithArray() {
		$ocVersion = array(6);
		$appInfo = array(
			'requiremin' => '6',
			'requiremax' => '6',
		);
		$this->assertTrue(OC_App::isAppCompatible($ocVersion, $appInfo));
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
