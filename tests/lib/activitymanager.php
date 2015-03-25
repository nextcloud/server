<?php

/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
*/

class Test_ActivityManager extends \Test\TestCase {

	/** @var \OC\ActivityManager */
	private $activityManager;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $request;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $session;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $config;

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

		$this->activityManager = new \OC\ActivityManager(
			$this->request,
			$this->session,
			$this->config
		);

		$this->activityManager->registerExtension(function() {
			return new NoOpExtension();
		});
		$this->activityManager->registerExtension(function() {
			return new SimpleExtension();
		});
	}

	public function testNotificationTypes() {
		$result = $this->activityManager->getNotificationTypes('en');
		$this->assertTrue(is_array($result));
		$this->assertEquals(2, sizeof($result));
	}

	public function testDefaultTypes() {
		$result = $this->activityManager->getDefaultTypes('stream');
		$this->assertTrue(is_array($result));
		$this->assertEquals(1, sizeof($result));

		$result = $this->activityManager->getDefaultTypes('email');
		$this->assertTrue(is_array($result));
		$this->assertEquals(0, sizeof($result));
	}

	public function testTypeIcon() {
		$result = $this->activityManager->getTypeIcon('NT1');
		$this->assertEquals('icon-nt-one', $result);

		$result = $this->activityManager->getTypeIcon('NT2');
		$this->assertEquals('', $result);
	}

	public function testTranslate() {
		$result = $this->activityManager->translate('APP0', '', '', array(), false, false, 'en');
		$this->assertEquals('Stupid translation', $result);

		$result = $this->activityManager->translate('APP1', '', '', array(), false, false, 'en');
		$this->assertFalse($result);
	}

	public function testGetSpecialParameterList() {
		$result = $this->activityManager->getSpecialParameterList('APP0', '');
		$this->assertEquals(array(0 => 'file', 1 => 'username'), $result);

		$result = $this->activityManager->getSpecialParameterList('APP1', '');
		$this->assertFalse($result);
	}

	public function testGroupParameter() {
		$result = $this->activityManager->getGroupParameter(array());
		$this->assertEquals(5, $result);
	}

	public function testNavigation() {
		$result = $this->activityManager->getNavigation();
		$this->assertEquals(4, sizeof($result['apps']));
		$this->assertEquals(2, sizeof($result['top']));
	}

	public function testIsFilterValid() {
		$result = $this->activityManager->isFilterValid('fv01');
		$this->assertTrue($result);

		$result = $this->activityManager->isFilterValid('InvalidFilter');
		$this->assertFalse($result);
	}

	public function testFilterNotificationTypes() {
		$result = $this->activityManager->filterNotificationTypes(array('NT0', 'NT1', 'NT2', 'NT3'), 'fv01');
		$this->assertTrue(is_array($result));
		$this->assertEquals(3, sizeof($result));

		$result = $this->activityManager->filterNotificationTypes(array('NT0', 'NT1', 'NT2', 'NT3'), 'InvalidFilter');
		$this->assertTrue(is_array($result));
		$this->assertEquals(4, sizeof($result));
	}

	public function testQueryForFilter() {
		// Register twice, to test the created sql part
		$this->activityManager->registerExtension(function() {
			return new SimpleExtension();
		});

		$result = $this->activityManager->getQueryForFilter('fv01');
		$this->assertEquals(
			array(
				' and ((`app` = ? and `message` like ?) or (`app` = ? and `message` like ?))',
				array('mail', 'ownCloud%', 'mail', 'ownCloud%')
			), $result
		);

		$result = $this->activityManager->getQueryForFilter('InvalidFilter');
		$this->assertEquals(array(null, null), $result);
	}

	public function getUserFromTokenThrowInvalidTokenData() {
		return [
			[null, []],
			['', []],
			['12345678901234567890123456789', []],
			['1234567890123456789012345678901', []],
			['123456789012345678901234567890', []],
			['123456789012345678901234567890', ['user1', 'user2']],
		];
	}

	/**
	 * @expectedException \UnexpectedValueException
	 * @dataProvider getUserFromTokenThrowInvalidTokenData
	 *
	 * @param string $token
	 * @param array $users
	 */
	public function testGetUserFromTokenThrowInvalidToken($token, $users) {
		if ($token !== null) {
			$this->request->expects($this->any())
				->method('getParam')
				->with('token', '')
				->willReturn($token);
		}
		$this->config->expects($this->any())
			->method('getUsersForUserValue')
			->with('activity', 'rsstoken', $token)
			->willReturn($users);

		\Test_Helper::invokePrivate($this->activityManager, 'getUserFromToken');
	}

	public function getUserFromTokenData() {
		return [
			[null, '123456789012345678901234567890', 'user1'],
			['user2', null, 'user2'],
			['user2', '123456789012345678901234567890', 'user2'],
		];
	}

	/**
	 * @dataProvider getUserFromTokenData
	 *
	 * @param string $userLoggedIn
	 * @param string $token
	 * @param string $expected
	 */
	public function testGetUserFromToken($userLoggedIn, $token, $expected) {
		if ($userLoggedIn !== null) {
			$this->mockUserSession($userLoggedIn);
		}

		if ($token !== null) {
			$this->request->expects($this->any())
				->method('getParam')
				->with('token', '')
				->willReturn($token);
		}

		$this->config->expects($this->any())
			->method('getUsersForUserValue')
			->with('activity', 'rsstoken', '123456789012345678901234567890')
			->willReturn(['user1']);

		$this->assertEquals($expected, $this->activityManager->getCurrentUserId());
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

class SimpleExtension implements \OCP\Activity\IExtension {

	public function getNotificationTypes($languageCode) {
		return array('NT1', 'NT2');
	}

	public function getDefaultTypes($method) {
		if ($method === 'stream') {
			return array('DT0');
		}

		return array();
	}

	public function getTypeIcon($type) {
		if ($type === 'NT1') {
			return 'icon-nt-one';
		}
		return '';
	}

	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		if ($app === 'APP0') {
			return "Stupid translation";
		}

		return false;
	}

	public function getSpecialParameterList($app, $text) {
		if ($app === 'APP0') {
			return array(0 => 'file', 1 => 'username');
		}

		return false;
	}

	public function getGroupParameter($activity) {
		return 5;
	}

	public function getNavigation() {
		return array(
			'apps' => array('nav1', 'nav2', 'nav3', 'nav4'),
			'top'  => array('top1', 'top2')
		);
	}

	public function isFilterValid($filterValue) {
		if ($filterValue === 'fv01') {
			return true;
		}

		return false;
	}

	public function filterNotificationTypes($types, $filter) {
		if ($filter === 'fv01') {
			unset($types[0]);
		}
		return $types;
	}

	public function getQueryForFilter($filter) {
		if ($filter === 'fv01') {
			return array('`app` = ? and `message` like ?', array('mail', 'ownCloud%'));
		}

		return false;
	}
}

class NoOpExtension implements \OCP\Activity\IExtension {

	public function getNotificationTypes($languageCode) {
		return false;
	}

	public function getDefaultTypes($method) {
		return false;
	}

	public function getTypeIcon($type) {
		return false;
	}

	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		return false;
	}

	public function getSpecialParameterList($app, $text) {
		return false;
	}

	public function getGroupParameter($activity) {
		return false;
	}

	public function getNavigation() {
		return false;
	}

	public function isFilterValid($filterValue) {
		return false;
	}

	public function filterNotificationTypes($types, $filter) {
		return false;
	}

	public function getQueryForFilter($filter) {
		return false;
	}
}
