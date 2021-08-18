<?php
/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\UserPluginManager;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ResetUser extends Command {
	/** @var DeletedUsersIndex */
	protected $dui;
	/** @var IUserManager */
	private $userManager;
	/** @var UserPluginManager */
	private $pluginManager;

	public function __construct(
		DeletedUsersIndex $dui,
		IUserManager $userManager,
		UserPluginManager $pluginManager
	) {
		$this->dui = $dui;
		$this->userManager = $userManager;
		$this->pluginManager = $pluginManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('ldap:reset-user')
			->setDescription('deletes an LDAP user independent of the user state')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'the user id as used in Nextcloud'
			)
			->addOption(
				'yes',
				'y',
				InputOption::VALUE_NONE,
				'do not ask for confirmation'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$uid = $input->getArgument('uid');
			$user = $this->userManager->get($uid);
			if (!$user instanceof IUser) {
				throw new \Exception('User not found');
			}
			$backend = $user->getBackend();
			if (!$backend instanceof User_Proxy) {
				throw new \Exception('The given user is not a recognized LDAP user.');
			}
			if ($input->getOption('yes') === false) {
				/** @var QuestionHelper $helper */
				$helper = $this->getHelper('question');
				$q = new Question('Delete all local data of this user (y|N)? ');
				$input->setOption('yes', $helper->ask($input, $output, $q) === 'y');
			}
			if ($input->getOption('yes') !== true) {
				throw new \Exception('Reset cancelled by operator');
			}

			$this->dui->markUser($uid);
			$pluginManagerSuppressed = $this->pluginManager->setSuppressDeletion(true);
			if ($user->delete()) {
				$this->pluginManager->setSuppressDeletion($pluginManagerSuppressed);
				return 0;
			}
		} catch (\Throwable $e) {
			if (isset($pluginManagerSuppressed)) {
				$this->pluginManager->setSuppressDeletion($pluginManagerSuppressed);
			}
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}
		$output->writeln('<error>Error while resetting user</error>');
		return 2;
	}
}
