<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\User\AuthTokens;

use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\PublicKeyTokenMapper;
use OC\Core\Command\Base;
use OCP\Authentication\Token\IToken;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Revoke extends Base {
	public function __construct(
		protected IUserManager $userManager,
		protected IProvider $tokenProvider,
		protected PublicKeyTokenMapper $mapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('user:auth-tokens:revoke')
			->setDescription('Revoke authentication tokens by class/type')
			->addArgument(
				'uid',
				InputArgument::OPTIONAL,
				'ID of the user to revoke tokens for'
			)
			->addOption(
				'all-users',
				null,
				InputOption::VALUE_NONE,
				'Revoke tokens for all users'
			)
			->addOption(
				'browser-sessions',
				null,
				InputOption::VALUE_NONE,
				'Revoke browser session tokens (temporary, non-remembered)'
			)
			->addOption(
				'remembered-sessions',
				null,
				InputOption::VALUE_NONE,
				'Revoke remembered browser session tokens (temporary, remembered)'
			)
			->addOption(
				'non-app-passwords',
				null,
				InputOption::VALUE_NONE,
				'Revoke all tokens except permanent app passwords (includes session, remembered, one-time, and wipe tokens)'
			)
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'Revoke all tokens including app passwords'
			)
			->addOption(
				'dry-run',
				null,
				InputOption::VALUE_NONE,
				'Show which tokens would be revoked without deleting them'
			)
			->addOption(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Skip confirmation prompt'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');
		$allUsers = (bool)$input->getOption('all-users');

		$modes = [
			'browser-sessions' => (bool)$input->getOption('browser-sessions'),
			'remembered-sessions' => (bool)$input->getOption('remembered-sessions'),
			'non-app-passwords' => (bool)$input->getOption('non-app-passwords'),
			'all' => (bool)$input->getOption('all'),
		];

		$selectedModes = array_filter($modes);
		if (count($selectedModes) !== 1) {
			throw new RuntimeException('Specify exactly one of --browser-sessions, --remembered-sessions, --non-app-passwords, or --all.');
		}

		if ($allUsers && $uid !== null) {
			throw new RuntimeException('Do not provide <uid> together with --all-users.');
		}

		if (!$allUsers && (!is_string($uid) || $uid === '')) {
			throw new RuntimeException('Specify <uid> or use --all-users.');
		}

		$dryRun = (bool)$input->getOption('dry-run');
		$force = (bool)$input->getOption('force');

		// Confirm destructive operations unless --force or --dry-run is set
		if (!$dryRun && !$force && $input->isInteractive()) {
			$modeName = array_key_first($selectedModes);
			$scope = $allUsers ? 'ALL users' : "user '$uid'";
			$message = "<question>This will revoke all --{$modeName} tokens for {$scope}. Continue? [y/N]</question> ";

			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = new ConfirmationQuestion($message, false);
			if (!$helper->ask($input, $output, $question)) {
				$output->writeln('Aborted.');
				return Command::SUCCESS;
			}
		}

		$revoked = 0;

		if ($allUsers) {
			if (!$dryRun) {
				// Use bulk deletion for --all-users when possible to avoid
				// iterating every user and their tokens individually
				$bulkCount = $this->bulkRevoke($modes, $output);
				if ($bulkCount !== null) {
					$output->writeln("<info>Revoked {$bulkCount} token(s).</info>");
					return Command::SUCCESS;
				}
			}

			// Fall back to per-user iteration for dry-run (needs token details)
			// or if bulk deletion is not applicable
			$this->userManager->callForAllUsers(function (IUser $user) use ($output, $dryRun, $modes, &$revoked): void {
				$revoked += $this->revokeForUser($user->getUID(), $modes, $output, $dryRun);
			});
		} else {
			$user = $this->userManager->get($uid);
			if ($user === null) {
				$output->writeln('<error>user not found</error>');
				return Command::FAILURE;
			}
			$revoked = $this->revokeForUser($user->getUID(), $modes, $output, $dryRun);
		}

		if ($dryRun) {
			$output->writeln("<info>Dry run complete. {$revoked} token(s) would be revoked.</info>");
		} else {
			$output->writeln("<info>Revoked {$revoked} token(s).</info>");
		}

		return Command::SUCCESS;
	}

	/**
	 * Attempt a bulk DELETE query for --all-users instead of per-user iteration.
	 *
	 * @return int|null Number of deleted rows, or null if the mode doesn't
	 *                  support bulk deletion (caller should fall back to per-user)
	 */
	private function bulkRevoke(array $modes, OutputInterface $output): ?int {
		// Build type/remember constraints for a single bulk query
		if ($modes['browser-sessions']) {
			return $this->mapper->invalidateByTypeAndRemember(
				IToken::TEMPORARY_TOKEN,
				IToken::DO_NOT_REMEMBER
			);
		}

		if ($modes['remembered-sessions']) {
			return $this->mapper->invalidateByTypeAndRemember(
				IToken::TEMPORARY_TOKEN,
				IToken::REMEMBER
			);
		}

		if ($modes['non-app-passwords']) {
			return $this->mapper->invalidateAllExceptType(IToken::PERMANENT_TOKEN);
		}

		if ($modes['all']) {
			return $this->mapper->invalidateAllTokens();
		}

		return null;
	}

	private function revokeForUser(string $uid, array $modes, OutputInterface $output, bool $dryRun): int {
		$tokens = $this->tokenProvider->getTokenByUser($uid);
		$count = 0;

		foreach ($tokens as $token) {
			if (!$this->matchesSelection($token, $modes)) {
				continue;
			}

			$count++;

			if ($output->isVerbose()) {
				$output->writeln(sprintf(
					'%s token %d for user %s (type=%s remember=%s name=%s)',
					$dryRun ? 'Would revoke' : 'Revoking',
					$token->getId(),
					$uid,
					self::formatTokenType($token->getType()),
					(string)$token->getRemember(),
					$token->getName()
				));
			}

			if (!$dryRun) {
				$this->tokenProvider->invalidateTokenById($uid, $token->getId());
			}
		}

		return $count;
	}

	private function matchesSelection(IToken $token, array $modes): bool {
		if ($modes['all']) {
			return true;
		}

		$type = $token->getType();

		if ($modes['browser-sessions']) {
			return $type === IToken::TEMPORARY_TOKEN
				&& $token->getRemember() === IToken::DO_NOT_REMEMBER;
		}

		if ($modes['remembered-sessions']) {
			return $type === IToken::TEMPORARY_TOKEN
				&& $token->getRemember() === IToken::REMEMBER;
		}

		if ($modes['non-app-passwords']) {
			// Includes TEMPORARY_TOKEN, WIPE_TOKEN, and ONETIME_TOKEN
			return $type !== IToken::PERMANENT_TOKEN;
		}

		return false;
	}

	private static function formatTokenType(int $type): string {
		return match ($type) {
			IToken::TEMPORARY_TOKEN => 'temporary',
			IToken::PERMANENT_TOKEN => 'permanent',
			IToken::WIPE_TOKEN => 'wipe',
			IToken::ONETIME_TOKEN => 'onetime',
			default => (string)$type,
		};
	}
}
