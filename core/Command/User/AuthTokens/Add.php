<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\User\AuthTokens;

use OC\Authentication\Events\AppPasswordCreatedEvent;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Add extends Command {
	public function __construct(
		protected IUserManager $userManager,
		protected IProvider $tokenProvider,
		private ISecureRandom $random,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:auth-tokens:add')
			->setAliases(['user:add-app-password'])
			->setDescription('Add app password for the named account')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'Login to add app password for'
			)
			->addOption(
				'password-from-env',
				null,
				InputOption::VALUE_NONE,
				'Read password from environment variable NC_PASS/OC_PASS. Alternatively it will be asked for interactively or an app password without the login password will be created.'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$username = $input->getArgument('user');
		$password = null;

		$user = $this->userManager->get($username);
		if (is_null($user)) {
			$output->writeln('<error>Account does not exist</error>');
			return 1;
		}

		if ($input->getOption('password-from-env')) {
			$password = getenv('NC_PASS') ?: getenv('OC_PASS');
			if (!$password) {
				$output->writeln('<error>--password-from-env given, but NC_PASS/OC_PASS is empty!</error>');
				return 1;
			}
		} elseif ($input->isInteractive()) {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');

			$question = new Question('Enter the account password: ');
			$question->setHidden(true);
			/** @var null|string $password */
			$password = $helper->ask($input, $output, $question);
		}

		if ($password === null) {
			$output->writeln('<info>No password provided. The generated app password will therefore have limited capabilities. Any operation that requires the login password will fail.</info>');
		}

		$token = $this->random->generate(72, ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
		$generatedToken = $this->tokenProvider->generateToken(
			$token,
			$user->getUID(),
			$user->getUID(),
			$password,
			'cli',
			IToken::PERMANENT_TOKEN,
			IToken::DO_NOT_REMEMBER
		);

		$this->eventDispatcher->dispatchTyped(
			new AppPasswordCreatedEvent($generatedToken)
		);

		$output->writeln('app password:');
		$output->writeln($token);

		return 0;
	}
}
