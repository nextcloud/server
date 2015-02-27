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

use OCA\user_ldap\User_Proxy;
use OCA\user_ldap\Group_Proxy;
use OCA\user_ldap\lib\Helper;
use OCA\user_ldap\lib\LDAP;
use OCP\IConfig;

class Search extends Command {
	/** @var \OCP\IConfig */
	protected $ocConfig;

	/**
	 * @param \OCP\IConfig $ocConfig
	 */
	public function __construct(IConfig $ocConfig) {
		$this->ocConfig = $ocConfig;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('ldap:search')
			->setDescription('executes a user or group search')
			->addArgument(
					'search',
					InputArgument::REQUIRED,
					'the search string (can be empty)'
				     )
			->addOption(
					'group',
					null,
					InputOption::VALUE_NONE,
					'searches groups instead of users'
				     )
			->addOption(
					'offset',
					null,
					InputOption::VALUE_REQUIRED,
					'The offset of the result set. Needs to be a multiple of limit. defaults to 0.',
					0
				     )
			->addOption(
					'limit',
					null,
					InputOption::VALUE_REQUIRED,
					'limit the results. 0 means no limit, defaults to 15',
					15
				     )
		;
	}

	/**
	 * Tests whether the offset and limit options are valid
	 * @param int $offset
	 * @param int $limit
	 * @throws \InvalidArgumentException
	 */
	protected function validateOffsetAndLimit($offset, $limit) {
		if($limit < 0) {
			throw new \InvalidArgumentException('limit must be  0 or greater');
		}
		if($offset  < 0) {
			throw new \InvalidArgumentException('offset must be 0 or greater');
		}
		if($limit === 0 && $offset !== 0) {
			throw new \InvalidArgumentException('offset must be 0 if limit is also set to 0');
		}
		if($offset > 0 && ($offset % $limit !== 0)) {
			throw new \InvalidArgumentException('offset must be a multiple of limit');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$helper = new Helper();
		$configPrefixes = $helper->getServerConfigurationPrefixes(true);
		$ldapWrapper = new LDAP();

		$offset = intval($input->getOption('offset'));
		$limit = intval($input->getOption('limit'));
		$this->validateOffsetAndLimit($offset, $limit);

		if($input->getOption('group')) {
			$proxy = new Group_Proxy($configPrefixes, $ldapWrapper);
			$getMethod = 'getGroups';
			$printID = false;
		} else {
			$proxy = new User_Proxy($configPrefixes, $ldapWrapper, $this->ocConfig);
			$getMethod = 'getDisplayNames';
			$printID = true;
		}

		$result = $proxy->$getMethod($input->getArgument('search'), $limit, $offset);
		foreach($result as $id => $name) {
			$line = $name . ($printID ? ' ('.$id.')' : '');
			$output->writeln($line);
		}
	}
}
