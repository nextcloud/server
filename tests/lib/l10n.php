<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_L10n extends PHPUnit_Framework_TestCase {

	/**
	 * Issue #4360: Do not call strtotime() on numeric strings.
	 */
	public function testNumericStringToDateTime() {
		$l = new OC_L10N('test');
		$this->assertSame('February 13, 2009 23:31', $l->l('datetime', '1234567890'));
	}

	public function testNumericToDateTime() {
		$l = new OC_L10N('test');
		$this->assertSame('February 13, 2009 23:31', $l->l('datetime', 1234567890));
	}
}
