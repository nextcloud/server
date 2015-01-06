<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\user_ldap\tests;

class Test_CleanUp extends \PHPUnit_Framework_TestCase {
	public function getMocks() {
		$mocks = array();
		$mocks['userBackend'] =
			$this->getMockBuilder('\OCA\user_ldap\User_Proxy')
				->disableOriginalConstructor()
				->getMock();
		$mocks['deletedUsersIndex'] =
			$this->getMockBuilder('\OCA\user_ldap\lib\user\deletedUsersIndex')
				->disableOriginalConstructor()
				->getMock();
		$mocks['ocConfig']    = $this->getMock('\OCP\IConfig');
		$mocks['db']          = $this->getMock('\OCP\IDBConnection');
		$mocks['helper']      = $this->getMock('\OCA\user_ldap\lib\Helper');

		return $mocks;
	}

	/**
	 * clean up job must not run when there are disabled configurations
	 */
	public function test_runNotAllowedByDisabledConfigurations() {
		$args = $this->getMocks();
		$args['helper']->expects($this->once())
			->method('haveDisabledConfigurations')
			->will($this->returnValue(true)	);

		$args['ocConfig']->expects($this->never())
			->method('getSystemValue');

		$bgJob = new \OCA\User_LDAP\Jobs\CleanUp();
		$bgJob->setArguments($args);

		$result = $bgJob->isCleanUpAllowed();
		$this->assertSame(false, $result);
	}

	/**
	 * clean up job must not run when LDAP Helper is broken i.e.
	 * returning unexpected results
	 */
	public function test_runNotAllowedByBrokenHelper() {
		$args = $this->getMocks();
		$args['helper']->expects($this->once())
			->method('haveDisabledConfigurations')
			->will($this->throwException(new \Exception()));

		$args['ocConfig']->expects($this->never())
			->method('getSystemValue');

		$bgJob = new \OCA\User_LDAP\Jobs\CleanUp();
		$bgJob->setArguments($args);

		$result = $bgJob->isCleanUpAllowed();
		$this->assertSame(false, $result);
	}

	/**
	 * clean up job must not run when it is not enabled
	 */
	public function test_runNotAllowedBySysConfig() {
		$args = $this->getMocks();
		$args['helper']->expects($this->once())
			->method('haveDisabledConfigurations')
			->will($this->returnValue(false));

		$args['ocConfig']->expects($this->once())
			->method('getSystemValue')
			->will($this->returnValue(false));

		$bgJob = new \OCA\User_LDAP\Jobs\CleanUp();
		$bgJob->setArguments($args);

		$result = $bgJob->isCleanUpAllowed();
		$this->assertSame(false, $result);
	}

	/**
	 * clean up job is allowed to run
	 */
	public function test_runIsAllowed() {
		$args = $this->getMocks();
		$args['helper']->expects($this->once())
			->method('haveDisabledConfigurations')
			->will($this->returnValue(false));

		$args['ocConfig']->expects($this->once())
			->method('getSystemValue')
			->will($this->returnValue(true));

		$bgJob = new \OCA\User_LDAP\Jobs\CleanUp();
		$bgJob->setArguments($args);

		$result = $bgJob->isCleanUpAllowed();
		$this->assertSame(true, $result);
	}

	/**
	 * check whether offset will be reset when it needs to
	 */
	public function test_OffsetResetIsNecessary() {
		$args = $this->getMocks();

		$bgJob = new \OCA\User_LDAP\Jobs\CleanUp();
		$bgJob->setArguments($args);

		$result = $bgJob->isOffsetResetNecessary($bgJob->getChunkSize() - 1);
		$this->assertSame(true, $result);
	}

	/**
	 * make sure offset is not reset when it is not due
	 */
	public function test_OffsetResetIsNotNecessary() {
		$args = $this->getMocks();

		$bgJob = new \OCA\User_LDAP\Jobs\CleanUp();
		$bgJob->setArguments($args);

		$result = $bgJob->isOffsetResetNecessary($bgJob->getChunkSize());
		$this->assertSame(false, $result);
	}

}

