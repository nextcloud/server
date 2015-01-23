<?php

/**
 * ownCloud
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Files_sharing\Tests;
use OCA\Files_sharing\Tests\TestCase;


class Activity extends \OCA\Files_Sharing\Tests\TestCase{

	/**
	 * @var \OCA\Files_Sharing\Activity
	 */
	private $activity;

	protected function setUp() {
		parent::setUp();
		$this->activity = new \OCA\Files_Sharing\Activity();
	}

	/**
	 * @dataProvider dataTestGetDefaultType
	 */
	public function testGetDefaultTypes($method, $expectedResult) {
		$result = $this->activity->getDefaultTypes($method);

		if (is_array($expectedResult)) {
			$this->assertSame(count($expectedResult), count($result));
			foreach ($expectedResult as $key => $expected) {
				$this->assertSame($expected, $result[$key]);
			}
		} else {
			$this->assertSame($expectedResult, $result);
		}

	}

	public function dataTestGetDefaultType() {
		return array(
			array('email', array(\OCA\Files_Sharing\Activity::TYPE_REMOTE_SHARE)),
			array('stream', array(\OCA\Files_Sharing\Activity::TYPE_REMOTE_SHARE, \OCA\Files_Sharing\Activity::TYPE_PUBLIC_LINKS)),
			array('foo', false)
		);
	}

}
