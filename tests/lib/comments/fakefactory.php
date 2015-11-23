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

	public function testNothing() {
		// If there would not be at least one test, phpunit would scream failure
		// So we have one and skip it.
		$this->markTestSkipped();
	}

	public function getManager() {
		return $this->getMock('\OCP\Comments\ICommentsManager');
	}
}
