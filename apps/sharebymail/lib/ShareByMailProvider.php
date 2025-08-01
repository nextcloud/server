<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ShareByMail;

use OC\Share20\DefaultShareProvider;
use OC\Share20\Exception\InvalidShare;
use OC\Share20\Share;
use OC\User\NoUserException;
use OCA\ShareByMail\Settings\SettingsManager;
use OCP\Activity\IManager;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\HintException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Security\PasswordContext;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IAttributes;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use OCP\Share\IShareProviderWithNotification;
use OCP\Util;
use Psr\Log\LoggerInterface;

/**
 * Class ShareByMail
 *
 * @package OCA\ShareByMail
 */
class ShareByMailProvider extends DefaultShareProvider implements IShareProviderWithNotification {
	/**
	 * Return the identifier of this provider.
	 *
	 * @return string Containing only [a-zA-Z0-9]
	 */
	public function identifier(): string {
		return 'ocMailShare';
	}

	public function __construct(
		private IConfig $config,
		private IDBConnection $dbConnection,
		private ISecureRandom $secureRandom,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private IL10N $l,
		private LoggerInterface $logger,
		private IMailer $mailer,
		private IURLGenerator $urlGenerator,
		private IManager $activityManager,
		private SettingsManager $settingsManager,
		private Defaults $defaults,
		private IHasher $hasher,
		private IEventDispatcher $eventDispatcher,
		private IShareManager $shareManager,
	) {
	}

	/**
	 * Share a path
	 *
	 * @throws ShareNotFound
	 * @throws \Exception
	 */
	public function create(IShare $share): IShare {
		$shareWith = $share->getSharedWith();
		// Check if file is not already shared with the given email,
		// if we have an email at all.
		$alreadyShared = $this->getSharedWith($shareWith, IShare::TYPE_EMAIL, $share->getNode(), 1, 0);
		if ($shareWith !== '' && !empty($alreadyShared)) {
			$message = 'Sharing %1$s failed, because this item is already shared with the account %2$s';
			$message_t = $this->l->t('Sharing %1$s failed, because this item is already shared with the account %2$s', [$share->getNode()->getName(), $shareWith]);
			$this->logger->debug(sprintf($message, $share->getNode()->getName(), $shareWith), ['app' => 'Federated File Sharing']);
			throw new \Exception($message_t);
		}

		// if the admin enforces a password for all mail shares we create a
		// random password and send it to the recipient
		$password = $share->getPassword() ?: '';
		$passwordEnforced = $this->shareManager->shareApiLinkEnforcePassword();
		if ($passwordEnforced && empty($password)) {
			$password = $this->autoGeneratePassword($share);
		}

		if (!empty($password)) {
			$share->setPassword($this->hasher->hash($password));
		}

		$shareId = $this->createMailShare($share);

		$this->createShareActivity($share);
		$data = $this->getRawShare($shareId);

		// Temporary set the clear password again to send it by mail
		// This need to be done after the share was created in the database
		// as the password is hashed in between.
		if (!empty($password)) {
			$data['password'] = $password;
		}

		return $this->createShareObject($data);
	}

	/**
	 * auto generate password in case of password enforcement on mail shares
	 *
	 * @throws \Exception
	 */
	protected function autoGeneratePassword(IShare $share): string {
		$initiatorUser = $this->userManager->get($share->getSharedBy());
		$initiatorEMailAddress = ($initiatorUser instanceof IUser) ? $initiatorUser->getEMailAddress() : null;
		$allowPasswordByMail = $this->settingsManager->sendPasswordByMail();

		if ($initiatorEMailAddress === null && !$allowPasswordByMail) {
			throw new \Exception(
				$this->l->t('We cannot send you the auto-generated password. Please set a valid email address in your personal settings and try again.')
			);
		}

		$passwordEvent = new GenerateSecurePasswordEvent(PasswordContext::SHARING);
		$this->eventDispatcher->dispatchTyped($passwordEvent);

		$password = $passwordEvent->getPassword();
		if ($password === null) {
			$password = $this->secureRandom->generate(8, ISecureRandom::CHAR_HUMAN_READABLE);
		}

		return $password;
	}

	/**
	 * create activity if a file/folder was shared by mail
	 */
	protected function createShareActivity(IShare $share, string $type = 'share'): void {
		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());

		$this->publishActivity(
			$type === 'share' ? Activity::SUBJECT_SHARED_EMAIL_SELF : Activity::SUBJECT_UNSHARED_EMAIL_SELF,
			[$userFolder->getRelativePath($share->getNode()->getPath()), $share->getSharedWith()],
			$share->getSharedBy(),
			$share->getNode()->getId(),
			(string)$userFolder->getRelativePath($share->getNode()->getPath())
		);

