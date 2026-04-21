<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\UserMigration;

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
				$userFolder = $this->rootFolder->getUserFolder($user->getUID());
				$shareNode = $this->rootFolder->get($shareData['path']);

				$share = $this->shareFromArray($shareData, $shareNode);
			} catch (NotFoundException $exception) {
				$output->writeln('Unable to import share with path ' . $shareData['path'] . '. Path doesn\'t exist');
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
		return [
			'shareType' => $share->getShareType(),
			'path' => $share->getNode()->getPath(),
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

	private function shareFromArray(array $shareData, Node $node): IShare {
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
			$expireDate = new \DateTime();
			$expireDate->setTimestamp($shareData['passwordExpireDate']);
			$share->setPasswordExpirationTime($expireDate);
			$share->setSendPasswordByTalk($shareData['sendPasswordByTalk']);
		}

		$share = $this->shareManager->createShare($share);
		// Set the token after the share is created because a new one
		// is generated whether there is an existing token or not
		$share = $share->setToken($shareData['token']);
		$share = $this->shareManager->updateShare($share);

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
