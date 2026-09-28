<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\User\DeletedUsersIndex;
use OCP\IDateTimeFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowRemnants extends Command {
	public function __construct(
		protected DeletedUsersIndex $dui,
		protected IDateTimeFormatter $dateFormatter,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:show-remnants')
			->setDescription('shows which users are not available on LDAP anymore, but have remnants in Nextcloud.')
			->addOption('json', null, InputOption::VALUE_NONE, 'return JSON array instead of pretty table.')
			->addOption('short-date', null, InputOption::VALUE_NONE, 'show dates in Y-m-d format');
	}

	protected function formatDate(int $timestamp, string $default, bool $showShortDate): string {
		if (!($timestamp > 0)) {
			return $default;
		}
		if ($showShortDate) {
			return date('Y-m-d', $timestamp);
		}
		return $this->dateFormatter->formatDate($timestamp);
	}

	/**
	 * executes the command, i.e. creates and outputs a table of LDAP users marked as deleted
	 *
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var \Symfony\Component\Console\Helper\Table $table */
		$table = new Table($output);
		$table->setHeaders([
			'Nextcloud name', 'Display Name', 'LDAP UID', 'LDAP DN', 'Last Login',
			'Detected on', 'Dir', 'Sharer'
		]);
		$rows = [];
		$resultSet = $this->dui->getUsers();
		foreach ($resultSet as $user) {
			$rows[] = [
				'ocName' => $user->getOCName(),
				'displayName' => $user->getDisplayName(),
				'uid' => $user->getUID(),
				'dn' => $user->getDN(),
				'lastLogin' => $this->formatDate($user->getLastLogin(), '-', (bool)$input->getOption('short-date')),
				'detectedOn' => $this->formatDate($user->getDetectedOn(), 'unknown', (bool)$input->getOption('short-date')),
				'homePath' => $user->getHomePath(),
				'sharer' => $user->getHasActiveShares() ? 'Y' : 'N',
			];
		}

		if ($input->getOption('json')) {
			$output->writeln(json_encode($rows));
		} else {
			$table->setRows($rows);
			$table->render();
		}
		return self::SUCCESS;
	}
}
