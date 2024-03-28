<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Laurens Post <lkpost@scept.re>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sujith H <sharidasan@owncloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command\User;

use OC\Core\Command\Base;
use OCP\App\IAppManager;
use OCP\IUser;
use OCP\IUserManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class CheckPassword extends Base {
	public function __construct(
		protected IUserManager $userManager,
		private IAppManager $appManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:checkpassword')
			->setDescription('Look up the user by ID and check the given password')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'User ID'
			)
			->addArgument(
				'password',
				InputArgument::OPTIONAL,
				'User\'s password - if not provided, will be prompted interactively if a TTY is available or read from STDIN'
			)
			->addOption(
				'password-from-env',
				null,
				InputOption::VALUE_NONE,
				'read password from environment variable OC_PASS'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userID = $input->getArgument('user');

		$user = $this->userManager->get($userID);
		
		if (is_null($user)) {
			$output->writeln('<error>User does not exist</error>');
			return 1;
		}

		// Get from the environment variable
		if ($input->getOption('password-from-env')) {
			$password = getenv('OC_PASS');
			if (!$password) {
				$output->writeln('<error>--password-from-env given, but OC_PASS is empty!</error>');
				return 2;
			}
		} else {
		// Was an argument provided?
			$password = $input->getArgument('password');

			if (is_null($password)) {
				// It was not.

				//If interactive, we prompt
				if ($input->isInteractive()) {
					/** @var QuestionHelper $helper */
					$helper = $this->getHelper('question');
		
					$question = new Question('Enter user\'s password: ');
					$question->setHidden(true);
					$password = $helper->ask($input, $output, $question);
		
					if ($password === null) {
						$output->writeln("<error>Password cannot be empty!</error>");
						return 2;
					}
				} else {
					// FIXME:
					// isInteractive seems to return true even when data are piped in
					// We want to detect if we are in a pipeline and not prompt for the question

					// Else, we try readin from stdin if the user piped it
					$password  = fgets(STDIN);
					if ($password === false) {
						// STDIN was closed.
						// No password provided, and not interactive, so we can't do anything.
						$output->writeln('<error>--Password not given but TTY is not interactive and no data on STDIN!</error>');
						return 2;
					}
					
				}
			}

		} // argument not provided

		if ($this->userManager->checkPassword($userID, $password)) {
			$output->writeln('<info>Password okay</info>');
			return 0;
		} else {
			$output->writeln('<error>Password is incorrect</error>');
			return 1;
		}


	}


	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'user') {
			return array_map(static fn (IUser $user) => $user->getUID(), $this->userManager->search($context->getCurrentWord()));
		}
		return [];
	}
}
