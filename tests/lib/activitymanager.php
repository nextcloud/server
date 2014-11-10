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

	protected function setUp() {
		parent::setUp();

		$this->activityManager = new \OC\ActivityManager();
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

	public function testFilterNotificationTypes() {
		$result = $this->activityManager->filterNotificationTypes(array('NT0', 'NT1', 'NT2', 'NT3'), 'FILTER1');
		$this->assertTrue(is_array($result));
		$this->assertEquals(3, sizeof($result));

		$result = $this->activityManager->filterNotificationTypes(array('NT0', 'NT1', 'NT2', 'NT3'), 'FILTER2');
		$this->assertTrue(is_array($result));
		$this->assertEquals(4, sizeof($result));
	}

	public function testDefaultTypes() {
		$result = $this->activityManager->getDefaultTypes('stream');
		$this->assertTrue(is_array($result));
		$this->assertEquals(1, sizeof($result));

		$result = $this->activityManager->getDefaultTypes('email');
		$this->assertTrue(is_array($result));
		$this->assertEquals(0, sizeof($result));
	}

	public function testTranslate() {
		$result = $this->activityManager->translate('APP0', '', '', array(), false, false, 'en');
		$this->assertEquals('Stupid translation', $result);

		$result = $this->activityManager->translate('APP1', '', '', array(), false, false, 'en');
		$this->assertFalse($result);
	}

	public function testTypeIcon() {
		$result = $this->activityManager->getTypeIcon('NT1');
		$this->assertEquals('icon-nt-one', $result);

		$result = $this->activityManager->getTypeIcon('NT2');
		$this->assertEquals('', $result);
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

		$result = $this->activityManager->isFilterValid('FV2');
		$this->assertFalse($result);
	}

	public function testQueryForFilter() {
		$result = $this->activityManager->getQueryForFilter('filter1');
		$this->assertEquals(
			array(
				'`app` = ? and `message` like ?',
				array('mail', 'ownCloud%')
			), $result
		);

		$result = $this->activityManager->isFilterValid('filter2');
		$this->assertFalse($result);
	}
}

class SimpleExtension implements \OCP\Activity\IExtension {

	public function getNotificationTypes($languageCode) {
		return array('NT1', 'NT2');
	}

	public function filterNotificationTypes($types, $filter) {
		if ($filter === 'FILTER1') {
			unset($types[0]);
		}
		return $types;
	}

	public function getDefaultTypes($method) {
		if ($method === 'stream') {
			return array('DT0');
		}

		return array();
	}

	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		if ($app === 'APP0') {
			return "Stupid translation";
		}

		return false;
	}

	public function getTypeIcon($type) {
		if ($type === 'NT1') {
			return 'icon-nt-one';
		}
		return '';
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

	public function getQueryForFilter($filter) {
		if ($filter === 'filter1') {
			return array('`app` = ? and `message` like ?', array('mail', 'ownCloud%'));
		}

		return false;
	}
}

class NoOpExtension implements \OCP\Activity\IExtension {

	public function getNotificationTypes($languageCode) {
		return false;
	}

	public function filterNotificationTypes($types, $filter) {
		return false;
	}

	public function getDefaultTypes($method) {
		return false;
	}

	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		return false;
	}

	public function getTypeIcon($type) {
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

	public function getQueryForFilter($filter) {
		return false;
	}
}
