<?php
/**
 * @copyright Copyright (c) 2023 Lucas Azevedo <lhs_azevedo@hotmail.com>
 *
 * @author Lucas Azevedo <lhs_azevedo@hotmail.com>
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
namespace OC\Core\Command\User\AuthTokens;

use DateTimeImmutable;
use OC\Authentication\Token\IProvider;
use OC\Core\Command\Base;
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
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');
		$id = (int) $input->getArgument('id');
		$before = $input->getOption('last-used-before');

		if ($before) {
			if ($id) {
				throw new RuntimeException('Option --last-used-before cannot be used with [<id>]');
			}

			return $this->deleteLastUsedBefore($uid, $before);
		}

		if (!$id) {
			throw new RuntimeException('Not enough arguments. Specify the token <id> or use the --last-used-before option.');
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
