<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace OCA\DAV\CalDAV\Schedule;

use OCP\ILogger;
use OCP\Mail\IMailer;
use Sabre\DAV;
use Sabre\VObject\ITip;
use Sabre\CalDAV\Schedule\IMipPlugin as SabreIMipPlugin;
/**
 * iMIP handler.
 *
 * This class is responsible for sending out iMIP messages. iMIP is the
 * email-based transport for iTIP. iTIP deals with scheduling operations for
 * iCalendar objects.
 *
 * If you want to customize the email that gets sent out, you can do so by
 * extending this class and overriding the sendMessage method.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class IMipPlugin extends SabreIMipPlugin {

	/** @var IMailer */
	private $mailer;

	/** @var ILogger */
	private $logger;

	/**
	 * Creates the email handler.
	 *
	 * @param IMailer $mailer
	 */
	function __construct(IMailer $mailer, ILogger $logger) {
		parent::__construct('');
		$this->mailer = $mailer;
		$this->logger = $logger;
	}

	/**
	 * Event handler for the 'schedule' event.
	 *
	 * @param ITip\Message $iTipMessage
	 * @return void
	 */
	function schedule(ITip\Message $iTipMessage) {

		// Not sending any emails if the system considers the update
		// insignificant.
		if (!$iTipMessage->significantChange) {
			if (!$iTipMessage->scheduleStatus) {
				$iTipMessage->scheduleStatus = '1.0;We got the message, but it\'s not significant enough to warrant an email';
			}
			return;
		}

		$summary = $iTipMessage->message->VEVENT->SUMMARY;

		if (parse_url($iTipMessage->sender, PHP_URL_SCHEME) !== 'mailto') {
			return;
		}

		if (parse_url($iTipMessage->recipient, PHP_URL_SCHEME) !== 'mailto') {
			return;
		}

		$sender = substr($iTipMessage->sender, 7);
		$recipient = substr($iTipMessage->recipient, 7);

		$senderName = ($iTipMessage->senderName) ? $iTipMessage->senderName : null;
		$recipientName = ($iTipMessage->recipientName) ? $iTipMessage->recipientName : null;

		$subject = 'SabreDAV iTIP message';
		switch (strtoupper($iTipMessage->method)) {
			case 'REPLY' :
				$subject = 'Re: ' . $summary;
				break;
			case 'REQUEST' :
				$subject = $summary;
				break;
			case 'CANCEL' :
				$subject = 'Cancelled: ' . $summary;
				break;
		}

		$contentType = 'text/calendar; charset=UTF-8; method=' . $iTipMessage->method;

		$message = $this->mailer->createMessage();

		$message->setReplyTo([$sender => $senderName])
			->setTo([$recipient => $recipientName])
			->setSubject($subject)
			->setBody($iTipMessage->message->serialize(), $contentType);
		try {
			$failed = $this->mailer->send($message);
			if ($failed) {
				$this->logger->error('Unable to deliver message to {failed}', ['app' => 'dav', 'failed' =>  implode(', ', $failed)]);
				$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
			}
			$iTipMessage->scheduleStatus = '1.1; Scheduling message is sent via iMip';
		} catch(\Exception $ex) {
			$this->logger->logException($ex, ['app' => 'dav']);
			$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
		}
	}

}
