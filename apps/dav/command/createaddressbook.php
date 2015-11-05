<?php

namespace OCA\DAV\Command;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\IDBConnection;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateAddressBook extends Command {

	/** @var IUserManager */
	protected $userManager;

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/**
	 * @param IUserManager $userManager
	 * @param IDBConnection $dbConnection
	 */
	function __construct(IUserManager $userManager, IDBConnection $dbConnection) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->dbConnection = $dbConnection;
	}

	protected function configure() {
		$this
			->setName('dav:create-addressbook')
			->setDescription('Create a dav addressbook')
			->addArgument('user',
				InputArgument::REQUIRED,
				'User for whom the addressbook will be created')
			->addArgument('name',
				InputArgument::REQUIRED,
				'Name of the addressbook');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$user = $input->getArgument('user');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User <$user> in unknown.");
		}
		$name = $input->getArgument('name');
		$carddav = new CardDavBackend($this->dbConnection);
		$carddav->createAddressBook("principals/$user", $name, []);
	}
}
