<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\UserMigration;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Security\IHasher;
use OC\Share20\Share;
use OC\Share20\ShareAttributes;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\IUser;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\ISizeEstimationMigrator;
use OCP\UserMigration\TMigratorBasicVersionHandling;
use Symfony\Component\Console\Output\OutputInterface;

class SharesMigrator implements IMigrator, ISizeEstimationMigrator {
	use TMigratorBasicVersionHandling;

	protected const PATH_SHARES_FILE = Application::APP_ID . '/shares.json';

	public function __construct(
		protected IL10N $l10n,
		protected IManager $shareManager,
		protected IRootFolder $rootFolder,
		protected IDBConnection $connection,
		protected IHasher $hasher,
	) {

	}

	/**
	 * {@inheritDoc}
	 */
	public function getEstimatedExportSize(IUser $user): int|float {
		$shares = $this->getShares($user->getUID());
		return ceil(strlen(json_encode($shares)));
	}

	/**
	 * {@inheritDoc}
	 */
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$shares = $this->getShares($user->getUID());
		$exportDestination->addFileContents(static::PATH_SHARES_FILE, json_encode($shares));
	}

	/**
	 * {@inheritDoc}
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		if ($importSource->getMigratorVersion($this->getId()) === null) {
			$output->writeln('No version for ' . static::class . ', skipping import…');
			return;
		}

		if (!$importSource->pathExists(static::PATH_SHARES_FILE)) {
			$output->writeln('No shares to import');
			return;
		}

		$importedShares = json_decode($importSource->getFileContents(static::PATH_SHARES_FILE), true);

		foreach ($importedShares as $shareData) {
			if (!$this->shareManager->shareProviderExists($shareData['shareType'])) {
				continue;
			}

			try {
				$share = $this->shareFromArray($shareData);

				$qb = $this->connection->getQueryBuilder();
				$qb->update('share')
					->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())));

				// Update the password and token
				// The password is already hashed so directly updating the password is required
				if (isset($shareData['password']) && $this->hasher->validate($shareData['password'])) {
					$qb->set('password', $qb->createNamedParameter($shareData['password'], IQueryBuilder::PARAM_STR));
				}

				// Set the token after the share is created because a new one
				// is generated whether there is an existing token or not
				if (isset($shareData['token'])) {
					$qb->set('token', $qb->createNamedParameter($shareData['token'], IQueryBuilder::PARAM_STR));
				}
				$qb->executeStatement();
			} catch (NotFoundException $exception) {
				$output->writeln('Unable to import share with path ' . $shareData['path'] . '. Path doesn\'t exist');
			} catch (\Exception $exception) {
				$output->writeln('Unable to import share with path ' . $shareData['path'] . '. An unexpected error occurred');
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getId(): string {
		return 'sharing';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Shares');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDescription(): string {
		return $this->l10n->t('Files you have shared with others. This includes your shares with other people, groups, circles and public shares.');
	}

	private function shareToArray(IShare $share): array {
		$ownerFolder = $this->rootFolder->getUserFolder($share->getShareOwner());

		return [
			'shareType' => $share->getShareType(),
			'path' => $ownerFolder->getRelativePath($share->getNode()->getPath()),
			'sharedWith' => $share->getSharedWith(),
			'sharedBy' => $share->getSharedBy(),
			'shareOwner' => $share->getShareOwner(),
			'permissions' => $share->getPermissions(),
			'attributes' => $share->getAttributes()?->toArray(),
			'status' => $share->getStatus(),
			'note' => $share->getNote(),
			'expireDate' => $share->getExpirationDate()?->getTimestamp(),
			'password' => $share->getPassword(),
			'passwordExpirationTime' => $share->getPasswordExpirationTime()?->getTimestamp(),
			'sendPasswordByTalk' => $share->getSendPasswordByTalk(),
			'token' => $share->getToken(),
			'target' => $share->getTarget(),
			'shareTime' => $share->getShareTime()?->getTimestamp(),
			'mailSend' => $share->getMailSend(),
			'hideDownload' => $share->getHideDownload(),
			'reminderSent' => $share->getReminderSent(),
		];
	}

	private function shareFromArray(array $shareData): IShare {
		$userFolder = $this->rootFolder->getUserFolder($shareData['shareOwner']);
		$node = $userFolder->get($shareData['path']);

		$shareTime = new \DateTime();
		$shareTime->setTimestamp($shareData['shareTime']);
		$share = $this->shareManager->newShare();
		$share->setShareType($shareData['shareType'])
			->setNode($node)
			->setSharedBy($shareData['sharedBy'])
			->setShareOwner($shareData['shareOwner'])
			->setPermissions($shareData['permissions'])
			->setStatus($shareData['status'])
			->setNote($shareData['note'])
			->setTarget($shareData['target'])
			->setShareTime($shareTime)
			->setMailSend($shareData['mailSend'])
			->setHideDownload($shareData['hideDownload'])
			->setReminderSent($shareData['reminderSent']);

		if ($shareData['shareType'] !== IShare::TYPE_LINK) {
			$share->setSharedWith($shareData['sharedWith']);
		}

		if (isset($shareData['expireDate'])) {
			$expireDate = new \DateTime();
			$expireDate->setTimestamp($shareData['expireDate']);
			$share->setExpirationDate($expireDate);
		}

		if (isset($shareData['attributes']) && $shareData['attributes'] !== []) {
			$attributes = new ShareAttributes();
			foreach ($shareData['attributes'] as $attribute) {
				if (!isset($attribute['scope']) && !isset($attribute['key']) && !isset($attribute['value'])) {
					continue;
				}

				$attributes->setAttribute($attribute['scope'], $attribute['key'], $attribute['value']);
			}

			$share->setAttributes($attributes);
		}

		if (isset($shareData['password'])) {
			$share->setPassword($shareData['password']);

			if (isset($shareData['passwordExpirationTime'])) {
				$passwordExpirationTime = new \DateTime();
				$passwordExpirationTime->setTimestamp($shareData['passwordExpirationTime']);
				$share->setPasswordExpirationTime($passwordExpirationTime);
			}
			$share->setSendPasswordByTalk($shareData['sendPasswordByTalk']);
		}

		$share = $this->shareManager->createShare($share);

		return $share;
	}

	private function getShares(string $userId): array {
		$providers = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_LINK,
			IShare::TYPE_EMAIL,
			IShare::TYPE_CIRCLE,
			IShare::TYPE_ROOM,
			IShare::TYPE_DECK,
		];

		/** @var IShare[] $shares */
		$shares = [];

		foreach ($providers as $provider) {
			if (!$this->shareManager->shareProviderExists($provider)) {
				continue;
			}

			$providerShares = $this->shareManager->getSharesBy($userId, $provider, null, true, -1);
			$shares = array_merge($shares, $providerShares);
		}

		if ($this->shareManager->outgoingServer2ServerSharesAllowed()) {
			$federatedShares = $this->shareManager->getSharesBy($userId, IShare::TYPE_REMOTE, null, true, -1);
			$shares = array_merge($shares, $federatedShares);
		}

		if ($this->shareManager->outgoingServer2ServerGroupSharesAllowed()) {
			$federatedShares = $this->shareManager->getSharesBy($userId, IShare::TYPE_REMOTE_GROUP, null, true, -1);
			$shares = array_merge($shares, $federatedShares);
		}

		return array_map(fn (IShare $share) => $this->shareToArray($share), $shares);
	}
}
