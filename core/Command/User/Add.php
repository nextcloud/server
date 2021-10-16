<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Laurens Post <lkpost@scept.re>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use OC\Files\Filesystem;
use OCA\Settings\Mailer\NewUserMailHelper;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\ISecureRandom;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Add extends Command {
	/**
	 * @var IUserManager
	 */
	protected $userManager;

	/**
	 * @var IGroupManager
	 */
	protected $groupManager;

	/**
	 * @var EmailValidator
	 */
	protected $emailValidator;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var NewUserMailHelper
	 */
	private $mailHelper;

	/**
	 * @var IEventDispatcher
	 */
	private $eventDispatcher;

	/**
	 * @var ISecureRandom
	 */
	private $secureRandom;

	/**
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param EmailValidator $emailValidator
	 */
	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		EmailValidator $emailValidator,
		IConfig $config,
		NewUserMailHelper $mailHelper,
		IEventDispatcher $eventDispatcher,
		ISecureRandom $secureRandom
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->emailValidator = $emailValidator;
		$this->config = $config;
		$this->mailHelper = $mailHelper;
		$this->eventDispatcher = $eventDispatcher;
		$this->secureRandom = $secureRandom;
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
				'password-from-env',
				null,
				InputOption::VALUE_NONE,
				'read password from environment variable OC_PASS'
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
			)
			->addOption(
				'email',
				null,
				InputOption::VALUE_REQUIRED,
				'When set, users may register using the default E-Mail verification workflow'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');
		$emailIsSet = \is_string($input->getOption('email')) && \mb_strlen($input->getOption('email')) > 0;
		$emailIsValid = $this->emailValidator->isValid($input->getOption('email') ?? '', new RFCValidation());
		$password = '';
		$temporaryPassword = '';

		if ($this->userManager->userExists($uid)) {
			$output->writeln('<error>The user "' . $uid . '" already exists.</error>');
			return 1;
		}

		if ($input->getOption('password-from-env')) {
			$password = getenv('OC_PASS');

			if (!$password) {
				$output->writeln('<error>--password-from-env given, but OC_PASS is empty!</error>');
				return 1;
			}
		} elseif ($input->isInteractive()) {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');

			$question = new Question('Enter password: ');
			$question->setHidden(true);
			$password = $helper->ask($input, $output, $question);

			$question = new Question('Confirm password: ');
			$question->setHidden(true);
			$confirm = $helper->ask($input, $output, $question);

			if ($password !== $confirm) {
				$output->writeln("<error>Passwords did not match!</error>");
				return 1;
			}
		} else {
			$output->writeln("<error>Interactive input or --password-from-env is needed for entering a password!</error>");
			return 1;
		}

		if (trim($password) === '' && $emailIsSet) {
			if ($emailIsValid) {
				$output->writeln('Setting a temporary password.');

				$temporaryPassword = $this->getTemporaryPassword();
			} else {
				$output->writeln(\sprintf(
					'<error>The given E-Mail address "%s" is invalid: %s</error>',
					$input->getOption('email'),
					$this->emailValidator->getError()->description()
				));

				return 1;
			}
		}

		try {
			$user = $this->userManager->createUser(
				$input->getArgument('uid'),
				$password ?: $temporaryPassword
			);
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}

		if ($user instanceof IUser) {
			$output->writeln('<info>The user "' . $user->getUID() . '" was created successfully</info>');
		} else {
			$output->writeln('<error>An error occurred while creating the user</error>');
			return 1;
		}

		if ($input->getOption('display-name')) {
			$user->setDisplayName($input->getOption('display-name'));
			$output->writeln(sprintf('Display name set to "%s"', $user->getDisplayName()));
		}

		if ($emailIsSet && $emailIsValid) {
			$user->setSystemEMailAddress($input->getOption('email'));
			$output->writeln(sprintf('E-Mail set to "%s"', (string) $user->getSystemEMailAddress()));

			if (trim($password) === '' && $this->config->getAppValue('core', 'newUser.sendEmail', 'yes') === 'yes') {
				try {
					$this->mailHelper->sendMail(
						$user,
						$this->mailHelper->generateTemplate($user, true)
					);
					$output->writeln('Invitation E-Mail sent.');
				} catch (\Exception $e) {
					$output->writeln(\sprintf('Unable to send the invitation mail to %s', $user->getEMailAddress()));
				}
			}
		}

		$groups = $input->getOption('group');

		if (!empty($groups)) {
			// Make sure we init the Filesystem for the user, in case we need to
			// init some group shares.
			Filesystem::init($user->getUID(), '');
		}

		foreach ($groups as $groupName) {
			$group = $this->groupManager->get($groupName);
			if (!$group) {
				$this->groupManager->createGroup($groupName);
				$group = $this->groupManager->get($groupName);
				if ($group instanceof IGroup) {
					$output->writeln('Created group "' . $group->getGID() . '"');
				}
			}
			if ($group instanceof IGroup) {
				$group->addUser($user);
				$output->writeln('User "' . $user->getUID() . '" added to group "' . $group->getGID() . '"');
			}
		}
		return 0;
	}

	/**
	 * @return string
	 */
	protected function getTemporaryPassword(): string
	{
		$passwordEvent = new GenerateSecurePasswordEvent();

		$this->eventDispatcher->dispatchTyped($passwordEvent);

		return $passwordEvent->getPassword() ?? $this->secureRandom->generate(20);
	}
}
