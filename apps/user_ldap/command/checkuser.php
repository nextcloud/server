<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\user_ldap\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\user_ldap\lib\user\User;
use OCA\User_LDAP\lib\User\DeletedUsersIndex;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\user_ldap\lib\Helper as LDAPHelper;
use OCA\user_ldap\User_Proxy;

class CheckUser extends Command {
	/** @var \OCA\user_ldap\User_Proxy */
	protected $backend;

	/** @var \OCA\User_LDAP\lib\Helper */
	protected $helper;

	/** @var \OCA\User_LDAP\lib\User\DeletedUsersIndex */
	protected $dui;

	/** @var \OCA\User_LDAP\Mapping\UserMapping */
	protected $mapping;

	/**
	 * @param OCA\user_ldap\User_Proxy $uBackend
	 * @param OCA\user_ldap\lib\Helper $helper
	 * @param OCA\User_LDAP\lib\User\DeletedUsersIndex $dui
	 * @param OCA\User_LDAP\Mapping\UserMapping $mapping
	 */
	public function __construct(User_Proxy $uBackend, LDAPHelper $helper, DeletedUsersIndex $dui, UserMapping $mapping) {
		$this->backend = $uBackend;
		$this->helper = $helper;
		$this->dui = $dui;
		$this->mapping = $mapping;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('ldap:check-user')
			->setDescription('checks whether a user exists on LDAP.')
			->addArgument(
					'ocName',
					InputArgument::REQUIRED,
					'the user name as used in ownCloud'
				     )
			->addOption(
					'force',
					null,
					InputOption::VALUE_NONE,
					'ignores disabled LDAP configuration'
				     )
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		try {
			$uid = $input->getArgument('ocName');
			$this->isAllowed($input->getOption('force'));
			$this->confirmUserIsMapped($uid);
			$exists = $this->backend->userExistsOnLDAP($uid);
			if($exists === true) {
				$output->writeln('The user is still available on LDAP.');
				return;
			}

			$this->dui->markUser($uid);
			$output->writeln('The user does not exists on LDAP anymore.');
			$output->writeln('Clean up the user\'s remnants by: ./occ user:delete "'
				. $uid . '"');
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage(). '</error>');
		}
	}

	/**
	 * checks whether a user is actually mapped
	 * @param string $ocName the username as used in ownCloud
	 * @throws \Exception
	 * @return true
	 */
	protected function confirmUserIsMapped($ocName) {
		$dn = $this->mapping->getDNByName($ocName);
		if ($dn === false) {
			throw new \Exception('The given user is not a recognized LDAP user.');
		}

		return true;
	}

	/**
	 * checks whether the setup allows reliable checking of LDAP user existence
	 * @throws \Exception
	 * @return true
	 */
	protected function isAllowed($force) {
		if($this->helper->haveDisabledConfigurations() && !$force) {
			throw new \Exception('Cannot check user existence, because '
				. 'disabled LDAP configurations are present.');
		}

		// we don't check ldapUserCleanupInterval from config.php because this
		// action is triggered manually, while the setting only controls the
		// background job.

		return true;
	}

}
