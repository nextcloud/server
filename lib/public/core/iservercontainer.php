<?php

namespace OCP\Core;


/**
 * Class IServerContainer
 * @package OCP\Core
 *
 * This container holds all ownCloud services
 */
interface IServerContainer {

	/**
	 * @return \OCP\Core\Contacts\IManager
	 */
	function getContactsManager();
}
