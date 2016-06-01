<?php
/**
 * @author Christoph Wurst <christoph@owncloud.com>
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

namespace OC\Core\Command\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\Manager;
use OC\User\Manager as UserManager;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Disable extends Base {

	/** @var Manager */
	private $manager;

	/** @var UserManager */
	private $userManager;

	public function __construct(Manager $manager, UserManager $userManager) {
		parent::__construct('twofactorauth:disable');
		$this->manager = $manager;
		$this->userManager = $userManager;
	}

	protected function configure() {
		parent::configure();

		$this->setName('twofactorauth:disable');
		$this->setDescription('Disable two-factor authentication for a user');
		$this->addArgument('uid', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$uid = $input->getArgument('uid');
		$user = $this->userManager->get($uid);
		if (is_null($user)) {
			$output->writeln("<error>Invalid UID</error>");
			return;
		}
		$this->manager->disableTwoFactorAuthentication($user);
		$output->writeln("Two-factor authentication disabled for user $uid");
	}

}
