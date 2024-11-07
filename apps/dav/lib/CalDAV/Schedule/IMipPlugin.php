<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2017, Georg Ehrke
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author brad2014 <brad2014@users.noreply.github.com>
 * @author Brad Rubenstein <brad@wbr.tech>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Leon Klingele <leon@struktur.de>
 * @author Nick Sweeting <git@sweeting.me>
 * @author rakekniven <mark.ziegler@rakekniven.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Anna Larch <anna.larch@gmx.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV\Schedule;

use OCA\DAV\CalDAV\CalendarObject;
use OCA\DAV\CalDAV\EventComparisonService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Schedule\IMipPlugin as SabreIMipPlugin;
use Sabre\DAV;
use Sabre\DAV\INode;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Parameter;
use Sabre\VObject\Reader;

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
	private IUserSession $userSession;
	private IConfig $config;
	private IMailer $mailer;
	private LoggerInterface $logger;
	private ITimeFactory $timeFactory;
	private Defaults $defaults;
	private ?VCalendar $vCalendar = null;
	private IMipService $imipService;
	public const MAX_DATE = '2038-01-01';
	public const METHOD_REQUEST = 'request';
	public const METHOD_REPLY = 'reply';
	public const METHOD_CANCEL = 'cancel';
	public const IMIP_INDENT = 15; // Enough for the length of all body bullet items, in all languages
	private EventComparisonService $eventComparisonService;

	public function __construct(IConfig $config,
		IMailer $mailer,
		LoggerInterface $logger,
		ITimeFactory $timeFactory,
		Defaults $defaults,
		IUserSession $userSession,
		IMipService $imipService,
		EventComparisonService $eventComparisonService) {
		parent::__construct('');
		$this->userSession = $userSession;
		$this->config = $config;
		$this->mailer = $mailer;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
		$this->defaults = $defaults;
		$this->imipService = $imipService;
		$this->eventComparisonService = $eventComparisonService;
	}

	public function initialize(DAV\Server $server): void {
		parent::initialize($server);
		$server->on('beforeWriteContent', [$this, 'beforeWriteContent'], 10);
	}

	/**
	 * Check quota before writing content
	 *
	 * @param string $uri target file URI
	 * @param INode $node Sabre Node
	 * @param resource $data data
	 * @param bool $modified modified
	 */
	public function beforeWriteContent($uri, INode $node, $data, $modified): void {
		if(!$node instanceof CalendarObject) {
			return;
		}
		/** @var VCalendar $vCalendar */
		$vCalendar = Reader::read($node->get());
		$this->setVCalendar($vCalendar);
	}

	/**
	 * Event handler for the 'schedule' event.
	 *
	 * @param Message $iTipMessage
	 * @return void
	 */
	public function schedule(Message $iTipMessage) {

		// do not send imip messages if external system already did
		/** @psalm-suppress UndefinedPropertyFetch */
		if ($iTipMessage->message?->VEVENT?->{'X-NC-DISABLE-SCHEDULING'}?->getValue() === 'true') {
			if (!$iTipMessage->scheduleStatus) {
				$iTipMessage->scheduleStatus = '1.0;We got the message, but iMip messages are disabled for this event';
			}
			return;
		}
		// Not sending any emails if the system considers the update insignificant
		if (!$iTipMessage->significantChange) {
			if (!$iTipMessage->scheduleStatus) {
				$iTipMessage->scheduleStatus = '1.0;We got the message, but it\'s not significant enough to warrant an email';
			}
			return;
		}

		if (parse_url($iTipMessage->sender, PHP_URL_SCHEME) !== 'mailto'
			|| parse_url($iTipMessage->recipient, PHP_URL_SCHEME) !== 'mailto') {
			return;
		}

		// don't send out mails for events that already took place
		$lastOccurrence = $this->imipService->getLastOccurrence($iTipMessage->message);
		$currentTime = $this->timeFactory->getTime();
		if ($lastOccurrence < $currentTime) {
			return;
		}

		// Strip off mailto:
		$recipient = substr($iTipMessage->recipient, 7);
		if (!$this->mailer->validateMailAddress($recipient)) {
			// Nothing to send if the recipient doesn't have a valid email address
			$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
			return;
		}
		$recipientName = $iTipMessage->recipientName ? (string)$iTipMessage->recipientName : null;

		$newEvents = $iTipMessage->message;
		$oldEvents = $this->getVCalendar();

		$modified = $this->eventComparisonService->findModified($newEvents, $oldEvents);
		/** @var VEvent $vEvent */
		$vEvent = array_pop($modified['new']);
		/** @var VEvent $oldVevent */
		$oldVevent = !empty($modified['old']) && is_array($modified['old']) ? array_pop($modified['old']) : null;
		$isModified = isset($oldVevent);

		// No changed events after all - this shouldn't happen if there is significant change yet here we are
		// The scheduling status is debatable
		if(empty($vEvent)) {
			$this->logger->warning('iTip message said the change was significant but comparison did not detect any updated VEvents');
			$iTipMessage->scheduleStatus = '1.0;We got the message, but it\'s not significant enough to warrant an email';
			return;
		}

		// we (should) have one event component left
		// as the ITip\Broker creates one iTip message per change
		// and triggers the "schedule" event once per message
		// we also might not have an old event as this could be a new
		// invitation, or a new recurrence exception
		$attendee = $this->imipService->getCurrentAttendee($iTipMessage);
		if($attendee === null) {
			$uid = $vEvent->UID ?? 'no UID found';
			$this->logger->debug('Could not find recipient ' . $recipient . ' as attendee for event with UID ' . $uid);
			$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
			return;
		}
		// Don't send emails to things
		if($this->imipService->isRoomOrResource($attendee)) {
			$this->logger->debug('No invitation sent as recipient is room or resource', [
				'attendee' => $recipient,
			]);
			$iTipMessage->scheduleStatus = '1.0;We got the message, but it\'s not significant enough to warrant an email';
			return;
		}
		$this->imipService->setL10n($attendee);

		// Build the sender name.
		// Due to a bug in sabre, the senderName property for an iTIP message can actually also be a VObject Property
		// If the iTIP message senderName is null or empty use the user session name as the senderName
		if (($iTipMessage->senderName instanceof Parameter) && !empty(trim($iTipMessage->senderName->getValue()))) {
			$senderName = trim($iTipMessage->senderName->getValue());
		} elseif (is_string($iTipMessage->senderName) && !empty(trim($iTipMessage->senderName))) {
			$senderName = trim($iTipMessage->senderName);
		} elseif ($this->userSession->getUser() !== null) {
			$senderName = trim($this->userSession->getUser()->getDisplayName());
		} else {
			$senderName = '';
		}

		$sender = substr($iTipMessage->sender, 7);

		$replyingAttendee = null;
		switch (strtolower($iTipMessage->method)) {
			case self::METHOD_REPLY:
				$method = self::METHOD_REPLY;
				$data = $this->imipService->buildBodyData($vEvent, $oldVevent);
				$replyingAttendee = $this->imipService->getReplyingAttendee($iTipMessage);
				break;
			case self::METHOD_CANCEL:
				$method = self::METHOD_CANCEL;
				$data = $this->imipService->buildCancelledBodyData($vEvent);
				break;
			default:
				$method = self::METHOD_REQUEST;
				$data = $this->imipService->buildBodyData($vEvent, $oldVevent);
				break;
		}

		$data['attendee_name'] = ($recipientName ?: $recipient);
		$data['invitee_name'] = ($senderName ?: $sender);

		$fromEMail = Util::getDefaultEmailAddress('invitations-noreply');
		$fromName = $this->imipService->getFrom($senderName, $this->defaults->getName());

		$message = $this->mailer->createMessage()
			->setFrom([$fromEMail => $fromName]);

		if ($recipientName !== null) {
			$message->setTo([$recipient => $recipientName]);
		} else {
			$message->setTo([$recipient]);
		}

		if ($senderName !== null) {
			$message->setReplyTo([$sender => $senderName]);
		} else {
			$message->setReplyTo([$sender]);
		}

		$template = $this->mailer->createEMailTemplate('dav.calendarInvite.' . $method, $data);
		$template->addHeader();

		$this->imipService->addSubjectAndHeading($template, $method, $data['invitee_name'], $data['meeting_title'], $isModified, $replyingAttendee);
		$this->imipService->addBulletList($template, $vEvent, $data);

		// Only add response buttons to invitation requests: Fix Issue #11230
		if (strcasecmp($method, self::METHOD_REQUEST) === 0 && $this->imipService->getAttendeeRsvpOrReqForParticipant($attendee)) {

			/*
			** Only offer invitation accept/reject buttons, which link back to the
			** nextcloud server, to recipients who can access the nextcloud server via
			** their internet/intranet.  Issue #12156
			**
			** The app setting is stored in the appconfig database table.
			**
			** For nextcloud servers accessible to the public internet, the default
			** "invitation_link_recipients" value "yes" (all recipients) is appropriate.
			**
			** When the nextcloud server is restricted behind a firewall, accessible
			** only via an internal network or via vpn, you can set "dav.invitation_link_recipients"
			** to the email address or email domain, or comma separated list of addresses or domains,
			** of recipients who can access the server.
			**
			** To always deliver URLs, set invitation_link_recipients to "yes".
			** To suppress URLs entirely, set invitation_link_recipients to boolean "no".
			*/

			$recipientDomain = substr(strrchr($recipient, '@'), 1);
			$invitationLinkRecipients = explode(',', preg_replace('/\s+/', '', strtolower($this->config->getAppValue('dav', 'invitation_link_recipients', 'yes'))));

			if (strcmp('yes', $invitationLinkRecipients[0]) === 0
				|| in_array(strtolower($recipient), $invitationLinkRecipients)
				|| in_array(strtolower($recipientDomain), $invitationLinkRecipients)) {
				$token = $this->imipService->createInvitationToken($iTipMessage, $vEvent, $lastOccurrence);
				$this->imipService->addResponseButtons($template, $token);
				$this->imipService->addMoreOptionsButton($template, $token);
			}
		}

		$template->addFooter();

		$message->useTemplate($template);

		$itip_msg = $iTipMessage->message->serialize();
		$message->attachInline(
			$itip_msg,
			'event.ics',
			'text/calendar; method=' . $iTipMessage->method,
		);

		try {
			$failed = $this->mailer->send($message);
			$iTipMessage->scheduleStatus = '1.1; Scheduling message is sent via iMip';
			if (!empty($failed)) {
				$this->logger->error('Unable to deliver message to {failed}', ['app' => 'dav', 'failed' => implode(', ', $failed)]);
				$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
			}
		} catch (\Exception $ex) {
			$this->logger->error($ex->getMessage(), ['app' => 'dav', 'exception' => $ex]);
			$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
		}
	}

	/**
	 * @return ?VCalendar
	 */
	public function getVCalendar(): ?VCalendar {
		return $this->vCalendar;
	}

	/**
	 * @param ?VCalendar $vCalendar
	 */
	public function setVCalendar(?VCalendar $vCalendar): void {
		$this->vCalendar = $vCalendar;
	}

}
