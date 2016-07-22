<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing\Tests;

/**
 * Class ActivityTest
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests
 */
class ActivityTest extends TestCase {

	/**
	 * @var \OCA\Files_Sharing\Activity
	 */
	private $activity;

	protected function setUp() {
		parent::setUp();
		$this->activity = new \OCA\Files_Sharing\Activity(
			$this->getMockBuilder('OCP\L10N\IFactory')
				->disableOriginalConstructor()
				->getMock(),
			$this->getMockBuilder('OCP\IURLGenerator')
				->disableOriginalConstructor()
				->getMock(),
			$this->getMockBuilder('OCP\Activity\IManager')
				->disableOriginalConstructor()
				->getMock()
		);
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
			array('email', array(\OCA\Files_Sharing\Activity::TYPE_SHARED, \OCA\Files_Sharing\Activity::TYPE_REMOTE_SHARE)),
			array('stream', array(\OCA\Files_Sharing\Activity::TYPE_SHARED, \OCA\Files_Sharing\Activity::TYPE_REMOTE_SHARE, \OCA\Files_Sharing\Activity::TYPE_PUBLIC_LINKS)),
		);
	}

}
