<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\BirthdayService;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncBirthdayCalendar extends Command {

	/** @var BirthdayService */
	private $birthdayService;

	/** @var IUserManager */
	private $userManager;

	/**
	 * @param IUserManager $userManager
	 * @param BirthdayService $birthdayService
	 */
	function __construct(IUserManager $userManager, BirthdayService $birthdayService) {
		parent::__construct();
		$this->birthdayService = $birthdayService;
		$this->userManager = $userManager;
	}

	protected function configure() {
		$this
			->setName('dav:sync-birthday-calendar')
			->setDescription('Synchronizes the birthday calendar')
			->addArgument('user',
				InputArgument::OPTIONAL,
				'User for whom the birthday calendar will be synchronized');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$user = $input->getArgument('user');
		if (!is_null($user)) {
			if (!$this->userManager->userExists($user)) {
				throw new \InvalidArgumentException("User <$user> in unknown.");
			}
			$output->writeln("Start birthday calendar sync for $user");
			$this->birthdayService->syncUser($user);
			return;
		}
		$output->writeln("Start birthday calendar sync for all users ...");
		$p = new ProgressBar($output);
		$p->start();
		$this->userManager->callForAllUsers(function($user) use ($p)  {
			$p->advance();
			/** @var IUser $user */
			$this->birthdayService->syncUser($user->getUID());
		});

		$p->finish();
		$output->writeln('');
	}
}
