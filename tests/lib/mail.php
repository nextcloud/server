<?php
/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Mail extends \Test\TestCase {

	protected function setUp()
	{
		parent::setUp();

		if (!function_exists('idn_to_ascii')) {
			$this->markTestSkipped(
				'The intl extension is not available.'
			);
		}
	}

	/**
	 * @dataProvider buildAsciiEmailProvider
	 * @param $expected
	 * @param $address
	 */
	public function testBuildAsciiEmail($expected, $address) {
		$actual = \OC_Mail::buildAsciiEmail($address);
		$this->assertEquals($expected, $actual);
	}

	function buildAsciiEmailProvider() {
		return array(
			array('info@example.com', 'info@example.com'),
			array('info@xn--cjr6vy5ejyai80u.com', 'info@國際化域名.com'),
			array('info@xn--mller-kva.de', 'info@müller.de'),
			array('info@xn--mller-kva.xn--mller-kva.de', 'info@müller.müller.de'),
		);
	}

}
