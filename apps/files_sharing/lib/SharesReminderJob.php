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
		$shares = $this->getShares();
		[$foldersByEmail, $langByEmail] = $this->prepareReminders($shares);
		$this->sendReminders($foldersByEmail, $langByEmail);
	}

	/**
	 * Finds all folder shares of type user or email with expiration dates within the specified timeframe.
	 * This method returns only those shares that have not yet received the reminder.
	 *
	 * @return array<IShare>
	 * @throws Exception if a database error occurs
	 */
	private function getShares(): array {
		$minDate = new \DateTime();
		$maxDate = new \DateTime();
		$maxDate->setTimestamp($maxDate->getTimestamp() + self::SECONDS_BEFORE_REMINDER);

		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'share_type')
			->from('share')
			->where(
				$qb->expr()->andX(
					$qb->expr()->orX(
						$qb->expr()->eq('share_type', $qb->expr()->literal(IShare::TYPE_USER)),
						$qb->expr()->eq('share_type', $qb->expr()->literal(IShare::TYPE_EMAIL))
					),
					$qb->expr()->eq('item_type', $qb->expr()->literal('folder')),
					$qb->expr()->gte('expiration', $qb->createNamedParameter($minDate->format('Y-m-d H:i:s'))),
					$qb->expr()->lt('expiration', $qb->createNamedParameter($maxDate->format('Y-m-d H:i:s'))),
					$qb->expr()->eq('reminder_sent', $qb->createNamedParameter(
						false, IQueryBuilder::PARAM_BOOL
					))
				)
			);

		$sharesResult = $qb->executeQuery();
		$shares = [];
		while ($share = $sharesResult->fetch()) {
			if ((int)$share['share_type'] === IShare::TYPE_EMAIL) {
				$id = "ocMailShare:$share[id]";
			} else {
				$id = "ocinternal:$share[id]";
			}

			try {
				$shares[] = $this->shareManager->getShareById($id);
			} catch (ShareNotFound) {
				$this->logger->error("Share with ID $id not found.");
			}
		}
		$sharesResult->closeCursor();
		return $shares;
	}

	/**
	 * Checks if the user should be reminded about this share.
	 * If so, it will retrieve and return all the necessary data for this.
	 * It also updates the reminder sent flag for the affected shares (to avoid multiple reminders).
	 *
	 * @param array<IShare> $shares Shares that were obtained with {@link getShares}
	 * @return array<array> A tuple consisting of two dictionaries: folders and languages by email
	 * @throws Exception if the reminder sent flag could not be saved
	 */
	private function prepareReminders(array $shares): array {
		// This dictionary stores email addresses as keys and folder lists as values.
		// It is used to ensure that each user receives no more than one email notification.
		// The email will include the names and links of the folders that the user should be reminded of.
		$foldersByEmail = [];
		// Similar to the previous one, this variable stores the language for each email (if provided)
		$langByEmail = [];

		/** @var IShare $share */
		foreach ($shares as $share) {
			if (!$this->shouldRemindOfThisShare($share)) {
				continue;
			}

			$sharedWith = $share->getSharedWith();
			if ($share->getShareType() == IShare::TYPE_USER) {
				$user = $this->userManager->get($sharedWith);
				$mailTo = $user->getEMailAddress();
				$lang = $this->l10nFactory->getUserLanguage($user);
				$link = $this->urlGenerator->linkToRouteAbsolute('files.view.index', [
					'dir' => $share->getTarget()
				]);
			} else {
				$mailTo = $sharedWith;
				$lang = '';
				$link = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', [
					'token' => $share->getToken()
				]);
			}
			if (empty($mailTo)) {
				continue;
			}

			if (!empty($lang)) {
				$langByEmail[$mailTo] ??= $lang;
			}
			if (!isset($foldersByEmail[$mailTo])) {
				$foldersByEmail[$mailTo] = [];
			}
			$foldersByEmail[$mailTo][] = ['name' => $share->getNode()->getName(), 'link' => $link];

			$share->setReminderSent(true);
			$qb = $this->db->getQueryBuilder();
			$qb->update('share')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
				->set('reminder_sent', $qb->createNamedParameter($share->getReminderSent()))
				->execute();
		}

		return [$foldersByEmail, $langByEmail];
	}

	/**
	 * Checks if user has write permission and folder is empty
	 *
	 * @param IShare $share Share to check
	 * @return bool
	 */
	private function shouldRemindOfThisShare(IShare $share): bool {
		try {
			$folder = $share->getNode();
			$fileCount = count($folder->getDirectoryListing());
		} catch (NotFoundException) {
			$id = $share->getFullId();
			$this->logger->debug("File by share ID $id not found.");
			return false;
		}
		$permissions = $share->getPermissions();
		$hasCreatePermission = ($permissions & Constants::PERMISSION_CREATE) === Constants::PERMISSION_CREATE;
		return ($fileCount == 0 && $hasCreatePermission);
	}

	/**
	 * This method accepts data obtained by {@link prepareReminders} and sends reminder emails.
	 *
	 * @param array $foldersByEmail
	 * @param array $langByEmail
	 * @return void
	 */
	private function sendReminders(array $foldersByEmail, array $langByEmail): void {
		$instanceName = $this->defaults->getName();
		$from = [Util::getDefaultEmailAddress($instanceName) => $instanceName];
		foreach ($foldersByEmail as $email => $folders) {
			$l = $this->l10nFactory->get('files_sharing', $langByEmail[$email] ?? null);
			$emailTemplate = $this->generateEMailTemplate($l, $folders);
			$message = $this->mailer->createMessage();
			$message->setFrom($from);
			$message->setTo([$email]);
			$message->useTemplate($emailTemplate);
			$errorText = "Sending email with share reminder to $email failed.";
			try {
				$failedRecipients = $this->mailer->send($message);
				if (count($failedRecipients) > 0) {
					$this->logger->error($errorText);
				}
			} catch (\Exception) {
				$this->logger->error($errorText);
			}
		}
	}

	/**
	 * Returns the reminder email template
	 *
	 * @param IL10N $l
	 * @param array<array> $folders Folders the user should be reminded of
	 * @return IEMailTemplate
	 */
	private function generateEMailTemplate(IL10N $l, array $folders): IEMailTemplate {
		$emailTemplate = $this->mailer->createEMailTemplate('files_sharing.SharesReminder', [
			'folders' => $folders,
		]);

		$emailTemplate->addHeader();
		if (count($folders) == 1) {
			$emailTemplate->setSubject(
				$l->t('Remember to upload the files to %s', [$folders[0]['name']])
			);
			$emailTemplate->addBodyText($l->t(
				'We would like to kindly remind you that you have not yet uploaded any files to the shared folder.'
			));
		} else {
			$emailTemplate->setSubject(
				$l->t('Remember to upload the files to shared folders')
			);
			$emailTemplate->addBodyText($l->t(
				'We would like to kindly remind you that you have not yet uploaded any files to the shared folders.'
			));
		}

		foreach ($folders as $folder) {
			$emailTemplate->addBodyButton(
				$l->t('Open "%s"', [$folder['name']]),
				$folder['link']
			);
		}
		$emailTemplate->addFooter();
		return $emailTemplate;
	}
}
