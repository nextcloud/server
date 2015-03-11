<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\User;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;

class Add extends Command {
	/** @var \OCP\IUserManager */
	protected $userManager;

	/** @var \OCP\IGroupManager */
	protected $groupManager;

	/**
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 */
	public function __construct(IUserManager $userManager, IGroupManager $groupManager) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}

	protected function configure() {
		$this
			->setName('user:add')
			->setDescription('adds a user')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'User ID used to login (must only contain a-z, A-Z, 0-9, -, _ and @)'
			)
			->addOption(
				'password',
				'p',
				InputOption::VALUE_OPTIONAL,
				''
			)
			->addOption(
				'display-name',
				null,
				InputOption::VALUE_OPTIONAL,
				'User name used in the web UI (can contain any characters)'
			)
			->addOption(
				'group',
				'g',
				InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
				'groups the user should be added to (The group will be created if it does not exist)'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$uid = $input->getArgument('uid');
		if ($this->userManager->userExists($uid)) {
			$output->writeln('<error>The user "' . $uid . '" already exists.</error>');
			return;
		}

		$password = $input->getOption('password');
		while (!$password) {
			$question = new Question('Please enter a non-empty password:');
			$question->setHidden(true);
			$question->setHiddenFallback(false);

			$helper = $this->getHelper('question');
			$password = $helper->ask($input, $output, $question);
		}

		$user = $this->userManager->createUser(
			$input->getArgument('uid'),
			$password
		);

		if ($user instanceof IUser) {
			$output->writeln('The user "' . $user->getUID() . '" was created successfully');
		} else {
			$output->writeln('<error>An error occurred while creating the user</error>');
		}

		if ($input->getOption('display-name')) {
			$user->setDisplayName($input->getOption('display-name'));
			$output->writeln('Display name set to "' . $user->getDisplayName() . '"');
		}

		foreach ($input->getOption('group') as $groupName) {
			$group = $this->groupManager->get($groupName);
			if (!$group) {
				$this->groupManager->createGroup($groupName);
				$group = $this->groupManager->get($groupName);
				$output->writeln('Created group "' . $group->getGID() . '"');
			}
			$group->addUser($user);
			$output->writeln('User "' . $user->getUID() . '" added to group "' . $group->getGID() . '"');
		}
	}
}
