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
	private const SECONDS_BEFORE_REMINDER = 86400;
	private const CHUNK_SIZE = 1000;

	public function __construct(
		ITimeFactory                     $time,
		private readonly IDBConnection   $db,
		private readonly IManager        $shareManager,
		private readonly IUserManager    $userManager,
		private readonly LoggerInterface $logger,
		private readonly IURLGenerator   $urlGenerator,
		private readonly IFactory        $l10nFactory,
		private readonly IMailer         $mailer,
		private readonly Defaults        $defaults,
	) {
		parent::__construct($time);
		$this->setInterval(3600);
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
					$qb->expr()->gte('s.expiration', $qb->createNamedParameter($minDate->format('Y-m-d H:i:s'))),
					$qb->expr()->lt('s.expiration', $qb->createNamedParameter($maxDate->format('Y-m-d H:i:s'))),
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

		$sharesResult = $qb->executeQuery();
		while ($share = $sharesResult->fetch()) {
			if ((int)$share['share_type'] === IShare::TYPE_EMAIL) {
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
		$sharesResult->closeCursor();
	}

	/**
	 * Retrieves and returns all the necessary data before sending a reminder.
	 * It also updates the reminder sent flag for the affected shares (to avoid multiple reminders).
	 *
	 * @param IShare $share Share that was obtained with {@link getShares}
	 * @return array|null Info needed to send a reminder
	 */
	private function prepareReminder(IShare $share): array|null {
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
