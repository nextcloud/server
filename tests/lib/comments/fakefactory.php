<?php

/**
 * Class Test_Comments_FakeFactory
 *
 * this class does not contain any tests. It's sole purpose is to return
 * a mock of \OCP\Comments\ICommentsManager when getManager() is called.
 * For mock creation and auto-loading it extends Test\TestCase. I am, uh, really
 * sorry for this hack.
 */
class Test_Comments_FakeFactory extends Test\TestCase implements \OCP\Comments\ICommentsManagerFactory {

	public function getManager() {
		return $this->getMock('\OCP\Comments\ICommentsManager');
	}

	public function testOverwriteDefaultManager() {
		$config = \OC::$server->getConfig();
		$defaultManagerFactory = $config->getSystemValue('comments.managerFactory', '\OC\Comments\ManagerFactory');

		$managerMock = $this->getMock('\OCP\Comments\ICommentsManager');

		$config->setSystemValue('comments.managerFactory', 'Test_Comments_FakeFactory');
		$manager = \OC::$server->getCommentsManager();
		$this->assertEquals($managerMock, $manager);

		$config->setSystemValue('comments.managerFactory', $defaultManagerFactory);
	}
}
