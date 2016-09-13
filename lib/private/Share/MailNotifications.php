<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author scolebrook <scolebrook@mac.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Share;

use DateTime;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Mail\IMailer;
use OCP\ILogger;
use OCP\Defaults;
use OCP\Util;

/**
 * Class MailNotifications
 *
 * @package OC\Share
 */
class MailNotifications {

	/** @var IUser sender userId */
	private $user;
	/** @var string sender email address */
	private $replyTo;
	/** @var string */
	private $senderDisplayName;
	/** @var IL10N */
	private $l;
	/** @var IMailer */
	private $mailer;
	/** @var Defaults */
	private $defaults;
	/** @var ILogger */
	private $logger;
	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param IUser $user
	 * @param IL10N $l10n
	 * @param IMailer $mailer
	 * @param ILogger $logger
	 * @param Defaults $defaults
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IUser $user,
								IL10N $l10n,
								IMailer $mailer,
								ILogger $logger,
								Defaults $defaults,
								IURLGenerator $urlGenerator) {
		$this->l = $l10n;
		$this->user = $user;
		$this->mailer = $mailer;
		$this->logger = $logger;
		$this->defaults = $defaults;
		$this->urlGenerator = $urlGenerator;

		$this->replyTo = $this->user->getEMailAddress();
		$this->senderDisplayName = $this->user->getDisplayName();
	}

	/**
	 * inform users if a file was shared with them
	 *
	 * @param IUser[] $recipientList list of recipients
	 * @param string $itemSource shared item source
	 * @param string $itemType shared item type
	 * @return array list of user to whom the mail send operation failed
	 */
	public function sendInternalShareMail($recipientList, $itemSource, $itemType) {
		$noMail = [];

		foreach ($recipientList as $recipient) {
			$recipientDisplayName = $recipient->getDisplayName();
			$to = $recipient->getEMailAddress();

			if ($to === '') {
				$noMail[] = $recipientDisplayName;
				continue;
			}

			$items = $this->getItemSharedWithUser($itemSource, $itemType, $recipient);
			$filename = trim($items[0]['file_target'], '/');
			$subject = (string) $this->l->t('%s shared »%s« with you', array($this->senderDisplayName, $filename));
			$expiration = null;
			if (isset($items[0]['expiration'])) {
				try {
					$date = new DateTime($items[0]['expiration']);
					$expiration = $date->getTimestamp();
				} catch (\Exception $e) {
					$this->logger->error("Couldn't read date: ".$e->getMessage(), ['app' => 'sharing']);
				}
			}

			$link = $this->urlGenerator->linkToRouteAbsolute(
				'files.viewcontroller.showFile',
				['fileid' => $items[0]['item_source']]
			);

			list($htmlBody, $textBody) = $this->createMailBody($filename, $link, $expiration, 'internal');

			// send it out now
			try {
				$message = $this->mailer->createMessage();
				$message->setSubject($subject);
				$message->setTo([$to => $recipientDisplayName]);
				$message->setHtmlBody($htmlBody);
				$message->setPlainBody($textBody);
				$message->setFrom([
					Util::getDefaultEmailAddress('sharing-noreply') =>
						(string)$this->l->t('%s via %s', [
							$this->senderDisplayName,
							$this->defaults->getName()
						]),
					]);
				if(!is_null($this->replyTo)) {
					$message->setReplyTo([$this->replyTo]);
				}

				$this->mailer->send($message);
			} catch (\Exception $e) {
				$this->logger->error("Can't send mail to inform the user about an internal share: ".$e->getMessage(), ['app' => 'sharing']);
				$noMail[] = $recipientDisplayName;
			}
		}

		return $noMail;

	}

	/**
	 * inform recipient about public link share
	 *
	 * @param string $recipient recipient email address
	 * @param string $filename the shared file
	 * @param string $link the public link
	 * @param int $expiration expiration date (timestamp)
	 * @return string[] $result of failed recipients
	 */
	public function sendLinkShareMail($recipient, $filename, $link, $expiration) {
		$subject = (string)$this->l->t('%s shared »%s« with you', [$this->senderDisplayName, $filename]);
		list($htmlBody, $textBody) = $this->createMailBody($filename, $link, $expiration);

		$recipient = str_replace([', ', '; ', ',', ';', ' '], ',', $recipient);
		$recipients = explode(',', $recipient);
		try {
			$message = $this->mailer->createMessage();
			$message->setSubject($subject);
			$message->setTo($recipients);
			$message->setHtmlBody($htmlBody);
			$message->setPlainBody($textBody);
			$message->setFrom([
				Util::getDefaultEmailAddress('sharing-noreply') =>
					(string)$this->l->t('%s via %s', [
						$this->senderDisplayName,
						$this->defaults->getName()
					]),
			]);
			if(!is_null($this->replyTo)) {
				$message->setReplyTo([$this->replyTo]);
			}

			return $this->mailer->send($message);
		} catch (\Exception $e) {
			$this->logger->error("Can't send mail with public link to $recipient: ".$e->getMessage(), ['app' => 'sharing']);
			return [$recipient];
		}
	}

	/**
	 * create mail body for plain text and html mail
	 *
	 * @param string $filename the shared file
	 * @param string $link link to the shared file
	 * @param int $expiration expiration date (timestamp)
	 * @param string $prefix prefix of mail template files
	 * @return array an array of the html mail body and the plain text mail body
	 */
	private function createMailBody($filename, $link, $expiration, $prefix = '') {
		$formattedDate = $expiration ? $this->l->l('date', $expiration) : null;

		$html = new \OC_Template('core', $prefix . 'mail', '');
		$html->assign ('link', $link);
		$html->assign ('user_displayname', $this->senderDisplayName);
		$html->assign ('filename', $filename);
		$html->assign('expiration',  $formattedDate);
		$htmlMail = $html->fetchPage();

		$plainText = new \OC_Template('core', $prefix . 'altmail', '');
		$plainText->assign ('link', $link);
		$plainText->assign ('user_displayname', $this->senderDisplayName);
		$plainText->assign ('filename', $filename);
		$plainText->assign('expiration', $formattedDate);
		$plainTextMail = $plainText->fetchPage();

		return [$htmlMail, $plainTextMail];
	}

	/**
	 * @param string $itemSource
	 * @param string $itemType
	 * @param IUser $recipient
	 * @return array
	 */
	protected function getItemSharedWithUser($itemSource, $itemType, $recipient) {
		return Share::getItemSharedWithUser($itemType, $itemSource, $recipient->getUID());
	}

}
