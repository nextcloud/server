<?php

namespace OCA\Dav\AppInfo;

use OCA\DAV\CardDAV\ContactsManager;
use \OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\Contacts\IManager;

class Application extends App {

	/**
	 * Application constructor.
	 *
	 * @param array $urlParams
	 */
	public function __construct (array $urlParams=array()) {
		parent::__construct('dav', $urlParams);

		$container = $this->getContainer();
		$container->registerService('ContactsManager', function($c) {
			/** @var IAppContainer $c */
			return new ContactsManager(
				$c->query('CardDavBackend')
			);
		});

		$container->registerService('CardDavBackend', function($c) {
			/** @var IAppContainer $c */
			$db = $c->getServer()->getDatabaseConnection();
			$logger = $c->getServer()->getLogger();
			$principal = new \OCA\DAV\Connector\Sabre\Principal(
				$c->getServer()->getUserManager()
			);
			return new \OCA\DAV\CardDAV\CardDavBackend($db, $principal, $logger);
		});

	}

	/**
	 * @param IManager $contactsManager
	 * @param string $userID
	 */
	public function setupContactsProvider(IManager $contactsManager, $userID) {
		/** @var ContactsManager $cm */
		$cm = $this->getContainer()->query('ContactsManager');
		$cm->setupContactsProvider($contactsManager, $userID);
	}

}
