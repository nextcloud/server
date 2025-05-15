<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\UserMigration;

use InvalidArgumentException;
use OC\Accounts\TAccountsHelper;
use OC\Core\Db\ProfileConfigMapper;
use OC\NotSquareException;
use OC\Profile\ProfileManager;
use OCA\Settings\AppInfo\Application;
use OCP\Accounts\IAccountManager;
use OCP\IAvatarManager;
use OCP\IL10N;
use OCP\Image;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\ISizeEstimationMigrator;
use OCP\UserMigration\TMigratorBasicVersionHandling;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class AccountMigrator implements IMigrator, ISizeEstimationMigrator {
	use TMigratorBasicVersionHandling;

	use TAccountsHelper;

	private ProfileManager $profileManager;

	private ProfileConfigMapper $configMapper;

	private const PATH_ROOT = Application::APP_ID . '/';

	private const PATH_ACCOUNT_FILE = AccountMigrator::PATH_ROOT . 'account.json';

	private const AVATAR_BASENAME = 'avatar';

	private const PATH_CONFIG_FILE = AccountMigrator::PATH_ROOT . 'config.json';

	public function __construct(
		private IAccountManager $accountManager,
		private IAvatarManager $avatarManager,
		ProfileManager $profileManager,
		ProfileConfigMapper $configMapper,
		private IL10N $l10n,
	) {
		$this->profileManager = $profileManager;
		$this->configMapper = $configMapper;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEstimatedExportSize(IUser $user): int|float {
		$size = 100; // 100KiB for account JSON

		try {
			$avatar = $this->avatarManager->getAvatar($user->getUID());
			if ($avatar->isCustomAvatar()) {
				$avatarFile = $avatar->getFile(-1);
				$size += $avatarFile->getSize() / 1024;
			}
		} catch (Throwable $e) {
			// Skip avatar in size estimate on failure
		}

		return ceil($size);
	}

	/**
	 * {@inheritDoc}
	 */
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln('Exporting account information in ' . AccountMigrator::PATH_ACCOUNT_FILE . '…');

		try {
			$account = $this->accountManager->getAccount($user);
			$exportDestination->addFileContents(AccountMigrator::PATH_ACCOUNT_FILE, json_encode($account));
		} catch (Throwable $e) {
			throw new AccountMigratorException('Could not export account information', 0, $e);
		}

		try {
			$avatar = $this->avatarManager->getAvatar($user->getUID());
			if ($avatar->isCustomAvatar()) {
				$avatarFile = $avatar->getFile(-1);
				$exportPath = AccountMigrator::PATH_ROOT . AccountMigrator::AVATAR_BASENAME . '.' . $avatarFile->getExtension();

				$output->writeln('Exporting avatar to ' . $exportPath . '…');
				$exportDestination->addFileAsStream($exportPath, $avatarFile->read());
			}
		} catch (Throwable $e) {
			throw new AccountMigratorException('Could not export avatar', 0, $e);
		}

		try {
			$output->writeln('Exporting profile config in ' . AccountMigrator::PATH_CONFIG_FILE . '…');
			$config = $this->profileManager->getProfileConfig($user, $user);
			$exportDestination->addFileContents(AccountMigrator::PATH_CONFIG_FILE, json_encode($config));
		} catch (Throwable $e) {
			throw new AccountMigratorException('Could not export profile config', 0, $e);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		if ($importSource->getMigratorVersion($this->getId()) === null) {
			$output->writeln('No version for ' . static::class . ', skipping import…');
			return;
		}

		$output->writeln('Importing account information from ' . AccountMigrator::PATH_ACCOUNT_FILE . '…');

		$account = $this->accountManager->getAccount($user);

		/** @var array<string, array<string, string>>|array<string, array<int, array<string, string>>> $data */
		$data = json_decode($importSource->getFileContents(AccountMigrator::PATH_ACCOUNT_FILE), true, 512, JSON_THROW_ON_ERROR);
		$account->setAllPropertiesFromJson($data);

		try {
			$this->accountManager->updateAccount($account);
		} catch (InvalidArgumentException $e) {
			throw new AccountMigratorException('Failed to import account information');
		}

		/** @var array<int, string> $avatarFiles */
		$avatarFiles = array_filter(
			$importSource->getFolderListing(AccountMigrator::PATH_ROOT),
			fn (string $filename) => pathinfo($filename, PATHINFO_FILENAME) === AccountMigrator::AVATAR_BASENAME,
		);

		if (!empty($avatarFiles)) {
			if (count($avatarFiles) > 1) {
				$output->writeln('Expected single avatar image file, using first file found');
			}

			$importPath = AccountMigrator::PATH_ROOT . reset($avatarFiles);

			$output->writeln('Importing avatar from ' . $importPath . '…');
			$stream = $importSource->getFileAsStream($importPath);
			$image = new Image();
			$image->loadFromFileHandle($stream);

			try {
				$avatar = $this->avatarManager->getAvatar($user->getUID());
				$avatar->set($image);
			} catch (NotSquareException $e) {
				throw new AccountMigratorException('Avatar image must be square');
			} catch (Throwable $e) {
				throw new AccountMigratorException('Failed to import avatar', 0, $e);
			}
		}

		try {
			$output->writeln('Importing profile config from ' . AccountMigrator::PATH_CONFIG_FILE . '…');
			/** @var array $configData */
			$configData = json_decode($importSource->getFileContents(AccountMigrator::PATH_CONFIG_FILE), true, 512, JSON_THROW_ON_ERROR);
			// Ensure that a profile config entry exists in the database
			$this->profileManager->getProfileConfig($user, $user);
			$config = $this->configMapper->get($user->getUID());
			$config->setConfigArray($configData);
			$this->configMapper->update($config);
		} catch (Throwable $e) {
			throw new AccountMigratorException('Failed to import profile config');
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getId(): string {
		return 'account';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Profile information');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDescription(): string {
		return $this->l10n->t('Profile picture, full name, email, phone number, address, website, Twitter, organisation, role, headline, biography, and whether your profile is enabled');
	}
}
