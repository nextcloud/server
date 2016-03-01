<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Dav\Command;

use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateAddressbooks extends Command {

	/** @var IUserManager */
	protected $userManager;

	/** @var \OCA\Dav\Migration\MigrateAddressbooks  */
	private $service;

	/**
	 * @param IUserManager $userManager
	 * @param \OCA\Dav\Migration\MigrateAddressbooks $service
	 */
	function __construct(IUserManager $userManager,
						 \OCA\Dav\Migration\MigrateAddressbooks $service
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->service = $service;
	}

	protected function configure() {
		$this
			->setName('dav:migrate-addressbooks')
			->setDescription('Migrate addressbooks from the contacts app to core')
			->addArgument('user',
				InputArgument::OPTIONAL,
				'User for whom all addressbooks will be migrated');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->service->setup();

		$user = $input->getArgument('user');
		if (!is_null($user)) {
			if (!$this->userManager->userExists($user)) {
				throw new \InvalidArgumentException("User <$user> in unknown.");
			}
			$output->writeln("Start migration for $user");
			$this->service->migrateForUser($user);
			return;
		}
		$output->writeln("Start migration of all known users ...");
		$p = new ProgressBar($output);
		$p->start();
		$this->userManager->callForAllUsers(function($user) use ($p) {
			$p->advance();
			/** @var IUser $user */
			$this->service->migrateForUser($user->getUID());
		});

		$p->finish();
		$output->writeln('');
	}
}
