<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\Constants;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Defaults;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * Send a reminder via email to the sharee(s) if the folder is still empty a predefined time before the expiration date
 */
class SharesReminderJob extends TimedJob {
	private const SECONDS_BEFORE_REMINDER = 24 * 60 * 60;
	private const CHUNK_SIZE = 1000;
	private int $folderMimeTypeId;

	public function __construct(
		ITimeFactory $time,
		private readonly IDBConnection $db,
		private readonly IManager $shareManager,
		private readonly IUserManager $userManager,
		private readonly LoggerInterface $logger,
		private readonly IURLGenerator $urlGenerator,
		private readonly IFactory $l10nFactory,
		private readonly IMailer $mailer,
		private readonly Defaults $defaults,
		IMimeTypeLoader $mimeTypeLoader,
	) {
		parent::__construct($time);
		$this->setInterval(60 * 60);
		$this->folderMimeTypeId = $mimeTypeLoader->getId(ICacheEntry::DIRECTORY_MIMETYPE);
	}


	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 * @throws Exception if a database error occurs
	 */
	public function run(mixed $argument): void {
		foreach ($this->getShares() as $share) {
			$reminderInfo = $this->prepareReminder($share);
			if ($reminderInfo !== null) {
				$this->sendReminder($reminderInfo);
			}
		}
	}

	/**
	 * Finds all shares of empty folders, for which the user has write permissions.
	 * The returned shares are of type user or email only, have expiration dates within the specified time frame
	 * and have not yet received a reminder.
	 *
	 * @return array<IShare>|\Iterator
	 * @throws Exception if a database error occurs
	 */
	private function getShares(): array|\Iterator {
		if ($this->db->getShardDefinition('filecache')) {
			$sharesResult = $this->getSharesDataSharded();
		} else {
			$sharesResult = $this->getSharesData();
		}
		foreach ($sharesResult as $share) {
			if ($share['share_type'] === IShare::TYPE_EMAIL) {
				$id = "ocMailShare:$share[id]";
			} else {
				$id = "ocinternal:$share[id]";
			}

			try {
				yield $this->shareManager->getShareById($id);
			} catch (ShareNotFound) {
				$this->logger->error("Share with ID $id not found.");
			}
		}
	}

	/**
	 * @return list<array{id: int, share_type: int}>
	 */
	private function getSharesData(): array {
		$minDate = new \DateTime();
		$maxDate = new \DateTime();
		$maxDate->setTimestamp($maxDate->getTimestamp() + self::SECONDS_BEFORE_REMINDER);

		$qb = $this->db->getQueryBuilder();
		$qb->select('s.id', 's.share_type')
			->from('share', 's')
			->leftJoin('s', 'filecache', 'f', $qb->expr()->eq('f.parent', 's.file_source'))
			->where(
				$qb->expr()->andX(
					$qb->expr()->orX(
						$qb->expr()->eq('s.share_type', $qb->expr()->literal(IShare::TYPE_USER)),
						$qb->expr()->eq('s.share_type', $qb->expr()->literal(IShare::TYPE_EMAIL))
					),
					$qb->expr()->eq('s.item_type', $qb->expr()->literal('folder')),
					$qb->expr()->gte('s.expiration', $qb->createNamedParameter($minDate, IQueryBuilder::PARAM_DATE)),
					$qb->expr()->lte('s.expiration', $qb->createNamedParameter($maxDate, IQueryBuilder::PARAM_DATE)),
					$qb->expr()->eq('s.reminder_sent', $qb->createNamedParameter(
						false, IQueryBuilder::PARAM_BOOL
					)),
					$qb->expr()->eq(
						$qb->expr()->bitwiseAnd('s.permissions', Constants::PERMISSION_CREATE),
						$qb->createNamedParameter(Constants::PERMISSION_CREATE, IQueryBuilder::PARAM_INT)
					),
					$qb->expr()->isNull('f.fileid')
				)
			)
			->setMaxResults(SharesReminderJob::CHUNK_SIZE);

		$shares = $qb->executeQuery()->fetchAll();
		return array_values(array_map(fn ($share): array => [
			'id' => (int)$share['id'],
			'share_type' => (int)$share['share_type'],
		], $shares));
	}

	/**
	 * Sharding compatible version of getSharesData
	 *
	 * @return list<array{id: int, share_type: int, file_source: int}>
	 */
	private function getSharesDataSharded(): array|\Iterator {
		$minDate = new \DateTime();
		$maxDate = new \DateTime();
		$maxDate->setTimestamp($maxDate->getTimestamp() + self::SECONDS_BEFORE_REMINDER);

		$qb = $this->db->getQueryBuilder();
		$qb->select('s.id', 's.share_type', 's.file_source')
			->from('share', 's')
			->where(
				$qb->expr()->andX(
					$qb->expr()->orX(
						$qb->expr()->eq('s.share_type', $qb->expr()->literal(IShare::TYPE_USER)),
						$qb->expr()->eq('s.share_type', $qb->expr()->literal(IShare::TYPE_EMAIL))
					),
					$qb->expr()->eq('s.item_type', $qb->expr()->literal('folder')),
					$qb->expr()->gte('s.expiration', $qb->createNamedParameter($minDate, IQueryBuilder::PARAM_DATE)),
					$qb->expr()->lte('s.expiration', $qb->createNamedParameter($maxDate, IQueryBuilder::PARAM_DATE)),
					$qb->expr()->eq('s.reminder_sent', $qb->createNamedParameter(
						false, IQueryBuilder::PARAM_BOOL
					)),
					$qb->expr()->eq(
						$qb->expr()->bitwiseAnd('s.permissions', Constants::PERMISSION_CREATE),
						$qb->createNamedParameter(Constants::PERMISSION_CREATE, IQueryBuilder::PARAM_INT)
					),
				)
			);

		$shares = $qb->executeQuery()->fetchAll();
		$shares = array_values(array_map(fn ($share): array => [
			'id' => (int)$share['id'],
			'share_type' => (int)$share['share_type'],
			'file_source' => (int)$share['file_source'],
		], $shares));
		return $this->filterSharesWithEmptyFolders($shares, self::CHUNK_SIZE);
	}

