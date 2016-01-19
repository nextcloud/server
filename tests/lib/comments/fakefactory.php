<?php

namespace Test\Comments;

use OCP\IServerContainer;

/**
 * Class FakeFactory
 */
class FakeFactory implements \OCP\Comments\ICommentsManagerFactory {

	public function __construct(IServerContainer $serverContainer) {
	}

	public function getManager() {
		return new FakeManager();
	}
}
