<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author scolebrook <scolebrook@mac.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\user_ldap\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
			->addOption('json', null, InputOption::VALUE_NONE, 'return JSON array instead of pretty table.');
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
			$rows[] = array('ocName'      => $user->getOCName(),
							'displayName' => $user->getDisplayName(),
							'uid'         => $user->getUid(),
							'dn'          => $user->getDN(),
							'lastLogin'   => $lastLogin,
							'homePath'    => $user->getHomePath(),
							'sharer'      => $hAS
			);
		}

		if ($input->getOption('json')) {
			$output->writeln(json_encode($rows));			
		} else {
			$table->setRows($rows);
			$table->render($output);
		}
	}
}
