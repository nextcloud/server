<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\ShareByMail\Tests;


use OCA\ShareByMail\Activity;

class ActivityTest extends \Test\TestCase   {

	/**
	 * @var \OCA\ShareByMail\Activity
	 */
	private $activity;

	protected function setUp() {
		parent::setUp();
		$this->activity = new Activity(
			$this->getMockBuilder('OCP\L10N\IFactory')
				->disableOriginalConstructor()
				->getMock(),
			$this->getMockBuilder('OCP\Activity\IManager')
				->disableOriginalConstructor()
				->getMock()
		);
	}

	/**
	 * @dataProvider dataTestGetSpecialParameterList
	 *
	 */
	public function testGetSpecialParameterList($app, $text, $expected) {
		$result = $this->activity->getSpecialParameterList($app, $text);
		$this->assertSame($expected, $result);
	}

	public function dataTestGetSpecialParameterList() {
		return [
			['sharebymail', Activity::SUBJECT_SHARED_EMAIL_SELF, [0 => 'file', 1 => 'email']],
			['sharebymail', Activity::SUBJECT_SHARED_EMAIL_BY, [0 => 'file', 1 => 'email', 2 => 'user']],
			['sharebymail', 'unknown', false],
			['randomApp', Activity::SUBJECT_SHARED_EMAIL_SELF, false],
			['randomApp', Activity::SUBJECT_SHARED_EMAIL_BY, false],

		];
	}

}

