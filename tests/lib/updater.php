<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

class UpdaterTest extends \Test\TestCase {

	public function testVersionCompatbility() {
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
	 * @dataProvider testVersionCompatbility
	 */
	function testIsUpgradePossible($oldVersion, $newVersion, $result) {
		$updater = new Updater();
		$this->assertSame($result, $updater->isUpgradePossible($oldVersion, $newVersion));
	}

}