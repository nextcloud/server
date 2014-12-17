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
use OCA\User_LDAP\lib\user\Manager;
use OCA\user_ldap\lib\Helper;
use OCA\user_ldap\User_Proxy;

class CheckUser extends Command {
	/** @var \OCA\user_ldap\User_Proxy */
	protected $backend;

	/** @var \OCA\User_LDAP\lib\Helper */
	protected $helper;

	/** @var \OCP\IConfig */
	protected $config;

	/**
	 * @param OCA\user_ldap\User_Proxy $uBackend
	 * @param OCA\User_LDAP\lib\Helper $helper
	 * @param OCP\IConfig $config
	 */
	public function __construct(User_Proxy $uBackend, Helper $helper, \OCP\IConfig $config) {
		$this->backend = $uBackend;
		$this->helper = $helper;
		$this->config = $config;
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

			// TODO FIXME consolidate next line in DeletedUsersIndex
			// (impractical now, because of class dependencies)
			$this->config->setUserValue($uid, 'user_ldap', 'isDeleted', '1');

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
	 * @return bool
	 */
	protected function confirmUserIsMapped($ocName) {
		//TODO FIXME this should go to Mappings in OC 8
		$db = \OC::$server->getDatabaseConnection();
		$query = $db->prepare('
			SELECT
				`ldap_dn` AS `dn`
			FROM `*PREFIX*ldap_user_mapping`
			WHERE `owncloud_name` = ?'
		);

		$query->execute(array($ocName));
		$result = $query->fetchColumn();

		if($result === false) {
			throw new \Exception('The given user is not a recognized LDAP user.');
		}

		return true;
	}

	/**
	 * checks whether the setup allows reliable checking of LDAP user existance
	 * @throws \Exception
	 * @return bool
	 */
	protected function isAllowed($force) {
		if($this->helper->haveDisabledConfigurations() && !$force) {
			throw new \Exception('Cannot check user existance, because '
				. 'disabled LDAP configurations are present.');
		}

		// we don't check ldapUserCleanupInterval from config.php because this
		// action is triggered manually, while the setting only controls the
		// background job.

		return true;
	}

}
