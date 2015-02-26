<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\user_ldap\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\user_ldap\lib\user\DeletedUsersIndex;
use OCP\IDateTimeFormatter;

class ShowRemnants extends Command {
	/** @var \OCA\User_LDAP\lib\User\DeletedUsersIndex */
	protected $dui;

	/** @var \OCP\IDateTimeFormatter */
	protected $dateFormatter;

	/**
	 * @param OCA\user_ldap\lib\user\DeletedUsersIndex $dui
	 * @param OCP\IDateTimeFormatter $dateFormatter
	 */
	public function __construct(DeletedUsersIndex $dui, IDateTimeFormatter $dateFormatter) {
		$this->dui = $dui;
		$this->dateFormatter = $dateFormatter;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('ldap:show-remnants')
			->setDescription('shows which users are not available on LDAP anymore, but have remnants in ownCloud.')
		;
	}

	/**
	 * executes the command, i.e. creeates and outputs a table of LDAP users marked as deleted
	 *
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		/** @var \Symfony\Component\Console\Helper\Table $table */
		$table = $this->getHelperSet()->get('table');
		$table->setHeaders(array(
			'ownCloud name', 'Display Name', 'LDAP UID', 'LDAP DN', 'Last Login',
			'Dir', 'Sharer'));
		$rows = array();
		$resultSet = $this->dui->getUsers();
		foreach($resultSet as $user) {
			$hAS = $user->getHasActiveShares() ? 'Y' : 'N';
			$lastLogin = ($user->getLastLogin() > 0) ?
				$this->dateFormatter->formatDate($user->getLastLogin()) : '-';
			$rows[] = array(
				$user->getOCName(),
				$user->getDisplayName(),
				$user->getUid(),
				$user->getDN(),
				$lastLogin,
				$user->getHomePath(),
				$hAS
			);
		}

		$table->setRows($rows);
		$table->render($output);
	}
}