		if ($share->getShareOwner() !== $share->getSharedBy()) {
			$ownerFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
			$fileId = $share->getNode()->getId();
			$nodes = $ownerFolder->getById($fileId);
			$ownerPath = $nodes[0]->getPath();
			$this->publishActivity(
				$type === 'share' ? Activity::SUBJECT_SHARED_EMAIL_BY : Activity::SUBJECT_UNSHARED_EMAIL_BY,
				[$ownerFolder->getRelativePath($ownerPath), $share->getSharedWith(), $share->getSharedBy()],
				$share->getShareOwner(),
				$fileId,
				(string)$ownerFolder->getRelativePath($ownerPath)
			);
		}
	}

	/**
	 * create activity if a file/folder was shared by mail
	 */
	protected function createPasswordSendActivity(IShare $share, string $sharedWith, bool $sendToSelf): void {
		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());

		if ($sendToSelf) {
			$this->publishActivity(
				Activity::SUBJECT_SHARED_EMAIL_PASSWORD_SEND_SELF,
				[$userFolder->getRelativePath($share->getNode()->getPath())],
				$share->getSharedBy(),
				$share->getNode()->getId(),
				(string)$userFolder->getRelativePath($share->getNode()->getPath())
			);
		} else {
			$this->publishActivity(
				Activity::SUBJECT_SHARED_EMAIL_PASSWORD_SEND,
				[$userFolder->getRelativePath($share->getNode()->getPath()), $sharedWith],
				$share->getSharedBy(),
				$share->getNode()->getId(),
				(string)$userFolder->getRelativePath($share->getNode()->getPath())
			);
		}
	}


	/**
	 * publish activity if a file/folder was shared by mail
	 */
	protected function publishActivity(string $subject, array $parameters, string $affectedUser, int $fileId, string $filePath): void {
		$event = $this->activityManager->generateEvent();
		$event->setApp('sharebymail')
			->setType('shared')
			->setSubject($subject, $parameters)
			->setAffectedUser($affectedUser)
			->setObject('files', $fileId, $filePath);
		$this->activityManager->publish($event);
	}

	/**
	 * @throws \Exception
	 */
	protected function createMailShare(IShare $share): int {
		$share->setToken($this->generateToken());
		return $this->addShareToDB(
			$share->getNodeId(),
			$share->getNodeType(),
			$share->getSharedWith(),
			$share->getSharedBy(),
			$share->getShareOwner(),
			$share->getPermissions(),
			$share->getToken(),
			$share->getPassword(),
			$share->getPasswordExpirationTime(),
			$share->getSendPasswordByTalk(),
			$share->getHideDownload(),
			$share->getLabel(),
			$share->getExpirationDate(),
			$share->getNote(),
			$share->getAttributes(),
			$share->getMailSend(),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function sendMailNotification(IShare $share): bool {
		$shareId = $share->getId();

		$emails = $this->getSharedWithEmails($share);
		$validEmails = array_filter($emails, function (string $email) {
			return $this->mailer->validateMailAddress($email);
		});

		if (count($validEmails) === 0) {
			$this->removeShareFromTable((int)$shareId);
			$e = new HintException('Failed to send share by mail. Could not find a valid email address: ' . join(', ', $emails),
				$this->l->t('Failed to send share by email. Got an invalid email address'));
			$this->logger->error('Failed to send share by mail. Could not find a valid email address ' . join(', ', $emails), [
				'app' => 'sharebymail',
				'exception' => $e,
			]);
		}

		try {
			$this->sendEmail($share, $validEmails);

			// If we have a password set, we send it to the recipient
			if ($share->getPassword() !== null) {
				// If share-by-talk password is enabled, we do not send the notification
				// to the recipient. They will have to request it to the owner after opening the link.
				// Secondly, if the password expiration is disabled, we send the notification to the recipient
				// Lastly, if the mail to recipient failed, we send the password to the owner as a fallback.
				// If a password expires, the recipient will still be able to request a new one via talk.
				$passwordExpire = $this->config->getSystemValue('sharing.enable_mail_link_password_expiration', false);
				$passwordEnforced = $this->shareManager->shareApiLinkEnforcePassword();
				if ($passwordExpire === false || $share->getSendPasswordByTalk()) {
					$send = $this->sendPassword($share, $share->getPassword(), $validEmails);
					if ($passwordEnforced && $send === false) {
						$this->sendPasswordToOwner($share, $share->getPassword());
					}
				}
			}

			return true;
		} catch (HintException $hintException) {
			$this->logger->error('Failed to send share by mail.', [
				'app' => 'sharebymail',
				'exception' => $hintException,
			]);
			$this->removeShareFromTable((int)$shareId);
			throw $hintException;
		} catch (\Exception $e) {
			$this->logger->error('Failed to send share by mail.', [
				'app' => 'sharebymail',
				'exception' => $e,
			]);
			$this->removeShareFromTable((int)$shareId);
			throw new HintException(
				'Failed to send share by mail',
				$this->l->t('Failed to send share by email'),
				0,
				$e,
			);
		}
		return false;
	}

	/**
	 * @param IShare $share The share to send the email for
	 * @param array $emails The email addresses to send the email to
	 */
	protected function sendEmail(IShare $share, array $emails): void {
		$link = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', [
			'token' => $share->getToken()
		]);

		$expiration = $share->getExpirationDate();
		$filename = $share->getNode()->getName();
		$initiator = $share->getSharedBy();
		$note = $share->getNote();
		$shareWith = $share->getSharedWith();

		$initiatorUser = $this->userManager->get($initiator);
		$initiatorDisplayName = ($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;
		$message = $this->mailer->createMessage();

		$emailTemplate = $this->mailer->createEMailTemplate('sharebymail.RecipientNotification', [
			'filename' => $filename,
			'link' => $link,
			'initiator' => $initiatorDisplayName,
			'expiration' => $expiration,
			'shareWith' => $shareWith,
			'note' => $note
		]);

		$emailTemplate->setSubject($this->l->t('%1$s shared %2$s with you', [$initiatorDisplayName, $filename]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l->t('%1$s shared %2$s with you', [$initiatorDisplayName, $filename]), false);

		if ($note !== '') {
			$emailTemplate->addBodyListItem(
				htmlspecialchars($note),
				$this->l->t('Note:'),
				$this->getAbsoluteImagePath('caldav/description.png'),
				$note
			);
		}

		if ($expiration !== null) {
			$dateString = (string)$this->l->l('date', $expiration, ['width' => 'medium']);
			$emailTemplate->addBodyListItem(
				$this->l->t('This share is valid until %s at midnight', [$dateString]),
				$this->l->t('Expiration:'),
				$this->getAbsoluteImagePath('caldav/time.png'),
			);
		}

		$emailTemplate->addBodyButton(
			$this->l->t('Open %s', [$filename]),
			$link
		);

		// If multiple recipients are given, we send the mail to all of them
		if (count($emails) > 1) {
			// We do not want to expose the email addresses of the other recipients
			$message->setBcc($emails);
		} else {
			$message->setTo($emails);
		}

		// The "From" contains the sharers name
		$instanceName = $this->defaults->getName();
		$senderName = $instanceName;
		if ($this->settingsManager->replyToInitiator()) {
			$senderName = $this->l->t(
				'%1$s via %2$s',
				[
					$initiatorDisplayName,
					$instanceName
				]
			);
		}
		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);

		// The "Reply-To" is set to the sharer if an mail address is configured
		// also the default footer contains a "Do not reply" which needs to be adjusted.
		if ($initiatorUser && $this->settingsManager->replyToInitiator()) {
			$initiatorEmail = $initiatorUser->getEMailAddress();
			if ($initiatorEmail !== null) {
				$message->setReplyTo([$initiatorEmail => $initiatorDisplayName]);
				$emailTemplate->addFooter($instanceName . ($this->defaults->getSlogan() !== '' ? ' - ' . $this->defaults->getSlogan() : ''));
			} else {
				$emailTemplate->addFooter();
			}
		} else {
			$emailTemplate->addFooter();
		}

		$message->useTemplate($emailTemplate);
		$failedRecipients = $this->mailer->send($message);
		if (!empty($failedRecipients)) {
			$this->logger->error('Share notification mail could not be sent to: ' . implode(', ', $failedRecipients));
			return;
		}
	}

	/**
	 * Send password to recipient of a mail share
	 * Will return false if
	 *  1. the password is empty
	 *  2. the setting to send the password by mail is disabled
	 *  3. the share is set to send the password by talk
	 *
	 * @param IShare $share
	 * @param string $password
	 * @param array $emails
	 * @return bool
	 */
	protected function sendPassword(IShare $share, string $password, array $emails): bool {
		$filename = $share->getNode()->getName();
		$initiator = $share->getSharedBy();
		$shareWith = $share->getSharedWith();

		if ($password === '' || $this->settingsManager->sendPasswordByMail() === false || $share->getSendPasswordByTalk()) {
			return false;
		}

		$initiatorUser = $this->userManager->get($initiator);
		$initiatorDisplayName = ($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;
		$initiatorEmailAddress = ($initiatorUser instanceof IUser) ? $initiatorUser->getEMailAddress() : null;

		$plainBodyPart = $this->l->t('%1$s shared %2$s with you. You should have already received a separate mail with a link to access it.', [$initiatorDisplayName, $filename]);
		$htmlBodyPart = $this->l->t('%1$s shared %2$s with you. You should have already received a separate mail with a link to access it.', [$initiatorDisplayName, $filename]);

		$message = $this->mailer->createMessage();

		$emailTemplate = $this->mailer->createEMailTemplate('sharebymail.RecipientPasswordNotification', [
			'filename' => $filename,
			'password' => $password,
			'initiator' => $initiatorDisplayName,
			'initiatorEmail' => $initiatorEmailAddress,
			'shareWith' => $shareWith,
		]);

		$emailTemplate->setSubject($this->l->t('Password to access %1$s shared to you by %2$s', [$filename, $initiatorDisplayName]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l->t('Password to access %s', [$filename]), false);
		$emailTemplate->addBodyText(htmlspecialchars($htmlBodyPart), $plainBodyPart);
		$emailTemplate->addBodyText($this->l->t('It is protected with the following password:'));
		$emailTemplate->addBodyText($password);

		if ($this->config->getSystemValue('sharing.enable_mail_link_password_expiration', false) === true) {
			$expirationTime = new \DateTime();
			$expirationInterval = $this->config->getSystemValue('sharing.mail_link_password_expiration_interval', 3600);
			$expirationTime = $expirationTime->add(new \DateInterval('PT' . $expirationInterval . 'S'));
			$emailTemplate->addBodyText($this->l->t('This password will expire at %s', [$expirationTime->format('r')]));
		}

		// If multiple recipients are given, we send the mail to all of them
		if (count($emails) > 1) {
			// We do not want to expose the email addresses of the other recipients
			$message->setBcc($emails);
		} else {
			$message->setTo($emails);
		}

		// The "From" contains the sharers name
		$instanceName = $this->defaults->getName();
		$senderName = $instanceName;
		if ($this->settingsManager->replyToInitiator()) {
			$senderName = $this->l->t(
				'%1$s via %2$s',
				[
					$initiatorDisplayName,
					$instanceName
				]
			);
		}
		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);

		// The "Reply-To" is set to the sharer if an mail address is configured
		// also the default footer contains a "Do not reply" which needs to be adjusted.
		if ($initiatorUser && $this->settingsManager->replyToInitiator()) {
			$initiatorEmail = $initiatorUser->getEMailAddress();
			if ($initiatorEmail !== null) {
				$message->setReplyTo([$initiatorEmail => $initiatorDisplayName]);
				$emailTemplate->addFooter($instanceName . ($this->defaults->getSlogan() !== '' ? ' - ' . $this->defaults->getSlogan() : ''));
			} else {
				$emailTemplate->addFooter();
			}
		} else {
			$emailTemplate->addFooter();
		}

		$message->useTemplate($emailTemplate);
		$failedRecipients = $this->mailer->send($message);
		if (!empty($failedRecipients)) {
			$this->logger->error('Share password mail could not be sent to: ' . implode(', ', $failedRecipients));
			return false;
		}

		$this->createPasswordSendActivity($share, $shareWith, false);
		return true;
	}

	protected function sendNote(IShare $share): void {
		$recipient = $share->getSharedWith();


		$filename = $share->getNode()->getName();
		$initiator = $share->getSharedBy();
		$note = $share->getNote();

		$initiatorUser = $this->userManager->get($initiator);
		$initiatorDisplayName = ($initiatorUser instanceof IUser) ? $initiatorUser->getDisplayName() : $initiator;
		$initiatorEmailAddress = ($initiatorUser instanceof IUser) ? $initiatorUser->getEMailAddress() : null;

		$plainHeading = $this->l->t('%1$s shared %2$s with you and wants to add:', [$initiatorDisplayName, $filename]);
		$htmlHeading = $this->l->t('%1$s shared %2$s with you and wants to add', [$initiatorDisplayName, $filename]);

		$message = $this->mailer->createMessage();

		$emailTemplate = $this->mailer->createEMailTemplate('shareByMail.sendNote');

		$emailTemplate->setSubject($this->l->t('%s added a note to a file shared with you', [$initiatorDisplayName]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading(htmlspecialchars($htmlHeading), $plainHeading);
		$emailTemplate->addBodyText(htmlspecialchars($note), $note);

		$link = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare',
			['token' => $share->getToken()]);
		$emailTemplate->addBodyButton(
			$this->l->t('Open %s', [$filename]),
			$link
		);

		// The "From" contains the sharers name
		$instanceName = $this->defaults->getName();
		$senderName = $instanceName;
		if ($this->settingsManager->replyToInitiator()) {
			$senderName = $this->l->t(
				'%1$s via %2$s',
				[
					$initiatorDisplayName,
					$instanceName
				]
			);
		}
		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);
		if ($this->settingsManager->replyToInitiator() && $initiatorEmailAddress !== null) {
			$message->setReplyTo([$initiatorEmailAddress => $initiatorDisplayName]);
			$emailTemplate->addFooter($instanceName . ' - ' . $this->defaults->getSlogan());
		} else {
			$emailTemplate->addFooter();
		}

		$message->setTo([$recipient]);
		$message->useTemplate($emailTemplate);
		$this->mailer->send($message);
	}

	/**
	 * send auto generated password to the owner. This happens if the admin enforces
	 * a password for mail shares and forbid to send the password by mail to the recipient
	 *
	 * @throws \Exception
	 */
	protected function sendPasswordToOwner(IShare $share, string $password): bool {
		$filename = $share->getNode()->getName();
		$initiator = $this->userManager->get($share->getSharedBy());
		$initiatorEMailAddress = ($initiator instanceof IUser) ? $initiator->getEMailAddress() : null;
		$initiatorDisplayName = ($initiator instanceof IUser) ? $initiator->getDisplayName() : $share->getSharedBy();
		$shareWith = implode(', ', $this->getSharedWithEmails($share));

		if ($initiatorEMailAddress === null) {
			throw new \Exception(
				$this->l->t('We cannot send you the auto-generated password. Please set a valid email address in your personal settings and try again.')
			);
		}

		$bodyPart = $this->l->t('You just shared %1$s with %2$s. The share was already sent to the recipient. Due to the security policies defined by the administrator of %3$s each share needs to be protected by password and it is not allowed to send the password directly to the recipient. Therefore you need to forward the password manually to the recipient.', [$filename, $shareWith, $this->defaults->getName()]);

		$message = $this->mailer->createMessage();
		$emailTemplate = $this->mailer->createEMailTemplate('sharebymail.OwnerPasswordNotification', [
			'filename' => $filename,
			'password' => $password,
			'initiator' => $initiatorDisplayName,
			'initiatorEmail' => $initiatorEMailAddress,
			'shareWith' => $shareWith,
		]);

		$emailTemplate->setSubject($this->l->t('Password to access %1$s shared by you with %2$s', [$filename, $shareWith]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l->t('Password to access %s', [$filename]), false);
		$emailTemplate->addBodyText($bodyPart);
		$emailTemplate->addBodyText($this->l->t('This is the password:'));
		$emailTemplate->addBodyText($password);

		if ($this->config->getSystemValue('sharing.enable_mail_link_password_expiration', false) === true) {
			$expirationTime = new \DateTime();
			$expirationInterval = $this->config->getSystemValue('sharing.mail_link_password_expiration_interval', 3600);
			$expirationTime = $expirationTime->add(new \DateInterval('PT' . $expirationInterval . 'S'));
			$emailTemplate->addBodyText($this->l->t('This password will expire at %s', [$expirationTime->format('r')]));
		}

		$emailTemplate->addBodyText($this->l->t('You can choose a different password at any time in the share dialog.'));

		$emailTemplate->addFooter();

		$instanceName = $this->defaults->getName();
		$senderName = $this->l->t(
			'%1$s via %2$s',
			[
				$initiatorDisplayName,
				$instanceName
			]
		);
		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);
		$message->setTo([$initiatorEMailAddress => $initiatorDisplayName]);
		$message->useTemplate($emailTemplate);
		$this->mailer->send($message);

		$this->createPasswordSendActivity($share, $shareWith, true);

		return true;
	}

	private function getAbsoluteImagePath(string $path):string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath('core', $path)
		);
	}

	/**
	 * generate share token
	 */
	protected function generateToken(int $size = 15): string {
		$token = $this->secureRandom->generate($size, ISecureRandom::CHAR_HUMAN_READABLE);
		return $token;
	}

	public function getChildren(IShare $parent): array {
		$children = [];

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('parent', $qb->createNamedParameter($parent->getId())))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL)))
			->orderBy('id');

		$cursor = $qb->executeQuery();
		while ($data = $cursor->fetch()) {
			$children[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $children;
	}

	/**
	 * Add share to the database and return the ID
	 */
	protected function addShareToDB(
		?int $itemSource,
		?string $itemType,
		?string $shareWith,
		?string $sharedBy,
		?string $uidOwner,
		?int $permissions,
		?string $token,
		?string $password,
		?\DateTimeInterface $passwordExpirationTime,
		?bool $sendPasswordByTalk,
		?bool $hideDownload,
		?string $label,
		?\DateTimeInterface $expirationTime,
		?string $note = '',
		?IAttributes $attributes = null,
		?bool $mailSend = true,
	): int {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert('share')
			->setValue('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL))
			->setValue('item_type', $qb->createNamedParameter($itemType))
			->setValue('item_source', $qb->createNamedParameter($itemSource))
			->setValue('file_source', $qb->createNamedParameter($itemSource))
			->setValue('share_with', $qb->createNamedParameter($shareWith))
			->setValue('uid_owner', $qb->createNamedParameter($uidOwner))
			->setValue('uid_initiator', $qb->createNamedParameter($sharedBy))
			->setValue('permissions', $qb->createNamedParameter($permissions))
			->setValue('token', $qb->createNamedParameter($token))
			->setValue('password', $qb->createNamedParameter($password))
			->setValue('password_expiration_time', $qb->createNamedParameter($passwordExpirationTime, IQueryBuilder::PARAM_DATETIME_MUTABLE))
			->setValue('password_by_talk', $qb->createNamedParameter($sendPasswordByTalk, IQueryBuilder::PARAM_BOOL))
			->setValue('stime', $qb->createNamedParameter(time()))
			->setValue('hide_download', $qb->createNamedParameter((int)$hideDownload, IQueryBuilder::PARAM_INT))
			->setValue('label', $qb->createNamedParameter($label))
			->setValue('note', $qb->createNamedParameter($note))
			->setValue('mail_send', $qb->createNamedParameter((int)$mailSend, IQueryBuilder::PARAM_INT));

		// set share attributes
		$shareAttributes = $this->formatShareAttributes($attributes);

		$qb->setValue('attributes', $qb->createNamedParameter($shareAttributes));
		if ($expirationTime !== null) {
			$qb->setValue('expiration', $qb->createNamedParameter($expirationTime, IQueryBuilder::PARAM_DATETIME_MUTABLE));
		}

		$qb->executeStatement();
		return $qb->getLastInsertId();
	}

	/**
	 * Update a share
	 */
	public function update(IShare $share, ?string $plainTextPassword = null): IShare {
		$originalShare = $this->getShareById($share->getId());

		// a real password was given
		$validPassword = $plainTextPassword !== null && $plainTextPassword !== '';

		if ($validPassword && ($originalShare->getPassword() !== $share->getPassword()
								|| ($originalShare->getSendPasswordByTalk() && !$share->getSendPasswordByTalk()))) {
			$emails = $this->getSharedWithEmails($share);
			$validEmails = array_filter($emails, function ($email) {
				return $this->mailer->validateMailAddress($email);
			});
			$this->sendPassword($share, $plainTextPassword, $validEmails);
		}

		$shareAttributes = $this->formatShareAttributes($share->getAttributes());

		/*
		 * We allow updating mail shares
		 */
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($share->getId())))
			->set('item_source', $qb->createNamedParameter($share->getNodeId()))
			->set('file_source', $qb->createNamedParameter($share->getNodeId()))
			->set('share_with', $qb->createNamedParameter($share->getSharedWith()))
			->set('permissions', $qb->createNamedParameter($share->getPermissions()))
			->set('uid_owner', $qb->createNamedParameter($share->getShareOwner()))
			->set('uid_initiator', $qb->createNamedParameter($share->getSharedBy()))
			->set('password', $qb->createNamedParameter($share->getPassword()))
			->set('password_expiration_time', $qb->createNamedParameter($share->getPasswordExpirationTime(), IQueryBuilder::PARAM_DATETIME_MUTABLE))
			->set('label', $qb->createNamedParameter($share->getLabel()))
			->set('password_by_talk', $qb->createNamedParameter($share->getSendPasswordByTalk(), IQueryBuilder::PARAM_BOOL))
			->set('expiration', $qb->createNamedParameter($share->getExpirationDate(), IQueryBuilder::PARAM_DATETIME_MUTABLE))
			->set('note', $qb->createNamedParameter($share->getNote()))
			->set('hide_download', $qb->createNamedParameter((int)$share->getHideDownload(), IQueryBuilder::PARAM_INT))
			->set('attributes', $qb->createNamedParameter($shareAttributes))
			->set('mail_send', $qb->createNamedParameter((int)$share->getMailSend(), IQueryBuilder::PARAM_INT))
			->set('reminder_sent', $qb->createNamedParameter($share->getReminderSent(), IQueryBuilder::PARAM_BOOL))
			->executeStatement();

		if ($originalShare->getNote() !== $share->getNote() && $share->getNote() !== '') {
			$this->sendNote($share);
		}

		return $share;
	}

	/**
	 * @inheritdoc
	 */
	public function move(IShare $share, $recipient): IShare {
		/**
		 * nothing to do here, mail shares are only outgoing shares
		 */
		return $share;
	}

	/**
	 * Delete a share (owner unShares the file)
	 *
	 * @param IShare $share
	 */
	public function delete(IShare $share): void {
		try {
			$this->createShareActivity($share, 'unshare');
		} catch (\Exception $e) {
		}

		$this->removeShareFromTable((int)$share->getId());
	}

	/**
	 * @inheritdoc
	 */
	public function deleteFromSelf(IShare $share, $recipient): void {
		// nothing to do here, mail shares are only outgoing shares
	}

	public function restore(IShare $share, string $recipient): IShare {
		throw new GenericShareException('not implemented');
	}

	/**
	 * @inheritdoc
	 */
	public function getSharesBy($userId, $shareType, $node, $reshares, $limit, $offset): array {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share');

		$qb->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL)));

		/**
		 * Reshares for this user are shares where they are the owner.
		 */
		if ($reshares === false) {
			//Special case for old shares created via the web UI
			$or1 = $qb->expr()->andX(
				$qb->expr()->eq('uid_owner', $qb->createNamedParameter($userId)),
				$qb->expr()->isNull('uid_initiator')
			);

			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId)),
					$or1
				)
			);
		} elseif ($node === null) {
			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->eq('uid_owner', $qb->createNamedParameter($userId)),
					$qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId))
				)
			);
		}

		if ($node !== null) {
			$qb->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($node->getId())));
		}

		if ($limit !== -1) {
			$qb->setMaxResults($limit);
		}

		$qb->setFirstResult($offset);
		$qb->orderBy('id');

		$cursor = $qb->executeQuery();
		$shares = [];
		while ($data = $cursor->fetch()) {
			$shares[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * @inheritdoc
	 */
	public function getShareById($id, $recipientId = null): IShare {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL)));

		$cursor = $qb->executeQuery();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ShareNotFound();
		}

		try {
			$share = $this->createShareObject($data);
		} catch (InvalidShare $e) {
			throw new ShareNotFound();
		}

		return $share;
	}

	/**
	 * Get shares for a given path
	 *
	 * @return IShare[]
	 */
	public function getSharesByPath(Node $path): array {
		$qb = $this->dbConnection->getQueryBuilder();

		$cursor = $qb->select('*')
			->from('share')
			->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($path->getId())))
			->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL)))
			->executeQuery();

		$shares = [];
		while ($data = $cursor->fetch()) {
			$shares[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * @inheritdoc
	 */
	public function getSharedWith($userId, $shareType, $node, $limit, $offset): array {
		/** @var IShare[] $shares */
		$shares = [];

		//Get shares directly with this user
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share');

		// Order by id
		$qb->orderBy('id');

		// Set limit and offset
		if ($limit !== -1) {
			$qb->setMaxResults($limit);
		}
		$qb->setFirstResult($offset);

		$qb->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL)));
		$qb->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($userId)));

		// Filter by node if provided
		if ($node !== null) {
			$qb->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($node->getId())));
		}

		$cursor = $qb->executeQuery();

		while ($data = $cursor->fetch()) {
			$shares[] = $this->createShareObject($data);
		}
		$cursor->closeCursor();


		return $shares;
	}

	/**
	 * Get a share by token
	 *
	 * @throws ShareNotFound
	 */
	public function getShareByToken($token): IShare {
		$qb = $this->dbConnection->getQueryBuilder();

		$cursor = $qb->select('*')
			->from('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL)))
			->andWhere($qb->expr()->eq('token', $qb->createNamedParameter($token)))
			->executeQuery();

		$data = $cursor->fetch();

		if ($data === false) {
			throw new ShareNotFound('Share not found', $this->l->t('Could not find share'));
		}

		try {
			$share = $this->createShareObject($data);
		} catch (InvalidShare $e) {
			throw new ShareNotFound('Share not found', $this->l->t('Could not find share'));
		}

		return $share;
	}

	/**
	 * remove share from table
	 */
	protected function removeShareFromTable(int $shareId): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($shareId)));
		$qb->executeStatement();
	}

	/**
	 * Create a share object from a database row
	 *
	 * @throws InvalidShare
	 * @throws ShareNotFound
	 */
	protected function createShareObject(array $data): IShare {
		$share = new Share($this->rootFolder, $this->userManager);
		$share->setId((int)$data['id'])
			->setShareType((int)$data['share_type'])
			->setPermissions((int)$data['permissions'])
			->setTarget($data['file_target'])
			->setMailSend((bool)$data['mail_send'])
			->setNote($data['note'])
			->setToken($data['token']);

		$shareTime = new \DateTime();
		$shareTime->setTimestamp((int)$data['stime']);
		$share->setShareTime($shareTime);
		$share->setSharedWith($data['share_with'] ?? '');
		$share->setPassword($data['password']);
		$passwordExpirationTime = \DateTime::createFromFormat('Y-m-d H:i:s', $data['password_expiration_time'] ?? '');
		$share->setPasswordExpirationTime($passwordExpirationTime !== false ? $passwordExpirationTime : null);
		$share->setLabel($data['label'] ?? '');
		$share->setSendPasswordByTalk((bool)$data['password_by_talk']);
		$share->setHideDownload((bool)$data['hide_download']);
		$share->setReminderSent((bool)$data['reminder_sent']);

		if ($data['uid_initiator'] !== null) {
			$share->setShareOwner($data['uid_owner']);
			$share->setSharedBy($data['uid_initiator']);
		} else {
			//OLD SHARE
			$share->setSharedBy($data['uid_owner']);
			$path = $this->getNode($share->getSharedBy(), (int)$data['file_source']);

			$owner = $path->getOwner();
			$share->setShareOwner($owner->getUID());
		}

		if ($data['expiration'] !== null) {
			$expiration = \DateTime::createFromFormat('Y-m-d H:i:s', $data['expiration']);
			if ($expiration !== false) {
				$share->setExpirationDate($expiration);
			}
		}

		$share = $this->updateShareAttributes($share, $data['attributes']);

		$share->setNodeId((int)$data['file_source']);
		$share->setNodeType($data['item_type']);

		$share->setProviderId($this->identifier());

		return $share;
	}

	/**
	 * Get the node with file $id for $user
	 *
	 * @throws InvalidShare
	 */
	private function getNode(string $userId, int $id): Node {
		try {
			$userFolder = $this->rootFolder->getUserFolder($userId);
		} catch (NoUserException $e) {
			throw new InvalidShare();
		}

		$nodes = $userFolder->getById($id);

		if (empty($nodes)) {
			throw new InvalidShare();
		}

		return $nodes[0];
	}

	/**
	 * A user is deleted from the system
	 * So clean up the relevant shares.
	 */
	public function userDeleted($uid, $shareType): void {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->delete('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL)))
			->andWhere($qb->expr()->eq('uid_owner', $qb->createNamedParameter($uid)))
			->executeStatement();
	}

	/**
	 * This provider does not support group shares
	 */
	public function groupDeleted($gid): void {
	}

	/**
	 * This provider does not support group shares
	 */
	public function userDeletedFromGroup($uid, $gid): void {
	}

	/**
	 * get database row of a give share
	 *
	 * @throws ShareNotFound
	 */
	protected function getRawShare(int $id): array {
		// Now fetch the inserted share and create a complete share object
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		$cursor = $qb->executeQuery();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new ShareNotFound;
		}

		return $data;
	}

	public function getSharesInFolder($userId, Folder $node, $reshares, $shallow = true): array {
		return $this->getSharesInFolderInternal($userId, $node, $reshares);
	}

	public function getAllSharesInFolder(Folder $node): array {
		return $this->getSharesInFolderInternal(null, $node, null);
	}

	/**
	 * @return array<int, list<IShare>>
	 */
	private function getSharesInFolderInternal(?string $userId, Folder $node, ?bool $reshares): array {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('*')
			->from('share', 's')
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere(
				$qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL))
			);

		if ($userId !== null) {
			/**
			 * Reshares for this user are shares where they are the owner.
			 */
			if ($reshares !== true) {
				$qb->andWhere($qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId)));
			} else {
				$qb->andWhere(
					$qb->expr()->orX(
						$qb->expr()->eq('uid_owner', $qb->createNamedParameter($userId)),
						$qb->expr()->eq('uid_initiator', $qb->createNamedParameter($userId))
					)
				);
			}
		}

		$qb->innerJoin('s', 'filecache', 'f', $qb->expr()->eq('s.file_source', 'f.fileid'));

		$qb->andWhere($qb->expr()->eq('f.parent', $qb->createNamedParameter($node->getId())));

		$qb->orderBy('id');

		$cursor = $qb->executeQuery();
		$shares = [];
		while ($data = $cursor->fetch()) {
			$shares[$data['fileid']][] = $this->createShareObject($data);
		}
		$cursor->closeCursor();

		return $shares;
	}

	/**
	 * @inheritdoc
	 */
	public function getAccessList($nodes, $currentAccess): array {
		$ids = [];
		foreach ($nodes as $node) {
			$ids[] = $node->getId();
		}

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('share_with', 'file_source', 'token')
			->from('share')
			->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL)))
			->andWhere($qb->expr()->in('file_source', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($qb->expr()->in('item_type', $qb->createNamedParameter(['file', 'folder'], IQueryBuilder::PARAM_STR_ARRAY)));
		$cursor = $qb->executeQuery();

		$public = false;
		$mail = [];
		while ($row = $cursor->fetch()) {
			$public = true;
			if ($currentAccess === false) {
				$mail[] = $row['share_with'];
			} else {
				$mail[$row['share_with']] = [
					'node_id' => $row['file_source'],
					'token' => $row['token']
				];
			}
		}
		$cursor->closeCursor();

		return ['public' => $public, 'mail' => $mail];
	}

	public function getAllShares(): iterable {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select('*')
			->from('share')
			->where(
				$qb->expr()->orX(
					$qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_EMAIL))
				)
			);

		$cursor = $qb->executeQuery();
		while ($data = $cursor->fetch()) {
			try {
				$share = $this->createShareObject($data);
			} catch (InvalidShare $e) {
				continue;
			} catch (ShareNotFound $e) {
				continue;
			}

			yield $share;
		}
		$cursor->closeCursor();
	}

	/**
	 * Extract the emails from the share
	 * It can be a single email, from the share_with field
	 * or a list of emails from the emails attributes field.
	 * @param IShare $share
	 * @return string[]
	 */
	protected function getSharedWithEmails(IShare $share): array {
		$attributes = $share->getAttributes();

		if ($attributes === null) {
			return [$share->getSharedWith()];
		}

		$emails = $attributes->getAttribute('shareWith', 'emails');
		if (isset($emails) && is_array($emails) && !empty($emails)) {
			return $emails;
		}
		return [$share->getSharedWith()];
	}
}