	/**
	 * Check which of the supplied file ids is an empty folder until there are `$maxResults` folders
	 * @param list<array{id: int, share_type: int, file_source: int}> $shares
	 * @return list<array{id: int, share_type: int, file_source: int}>
	 */
	private function filterSharesWithEmptyFolders(array $shares, int $maxResults): array {
		$query = $this->db->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->where($query->expr()->eq('size', $query->createNamedParameter(0), IQueryBuilder::PARAM_INT_ARRAY))
			->andWhere($query->expr()->eq('mimetype', $query->createNamedParameter($this->folderMimeTypeId, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->in('fileid', $query->createParameter('fileids')));
		$chunks = array_chunk($shares, SharesReminderJob::CHUNK_SIZE);
		$results = [];
		foreach ($chunks as $chunk) {
			$chunkFileIds = array_map(fn ($share): int => $share['file_source'], $chunk);
			$chunkByFileId = array_combine($chunkFileIds, $chunk);
			$query->setParameter('fileids', $chunkFileIds, IQueryBuilder::PARAM_INT_ARRAY);
			$chunkResults = $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
			foreach ($chunkResults as $folderId) {
				$results[] = $chunkByFileId[$folderId];
			}
			if (count($results) >= $maxResults) {
				break;
			}
		}
		return $results;
	}

	/**
	 * Retrieves and returns all the necessary data before sending a reminder.
	 * It also updates the reminder sent flag for the affected shares (to avoid multiple reminders).
	 *
	 * @param IShare $share Share that was obtained with {@link getShares}
	 * @return array|null Info needed to send a reminder
	 */
	private function prepareReminder(IShare $share): ?array {
		$sharedWith = $share->getSharedWith();
		$reminderInfo = [];
		if ($share->getShareType() == IShare::TYPE_USER) {
			$user = $this->userManager->get($sharedWith);
			if ($user === null) {
				return null;
			}
			$reminderInfo['email'] = $user->getEMailAddress();
			$reminderInfo['userLang'] = $this->l10nFactory->getUserLanguage($user);
			$reminderInfo['folderLink'] = $this->urlGenerator->linkToRouteAbsolute('files.view.index', [
				'dir' => $share->getTarget()
			]);
		} else {
			$reminderInfo['email'] = $sharedWith;
			$reminderInfo['folderLink'] = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', [
				'token' => $share->getToken()
			]);
		}
		if (empty($reminderInfo['email'])) {
			return null;
		}

		try {
			$reminderInfo['folderName'] = $share->getNode()->getName();
		} catch (NotFoundException) {
			$id = $share->getFullId();
			$this->logger->error("File by share ID $id not found.");
		}
		$share->setReminderSent(true);
		$this->shareManager->updateShare($share);
		return $reminderInfo;
	}

	/**
	 * This method accepts data obtained by {@link prepareReminder} and sends reminder email.
	 *
	 * @param array $reminderInfo
	 * @return void
	 */
	private function sendReminder(array $reminderInfo): void {
		$instanceName = $this->defaults->getName();
		$from = [Util::getDefaultEmailAddress($instanceName) => $instanceName];
		$l = $this->l10nFactory->get('files_sharing', $reminderInfo['userLang'] ?? null);
		$emailTemplate = $this->generateEMailTemplate($l, [
			'link' => $reminderInfo['folderLink'], 'name' => $reminderInfo['folderName']
		]);

		$message = $this->mailer->createMessage();
		$message->setFrom($from);
		$message->setTo([$reminderInfo['email']]);
		$message->useTemplate($emailTemplate);
		$errorText = "Sending email with share reminder to $reminderInfo[email] failed.";
		try {
			$failedRecipients = $this->mailer->send($message);
			if (count($failedRecipients) > 0) {
				$this->logger->error($errorText);
			}
		} catch (\Exception) {
			$this->logger->error($errorText);
		}
	}

	/**
	 * Returns the reminder email template
	 *
	 * @param IL10N $l
	 * @param array $folder Folder the user should be reminded of
	 * @return IEMailTemplate
	 */
	private function generateEMailTemplate(IL10N $l, array $folder): IEMailTemplate {
		$emailTemplate = $this->mailer->createEMailTemplate('files_sharing.SharesReminder', [
			'folder' => $folder,
		]);
		$emailTemplate->addHeader();
		$emailTemplate->setSubject(
			$l->t('Remember to upload the files to %s', [$folder['name']])
		);
		$emailTemplate->addBodyText($l->t(
			'We would like to kindly remind you that you have not yet uploaded any files to the shared folder.'
		));
		$emailTemplate->addBodyButton(
			$l->t('Open "%s"', [$folder['name']]),
			$folder['link']
		);
		$emailTemplate->addFooter();
		return $emailTemplate;
	}
}
