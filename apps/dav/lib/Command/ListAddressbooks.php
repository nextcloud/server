<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Command;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\SystemAddressbook;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListAddressbooks extends Command {
	public function __construct(
		protected IUserManager $userManager,
		private CardDavBackend $cardDavBackend,
	) {
		parent::__construct('dav:list-addressbooks');
	}

	protected function configure(): void {
		$this
			->setDescription('List all addressbooks of a user')
			->addArgument('uid',
				InputArgument::REQUIRED,
				'User for whom all addressbooks will be listed');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = $input->getArgument('uid');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User <$user> is unknown.");
		}

		$addressBooks = $this->cardDavBackend->getAddressBooksForUser("principals/users/$user");

		$addressBookTableData = [];
		foreach ($addressBooks as $book) {
			// skip system / contacts integration address book
			if ($book['uri'] === SystemAddressbook::URI_SHARED) {
				continue;
			}

			$readOnly = false;
			$readOnlyIndex = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only';
			if (isset($book[$readOnlyIndex])) {
				$readOnly = $book[$readOnlyIndex];
			}

			$addressBookTableData[] = [
				$book['uri'],
				$book['{DAV:}displayname'],
				$book['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal'] ?? $book['principaluri'],
				$book['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}owner-displayname'],
				$readOnly ? ' x ' : ' ✓ ',
			];
		}

		if (count($addressBookTableData) > 0) {
			$table = new Table($output);
			$table->setHeaders(['Database ID', 'URI', 'Displayname', 'Owner principal', 'Owner displayname', 'Writable'])
				->setRows($addressBookTableData);

			$table->render();
		} else {
			$output->writeln("<info>User <$user> has no addressbooks</info>");
		}
		return self::SUCCESS;
	}
}
