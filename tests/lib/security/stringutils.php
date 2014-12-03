<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use \OC\Security\StringUtils;

class StringUtilsTest extends \Test\TestCase {

	public function dataProvider()
	{
		return array(
			array('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt.', 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt.'),
			array('', ''),
			array('我看这本书。 我看這本書', '我看这本书。 我看這本書'),
			array('GpKY9fSnWNJbES99zVGvA', 'GpKY9fSnWNJbES99zVGvA')
		);
	}

	/**
	 * @dataProvider dataProvider
	 */
	function testWrongEquals($string) {
		$this->assertFalse(StringUtils::equals($string, 'A Completely Wrong String'));
		$this->assertFalse(StringUtils::equals($string, null));
	}

	/**
	 * @dataProvider dataProvider
	 */
	function testTrueEquals($string, $expected) {
		$this->assertTrue(StringUtils::equals($string, $expected));
	}

}
