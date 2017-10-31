<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2017, Georg Ehrke
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\DAV\CalDAV\Schedule;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\L10N\IFactory as L10NFactory;
use OCP\Mail\IMailer;
use Sabre\CalDAV\Schedule\IMipPlugin as SabreIMipPlugin;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property;
use Sabre\VObject\Recur\EventIterator;
use Swift_Attachment;
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

	/** @var string */
	private $userId;

	/** @var IConfig */
	private $config;

	/** @var IMailer */
	private $mailer;

	/** @var ILogger */
	private $logger;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var L10NFactory */
	private $l10nFactory;

	const MAX_DATE = '2038-01-01';

	const METHOD_REQUEST = 'request';
	const METHOD_REPLY = 'reply';
	const METHOD_CANCEL = 'cancel';

	/**
	 * @param IConfig $config
	 * @param IMailer $mailer
	 * @param ILogger $logger
	 * @param ITimeFactory $timeFactory
	 * @param L10NFactory $l10nFactory
	 * @param string $userId
	 */
	public function __construct(IConfig $config, IMailer $mailer, ILogger $logger, ITimeFactory $timeFactory, L10NFactory $l10nFactory, $userId) {
		parent::__construct('');
		$this->userId = $userId;
		$this->config = $config;
		$this->mailer = $mailer;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
		$this->l10nFactory = $l10nFactory;
	}

	/**
	 * Event handler for the 'schedule' event.
	 *
	 * @param Message $iTipMessage
	 * @return void
	 */
	public function schedule(Message $iTipMessage) {

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

		// don't send out mails for events that already took place
		if ($this->isEventInThePast($iTipMessage->message)) {
			return;
		}

		// Strip off mailto:
		$sender = substr($iTipMessage->sender, 7);
		$recipient = substr($iTipMessage->recipient, 7);

		$senderName = $iTipMessage->senderName ?: null;
		$recipientName = $iTipMessage->recipientName ?: null;

		switch (strtolower($iTipMessage->method)) {
			case self::METHOD_REPLY:
				$subject = 'Re: ' . $summary;
				$templateName = self::METHOD_REPLY;
				break;
			case self::METHOD_CANCEL:
				$subject = 'Cancelled: ' . $summary;
				$templateName = self::METHOD_CANCEL;
				break;
			case self::METHOD_REQUEST:
			default: // Treat 'REQUEST' as the default
				$subject = $summary;
				$templateName = self::METHOD_REQUEST;
				break;
		}

		/** @var VEvent $vevent */
		$vevent = $iTipMessage->message->VEVENT;

		$attendee = $this->getCurrentAttendee($iTipMessage);
		$defaultLang = $this->config->getUserValue($this->userId, 'core', 'lang', $this->l10nFactory->findLanguage());
		$lang = $this->getAttendeeLangOrDefault($defaultLang, $attendee);
		$l10n = $this->l10nFactory->get('dav', $lang);

		$meetingAttendeeName = $recipientName ?: $recipient;
		$meetingInviteeName = $senderName ?: $sender;

		$meetingTitle = $vevent->SUMMARY;
		$meetingDescription = $vevent->DESCRIPTION;

		// TODO(leon): Maybe it's a good idea to make this locale dependent?
		// TODO(leon): Don't show H:i if it's an all-day meeting
		$dateFormatStr = 'Y-m-d H:i e';
		$meetingStart = $vevent->DTSTART->getDateTime()->format($dateFormatStr);
		$meetingEnd = $vevent->DTEND->getDateTime()->format($dateFormatStr);

		$meetingUrl = $vevent->URL;

		$defaultVal = '--';
		$templateParams = array(
			'attendee_name' => (string)$meetingAttendeeName ?: $defaultVal,
			'invitee_name' => (string)$meetingInviteeName ?: $defaultVal,
			'meeting_title' => (string)$meetingTitle ?: $defaultVal,
			'meeting_description' => (string)$meetingDescription ?: $defaultVal,
			'meeting_start' => (string)$meetingStart,
			'meeting_end' => (string)$meetingEnd,
			'meeting_url' => (string)$meetingUrl ?: $defaultVal,
		);
		$templates = $this->getInviteTemplates($l10n, $templateParams);

		$message = $this->mailer->createMessage()
			->setReplyTo([$sender => $senderName])
			->setTo([$recipient => $recipientName])
			->setSubject($subject)
			->setPlainBody($templates[$templateName]->renderText())
		;
		// We need to attach the event as 'attachment'
		// Swiftmail can't properly handle inline-multipart-based files
		// See https://github.com/swiftmailer/swiftmailer/issues/615
		$filename = 'event.ics'; // TODO(leon): Make file name unique, e.g. add event id
		$contentType = 'text/calendar; method=' . $iTipMessage->method;
		$attachment = Swift_Attachment::newInstance()
			->setFilename($filename)
			->setContentType($contentType)
			->setBody($iTipMessage->message->serialize())
		;
		$message->getSwiftMessage()->attach($attachment);

		try {
			$failed = $this->mailer->send($message);
			$iTipMessage->scheduleStatus = '1.1; Scheduling message is sent via iMip';
			if ($failed) {
				$this->logger->error('Unable to deliver message to {failed}', ['app' => 'dav', 'failed' =>  implode(', ', $failed)]);
				$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
			}
		} catch(\Exception $ex) {
			$this->logger->logException($ex, ['app' => 'dav']);
			$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
		}
	}

	/**
	 * check if event took place in the past already
	 * @param VCalendar $vObject
	 * @return bool
	 */
	private function isEventInThePast(VCalendar $vObject) {
		/** @var VEvent $component */
		$component = $vObject->VEVENT;

		$firstOccurrence = $component->DTSTART->getDateTime()->getTimeStamp();
		// Finding the last occurrence is a bit harder
		if (!isset($component->RRULE)) {
			if (isset($component->DTEND)) {
				$lastOccurrence = $component->DTEND->getDateTime()->getTimeStamp();
			} elseif (isset($component->DURATION)) {
				/** @var \DateTime $endDate */
				$endDate = clone $component->DTSTART->getDateTime();
				// $component->DTEND->getDateTime() returns DateTimeImmutable
				$endDate = $endDate->add(DateTimeParser::parse($component->DURATION->getValue()));
				$lastOccurrence = $endDate->getTimestamp();
			} elseif (!$component->DTSTART->hasTime()) {
				/** @var \DateTime $endDate */
				$endDate = clone $component->DTSTART->getDateTime();
				// $component->DTSTART->getDateTime() returns DateTimeImmutable
				$endDate = $endDate->modify('+1 day');
				$lastOccurrence = $endDate->getTimestamp();
			} else {
				$lastOccurrence = $firstOccurrence;
			}
		} else {
			$it = new EventIterator($vObject, (string)$component->UID);
			$maxDate = new \DateTime(self::MAX_DATE);
			if ($it->isInfinite()) {
				$lastOccurrence = $maxDate->getTimestamp();
			} else {
				$end = $it->getDtEnd();
				while($it->valid() && $end < $maxDate) {
					$end = $it->getDtEnd();
					$it->next();

				}
				$lastOccurrence = $end->getTimestamp();
			}
		}

		$currentTime = $this->timeFactory->getTime();
		return $lastOccurrence < $currentTime;
	}

	/**
	 * @param string $scope
	 * @return \OCP\Mail\IEMailTemplate
	 */
	private function getEmptyInviteTemplate($scope) {
		return $this->mailer->createEMailTemplate('dav.invite.' . $scope, []);
	}

	/**
	 * @param IL10N $l10n
	 * @param array $_
	 * @return array
	 */
	private function getInviteTemplates(IL10N $l10n, array $_) {
		$ret = [];
		$requestTmpl = $ret[self::METHOD_REQUEST] = $this->getEmptyInviteTemplate(self::METHOD_REQUEST);
		$replyTmpl = $ret[self::METHOD_REPLY] = $this->getEmptyInviteTemplate(self::METHOD_REPLY);
		$cancelTmpl = $ret[self::METHOD_CANCEL] = $this->getEmptyInviteTemplate(self::METHOD_CANCEL);

		$commonPlainBodyStart = $l10n->t('Hello %s,', array($_['attendee_name']));
		$commonPlainBodyEnd = $l10n->t(
'      Title: %s
Description: %s
      Start: %s
        End: %s
        URL: %s', array(
			$_['meeting_title'],
			$_['meeting_description'],
			$_['meeting_start'],
			$_['meeting_end'],
			$_['meeting_url'],
		));

		$requestTmpl->addBodyText('', $commonPlainBodyStart);
		$requestTmpl->addBodyText('', $l10n->t('%s has invited you to a meeting.', array($_['invitee_name'])));
		$requestTmpl->addBodyText('', $commonPlainBodyEnd);

		$replyTmpl->addBodyText('', $commonPlainBodyStart);
		$replyTmpl->addBodyText('', $l10n->t('the meeting with %s was updated.', array($_['invitee_name'])));
		$replyTmpl->addBodyText('', $commonPlainBodyEnd);

		$cancelTmpl->addBodyText('', $commonPlainBodyStart);
		$cancelTmpl->addBodyText('', $l10n->t('the meeting with %s was canceled.', array($_['invitee_name'])));
		$cancelTmpl->addBodyText('', $commonPlainBodyEnd);

		return $ret;
	}

	/**
	 * @param Message $iTipMessage
	 * @return null|Property
	 */
	private function getCurrentAttendee(Message $iTipMessage) {
		/** @var VEvent $vevent */
		$vevent = $iTipMessage->message->VEVENT;
		$attendees = $vevent->select('ATTENDEE');
		foreach ($attendees as $attendee) {
			/** @var Property $attendee */
			if (strcasecmp($attendee->getValue(), $iTipMessage->recipient) === 0) {
				return $attendee;
			}
		}
		return null;
	}

	/**
	 * @param string $default
	 * @param Property|null $attendee
	 * @return string
	 */
	private function getAttendeeLangOrDefault($default, Property $attendee = null) {
		if ($attendee !== null) {
			$lang = $attendee->offsetGet('LANGUAGE');
			if ($lang instanceof Parameter) {
				return $lang->getValue();
			}
		}
		return $default;
	}

}
