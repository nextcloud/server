<?php

namespace OC\Comments;

use OCP\Comments\ICommentsManager;
use OCP\Comments\ICommentsManagerFactory;


class ManagerFactory implements ICommentsManagerFactory {

	/**
	 * creates and returns an instance of the ICommentsManager
	 *
	 * @return ICommentsManager
	 * @since 9.0.0
	 */
	public function getManager() {
		return new Manager(
			\oc::$server->getDatabaseConnection(),
			\oc::$server->getLogger()
		);
	}
}
