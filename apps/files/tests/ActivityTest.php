<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OCA\Files\Tests;

use OCA\Files\Activity;
use Test\TestCase;

/**
 * Class ActivityTest
 *
 * @group DB
 * @package OCA\Files\Tests
 */
class ActivityTest extends TestCase {

	/** @var \OCP\Activity\IManager */
	private $activityManager;

	/** @var \OCP\IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;

	/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $session;

	/** @var \OCP\IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;

	/** @var \OCA\Files\ActivityHelper|\PHPUnit_Framework_MockObject_MockObject */
	protected $activityHelper;

	/** @var \OCP\L10N\IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $l10nFactory;

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

		$this->activityManager = new \OC\Activity\Manager(
			$this->request,
			$this->session,
			$this->config
		);

		$this->l10nFactory = $this->getMockBuilder('OCP\L10N\IFactory')
			->disableOriginalConstructor()
			->getMock();
		$deL10n = $this->getMockBuilder('OC_L10N')
			->disableOriginalConstructor()
			->getMock();
		$deL10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($argument) {
				return 'translate(' . $argument . ')';
			});

		$this->l10nFactory->expects($this->any())
			->method('get')
			->willReturnMap([
				['files', null, new \OC_L10N('files', 'en')],
				['files', 'en', new \OC_L10N('files', 'en')],
				['files', 'de', $deL10n],
			]);

		$this->activityExtension = $activityExtension = new Activity(
			$this->l10nFactory,
			$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
			$this->activityManager,
			$this->activityHelper,
			\OC::$server->getDatabaseConnection(),
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

		// Test english
		$this->assertNotFalse(
			$this->activityExtension->translate('files', 'deleted_self', ['file'], false, false, 'en'),
			'Asserting that translations are set for files.deleted_self'
		);
		$this->assertStringStartsWith(
			'You deleted ',
			$this->activityExtension->translate('files', 'deleted_self', ['file'], false, false, 'en')
		);

		// Test translation
		$this->assertNotFalse(
			$this->activityExtension->translate('files', 'deleted_self', ['file'], false, false, 'de'),
			'Asserting that translations are set for files.deleted_self'
		);
		$this->assertStringStartsWith(
			'translate(You deleted ',
			$this->activityExtension->translate('files', 'deleted_self', ['file'], false, false, 'de')
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
				' CASE WHEN `app` <> ? THEN 1 WHEN `app` = ? AND ((`type` <> ? AND `type` <> ?)) THEN 1 ELSE 0 END = 1 ',
				['files', 'files', Activity::TYPE_SHARE_CREATED, Activity::TYPE_SHARE_CHANGED]
			],
			[
				[
					'items' => ['file.txt', 'folder'],
					'folders' => ['folder'],
				],
				' CASE WHEN `app` <> ? THEN 1 WHEN `app` = ? AND ((`type` <> ? AND `type` <> ?) OR `file` = ? OR `file` = ? OR `file` LIKE ?) THEN 1 ELSE 0 END = 1 ',
				['files', 'files', Activity::TYPE_SHARE_CREATED, Activity::TYPE_SHARE_CHANGED, 'file.txt', 'folder', 'folder/%']
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

		$this->executeQueryForFilter($result);
	}

	public function executeQueryForFilter(array $result) {
		list($resultQuery, $resultParameters) = $result;
		$resultQuery = str_replace('`file`', '`user`', $resultQuery);
		$resultQuery = str_replace('`type`', '`key`', $resultQuery);

		$connection = \OC::$server->getDatabaseConnection();
		// Test the query on the privatedata table, because the activity table
		// does not exist in core
		$result = $connection->executeQuery('SELECT * FROM `*PREFIX*privatedata` WHERE ' . $resultQuery, $resultParameters);
		$rows = $result->fetchAll();
		$result->closeCursor();
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
