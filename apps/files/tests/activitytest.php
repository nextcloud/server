<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Files\Tests;

use OCA\Files\Activity;
use Test\TestCase;

class ActivityTest extends TestCase {

	/** @var \OC\ActivityManager */
	private $activityManager;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $request;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $session;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $config;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $activityHelper;

	/** @var \OCA\Files\Activity */
	protected $activityExtension;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->session = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->activityHelper = $this->getMockBuilder('OCA\Files\ActivityHelper')
			->disableOriginalConstructor()
			->getMock();

		$this->activityManager = new \OC\ActivityManager(
			$this->request,
			$this->session,
			$this->config
		);

		$this->activityExtension = $activityExtension = new Activity(
			new \OC\L10N\Factory(),
			$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
			$this->activityManager,
			$this->activityHelper,
			$this->config
		);

		$this->activityManager->registerExtension(function() use ($activityExtension) {
			return $activityExtension;
		});
	}

	public function testNotificationTypes() {
		$result = $this->activityExtension->getNotificationTypes('en');
		$this->assertTrue(is_array($result), 'Asserting getNotificationTypes() returns an array');
		$this->assertCount(5, $result);
		$this->assertArrayHasKey(Activity::TYPE_SHARE_CREATED, $result);
		$this->assertArrayHasKey(Activity::TYPE_SHARE_CHANGED, $result);
		$this->assertArrayHasKey(Activity::TYPE_FAVORITES, $result);
		$this->assertArrayHasKey(Activity::TYPE_SHARE_DELETED, $result);
		$this->assertArrayHasKey(Activity::TYPE_SHARE_RESTORED, $result);
	}

	public function testDefaultTypes() {
		$result = $this->activityExtension->getDefaultTypes('stream');
		$this->assertTrue(is_array($result), 'Asserting getDefaultTypes(stream) returns an array');
		$this->assertCount(4, $result);
		$result = array_flip($result);
		$this->assertArrayHasKey(Activity::TYPE_SHARE_CREATED, $result);
		$this->assertArrayHasKey(Activity::TYPE_SHARE_CHANGED, $result);
		$this->assertArrayNotHasKey(Activity::TYPE_FAVORITES, $result);
		$this->assertArrayHasKey(Activity::TYPE_SHARE_DELETED, $result);
		$this->assertArrayHasKey(Activity::TYPE_SHARE_RESTORED, $result);

		$result = $this->activityExtension->getDefaultTypes('email');
		$this->assertFalse($result, 'Asserting getDefaultTypes(email) returns false');
	}

	public function testTranslate() {
		$this->assertFalse(
			$this->activityExtension->translate('files_sharing', '', [], false, false, 'en'),
			'Asserting that no translations are set for files_sharing'
		);
	}

	public function testGetSpecialParameterList() {
		$this->assertFalse(
			$this->activityExtension->getSpecialParameterList('files_sharing', ''),
			'Asserting that no special parameters are set for files_sharing'
		);
	}

	public function typeIconData() {
		return [
			[Activity::TYPE_SHARE_CHANGED, 'icon-change'],
			[Activity::TYPE_SHARE_CREATED, 'icon-add-color'],
			[Activity::TYPE_SHARE_DELETED, 'icon-delete-color'],
			[Activity::TYPE_SHARE_RESTORED, false],
			[Activity::TYPE_FAVORITES, false],
			['unknown type', false],
		];
	}

	/**
	 * @dataProvider typeIconData
	 *
	 * @param string $type
	 * @param mixed $expected
	 */
	public function testTypeIcon($type, $expected) {
		$this->assertSame($expected, $this->activityExtension->getTypeIcon($type));
	}

	public function testGroupParameter() {
		$this->assertFalse(
			$this->activityExtension->getGroupParameter(['app' => 'files_sharing']),
			'Asserting that no group parameters are set for files_sharing'
		);
	}

	public function testNavigation() {
		$result = $this->activityExtension->getNavigation();
		$this->assertCount(1, $result['top']);
		$this->assertArrayHasKey(Activity::FILTER_FAVORITES, $result['top']);

		$this->assertCount(1, $result['apps']);
		$this->assertArrayHasKey(Activity::FILTER_FILES, $result['apps']);
	}

	public function testIsFilterValid() {
		$this->assertTrue($this->activityExtension->isFilterValid(Activity::FILTER_FAVORITES));
		$this->assertTrue($this->activityExtension->isFilterValid(Activity::FILTER_FILES));
		$this->assertFalse($this->activityExtension->isFilterValid('unknown filter'));
	}

	public function filterNotificationTypesData() {
		return [
			[
				Activity::FILTER_FILES,
				[
					'NT0',
					Activity::TYPE_SHARE_CREATED,
					Activity::TYPE_SHARE_CHANGED,
					Activity::TYPE_SHARE_DELETED,
					Activity::TYPE_SHARE_RESTORED,
					Activity::TYPE_FAVORITES,
				], [
					Activity::TYPE_SHARE_CREATED,
					Activity::TYPE_SHARE_CHANGED,
					Activity::TYPE_SHARE_DELETED,
					Activity::TYPE_SHARE_RESTORED,
				],
			],
			[
				Activity::FILTER_FILES,
				[
					'NT0',
					Activity::TYPE_SHARE_CREATED,
					Activity::TYPE_FAVORITES,
				],
				[
					Activity::TYPE_SHARE_CREATED,
				],
			],
			[
				Activity::FILTER_FAVORITES,
				[
					'NT0',
					Activity::TYPE_SHARE_CREATED,
					Activity::TYPE_SHARE_CHANGED,
					Activity::TYPE_SHARE_DELETED,
					Activity::TYPE_SHARE_RESTORED,
					Activity::TYPE_FAVORITES,
				], [
					Activity::TYPE_SHARE_CREATED,
					Activity::TYPE_SHARE_CHANGED,
					Activity::TYPE_SHARE_DELETED,
					Activity::TYPE_SHARE_RESTORED,
				],
			],
			[
				'unknown filter',
				[
					'NT0',
					Activity::TYPE_SHARE_CREATED,
					Activity::TYPE_SHARE_CHANGED,
					Activity::TYPE_SHARE_DELETED,
					Activity::TYPE_SHARE_RESTORED,
					Activity::TYPE_FAVORITES,
				],
				false,
			],
		];
	}

	/**
	 * @dataProvider filterNotificationTypesData
	 *
	 * @param string $filter
	 * @param array $types
	 * @param mixed $expected
	 */
	public function testFilterNotificationTypes($filter, $types, $expected) {
		$result = $this->activityExtension->filterNotificationTypes($types, $filter);
		$this->assertEquals($expected, $result);
	}

	public function queryForFilterData() {
		return [
			[
				new \RuntimeException(),
				'`app` = ?',
				['files']
			],
			[
				[
					'items' => [],
					'folders' => [],
				],
				' CASE WHEN `app` = ? THEN ((`type` <> ? AND `type` <> ?)) ELSE `app` <> ? END ',
				['files', Activity::TYPE_SHARE_CREATED, Activity::TYPE_SHARE_CHANGED, 'files']
			],
			[
				[
					'items' => ['file.txt', 'folder'],
					'folders' => ['folder'],
				],
				' CASE WHEN `app` = ? THEN ((`type` <> ? AND `type` <> ?) OR `file` = ? OR `file` = ? OR `file` LIKE ?) ELSE `app` <> ? END ',
				['files', Activity::TYPE_SHARE_CREATED, Activity::TYPE_SHARE_CHANGED, 'file.txt', 'folder', 'folder/%', 'files']
			],
		];
	}

	/**
	 * @dataProvider queryForFilterData
	 *
	 * @param mixed $will
	 * @param string $query
	 * @param array $parameters
	 */
	public function testQueryForFilter($will, $query, $parameters) {
		$this->mockUserSession('test');

		$this->config->expects($this->any())
			->method('getUserValue')
			->willReturnMap([
				['test', 'activity', 'notify_stream_' . Activity::TYPE_FAVORITES, false, true],
			]);
		if (is_array($will)) {
			$this->activityHelper->expects($this->any())
				->method('getFavoriteFilePaths')
				->with('test')
				->willReturn($will);
		} else {
			$this->activityHelper->expects($this->any())
				->method('getFavoriteFilePaths')
				->with('test')
				->willThrowException($will);
		}

		$result = $this->activityExtension->getQueryForFilter('all');
		$this->assertEquals([$query, $parameters], $result);
	}

	protected function mockUserSession($user) {
		$mockUser = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$mockUser->expects($this->any())
			->method('getUID')
			->willReturn($user);

		$this->session->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);
		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($mockUser);
	}
}
