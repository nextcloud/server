<?php

namespace Test\Comments;

use Test\TestCase;

/**
 * Class Test_Comments_FakeFactory
 */
class Test_Comments_FakeFactory extends TestCase implements \OCP\Comments\ICommentsManagerFactory {

	public function getManager() {
		return $this->getMock('\OCP\Comments\ICommentsManager');
	}

	public function testOverwriteDefaultManager() {
		$config = \OC::$server->getConfig();
		$defaultManagerFactory = $config->getSystemValue('comments.managerFactory', '\OC\Comments\ManagerFactory');

		$managerMock = $this->getMock('\OCP\Comments\ICommentsManager');

		$config->setSystemValue('comments.managerFactory', '\Test\Comments\Test_Comments_FakeFactory');
		$manager = \OC::$server->getCommentsManager();
		$this->assertEquals($managerMock, $manager);

		$config->setSystemValue('comments.managerFactory', $defaultManagerFactory);
	}
}
