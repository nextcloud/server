<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use \OC\Security\StringUtils;

class StringUtilsTest extends \PHPUnit_Framework_TestCase {

	function testEquals() {
		$this->assertTrue(StringUtils::equals('GpKY9fSnWRaeFNJbES99zVGvA', 'GpKY9fSnWRaeFNJbES99zVGvA'));
		$this->assertFalse(StringUtils::equals('GpKY9fSnWNJbES99zVGvA', 'GpKY9fSnWRaeFNJbES99zVGvA'));
		$this->assertFalse(StringUtils::equals('', 'GpKY9fSnWRaeFNJbES99zVGvA'));
		$this->assertFalse(StringUtils::equals('GpKY9fSnWRaeFNJbES99zVGvA', ''));
		$this->assertTrue(StringUtils::equals('', ''));
	}

}
