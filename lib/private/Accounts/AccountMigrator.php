<?php

declare(strict_types=1);

/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

namespace OC\Accounts;

use InvalidArgumentException;
use OCP\Accounts\IAccountManager;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\TMigratorBasicVersionHandling;
use Symfony\Component\Console\Output\OutputInterface;

class AccountMigrator implements IMigrator {

	use TMigratorBasicVersionHandling;

	use TAccountsHelper;

	private IAccountManager $accountManager;

	private const EXPORT_FILE = 'account.json';

	public function __construct(
		IAccountManager $accountManager
	) {
		$this->accountManager = $accountManager;
	}

	/**
	 * {@inheritDoc}
	 */
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln('Exporting account information in ' . AccountMigrator::EXPORT_FILE . '…');

		if ($exportDestination->addFileContents(AccountMigrator::EXPORT_FILE, json_encode($this->accountManager->getAccount($user))) === false) {
			throw new AccountMigratorException('Could not export account information');
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		if ($importSource->getMigratorVersion(static::class) === null) {
			$output->writeln('No version for ' . static::class . ', skipping import…');
			return;
		}

		$output->writeln('Importing account information from ' . AccountMigrator::EXPORT_FILE . '…');

		$account = $this->accountManager->getAccount($user);

		/** @var array<string, array> $data */
		$data = json_decode($importSource->getFileContents(AccountMigrator::EXPORT_FILE), true, 512, JSON_THROW_ON_ERROR);

		foreach ($data as $propertyName => $propertyData) {
			if ($this->isCollection($propertyName)) {
				$collection = new AccountPropertyCollection($propertyName);
				/** @var array<int, array{name: string, value: string, scope: string, verified: string, verificationData: string}> $collectionData */
				$collectionData = $propertyData[$propertyName];
				foreach ($collectionData as ['value' => $value, 'scope' => $scope, 'verified' => $verified, 'verificationData' => $verificationData]) {
					$collection->addProperty(new AccountProperty($collection->getName(), $value, $scope, $verified, $verificationData));
				}
				$account->setPropertyCollection($collection);
			} else {
				/** @var array{name: string, value: string, scope: string, verified: string, verificationData: string} $propertyData */
				['value' => $value, 'scope' => $scope, 'verified' => $verified, 'verificationData' => $verificationData] = $propertyData;
				$account->setProperty($propertyName, $value, $scope, $verified, $verificationData);
			}
		}

		try {
			$this->accountManager->updateAccount($account);
		} catch (InvalidArgumentException $e) {
			throw new AccountMigratorException('Failed to import account information');
		}
	}
}
