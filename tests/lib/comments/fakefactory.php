<?php

namespace Test\Comments;

/**
 * Class FakeFactory
 */
class FakeFactory implements \OCP\Comments\ICommentsManagerFactory {

	public function getManager() {
		return new FakeManager();
	}
}
