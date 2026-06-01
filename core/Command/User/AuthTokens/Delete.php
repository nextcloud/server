<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\User\AuthTokens;

use DateTimeImmutable;
use OC\Authentication\Token\IProvider;
use OC\Core\Command\Base;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\Authentication\Exceptions\WipeTokenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Base {
	public function __construct(
		protected IProvider $tokenProvider,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('user:auth-tokens:delete')
			->setDescription('Deletes an authentication token')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'ID of the user to delete tokens for'
			)
			->addArgument(
				'id',
				InputArgument::OPTIONAL,
				'ID of the auth token to delete'
			)
			->addOption(
				'last-used-before',
				null,
				InputOption::VALUE_REQUIRED,
				'Delete tokens last used before a given date.'
			)
			->addOption(
				'cancel-wipe',
				null,
				InputOption::VALUE_NONE,
				'Allow deleting a token that is marked for remote wipe. The pending wipe will not run.'
			);
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');
		$id = (int)$input->getArgument('id');
		$before = $input->getOption('last-used-before');
		$cancelWipe = (bool)$input->getOption('cancel-wipe');

		if ($before) {
			if ($id) {
				throw new RuntimeException('Option --last-used-before cannot be used with [<id>]');
			}

			return $this->deleteLastUsedBefore($uid, $before);
		}

		if (!$id) {
			throw new RuntimeException('Not enough arguments. Specify the token <id> or use the --last-used-before option.');
		}

		if (!$cancelWipe) {
			try {
				$this->tokenProvider->getTokenById($id);
			} catch (WipeTokenException $e) {
				$output->writeln('<error>Token ' . $id . ' is marked for remote wipe. Pass --cancel-wipe to delete it anyway; the pending wipe will not run.</error>');
				return Command::FAILURE;
			} catch (InvalidTokenException $e) {
				// Token doesn't exist, has expired, or is otherwise unusable.
				// Defer to invalidateTokenById, which handles the no-op case.
			}
		}

		return $this->deleteById($uid, $id);
	}

	protected function deleteById(string $uid, int $id): int {
		$this->tokenProvider->invalidateTokenById($uid, $id);

		return Command::SUCCESS;
	}

	protected function deleteLastUsedBefore(string $uid, string $before): int {
		$date = $this->parseDateOption($before);
		if (!$date) {
			throw new RuntimeException('Invalid date format. Acceptable formats are: ISO8601 (w/o fractions), "YYYY-MM-DD" and Unix time in seconds.');
		}

		$this->tokenProvider->invalidateLastUsedBefore($uid, $date->getTimestamp());

		return Command::SUCCESS;
	}

	/**
	 * @return \DateTimeImmutable|false
	 */
	protected function parseDateOption(string $input) {
		$date = false;

		// Handle Unix timestamp
		if (filter_var($input, FILTER_VALIDATE_INT)) {
			return new DateTimeImmutable('@' . $input);
		}

		// ISO8601
		$date = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $input);
		if ($date) {
			return $date;
		}

		// YYYY-MM-DD
		return DateTimeImmutable::createFromFormat('!Y-m-d', $input);
	}
}
