<?php

namespace OC;

use OC\AppFramework\Utility\SimpleContainer;
use OCP\Core\IServerContainer;

/**
 * Class Server
 * @package OC
 *
 * TODO: hookup all manager classes
 */
class Server extends SimpleContainer implements IServerContainer {

	function __construct() {
		$this->registerService('ContactsManager', function($c){
			return new ContactsManager();
		});
	}

	/**
	 * @return \OCP\Core\Contacts\IManager
	 */
	function getContactsManager() {
		return $this->query('ContactsManager');
	}
}
