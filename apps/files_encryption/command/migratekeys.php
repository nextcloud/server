<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Files_Encryption\Command;

use OCA\Files_Encryption\Migration;
use OCP\IUserBackend;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateKeys extends Command {

	/** @var \OC\User\Manager */
	private $userManager;

	public function __construct(\OC\User\Manager $userManager) {
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('encryption:migrate-keys')
			->setDescription('migrate encryption keys')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'will migrate keys of the given user(s)'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		// perform system reorganization
		$migration = new Migration();
		$output->writeln("Reorganize system folder structure");
		$migration->reorganizeSystemFolderStructure();

		$users = $input->getArgument('user_id');
		if (!empty($users)) {
			foreach ($users as $user) {
				if ($this->userManager->userExists($user)) {
					$output->writeln("Migrating keys   <info>$user</info>");
					$migration->reorganizeFolderStructureForUser($user);
				} else {
					$output->writeln("<error>Unknown user $user</error>");
				}
			}
		} else {
			foreach($this->userManager->getBackends() as $backend) {
				$name = get_class($backend);

				if ($backend instanceof IUserBackend) {
					$name = $backend->getBackendName();
				}

				$output->writeln("Migrating keys for users on backend <info>$name</info>");

				$limit = 500;
				$offset = 0;
				do {
					$users = $backend->getUsers('', $limit, $offset);
					foreach ($users as $user) {
						$output->writeln("   <info>$user</info>");
						$migration->reorganizeFolderStructureForUser($user);
					}
					$offset += $limit;
				} while(count($users) >= $limit);
			}
		}

	}
}
