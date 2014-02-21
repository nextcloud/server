<?php
/**
 * Copyright (c) 2014 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Urlgenerator extends PHPUnit_Framework_TestCase {


	/**
	 * @small
	 * @brief test absolute URL construction
	 * @dataProvider provideURLs
	 */
	function testGetAbsoluteURL($url, $expectedResult) {

		$urlGenerator = new \OC\URLGenerator(null);
		$result = $urlGenerator->getAbsoluteURL($url);

		$this->assertEquals($expectedResult, $result);
	}

	public function provideURLs() {
		return array(
			array("index.php", "http://localhost/index.php"),
			array("/index.php", "http://localhost/index.php"),
			array("/apps/index.php", "http://localhost/apps/index.php"),
			array("apps/index.php", "http://localhost/apps/index.php"),
			);
	}
}

