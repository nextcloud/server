<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2007-2015 fruux GmbH (https://fruux.com/)
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\CalDAV\Schedule;

use OCA\DAV\CalDAV\CalendarObject;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Mail\IEmailValidator;
use OCP\Mail\IMailer;
use OCP\Mail\Provider\Address;
use OCP\Mail\Provider\Attachment;
use OCP\Mail\Provider\IManager as IMailManager;
use OCP\Mail\Provider\IMessageSend;
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

	private ?VCalendar $vCalendar = null;
	public const MAX_DATE = '2038-01-01';
	public const METHOD_REQUEST = 'request';
	public const METHOD_REPLY = 'reply';
	public const METHOD_CANCEL = 'cancel';
	public const IMIP_INDENT = 15;

	public function __construct(
		private IAppConfig $config,
		private IMailer $mailer,
		private LoggerInterface $logger,
		private ITimeFactory $timeFactory,
		private Defaults $defaults,
		private IUserSession $userSession,
		private IMipService $imipService,
		private IMailManager $mailManager,
		private IEmailValidator $emailValidator,
		private IAccountManager $accountManager,
	) {
		parent::__construct('');
	}

	#[\Override]
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
		if (!$node instanceof CalendarObject) {
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
	#[\Override]
	public function schedule(Message $iTipMessage) {

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
		if (!$this->emailValidator->isValid($recipient)) {
			// Nothing to send if the recipient doesn't have a valid email address
			$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
			return;
		}

		// Check if external attendees are disabled
		$externalAttendeesDisabled = $this->config->getValueBool('dav', 'caldav_external_attendees_disabled', false);
		if ($externalAttendeesDisabled && !$this->imipService->isSystemUser($recipient)) {
			$this->logger->debug('Invitation not sent to external attendee (external attendees disabled)', [
				'uid' => $iTipMessage->uid,
				'attendee' => $recipient,
			]);
			$iTipMessage->scheduleStatus = '5.0; External attendees are disabled';
			return;
		}

		$recipientName = $iTipMessage->recipientName ? (string)$iTipMessage->recipientName : null;

		$newObjects = $iTipMessage->message;
		$oldObjects = $this->getVCalendar();

		$method = match (strtolower($iTipMessage->method)) {
			'reply' => self::METHOD_REPLY,
			'cancel' => self::METHOD_CANCEL,
			default => self::METHOD_REQUEST,
		};

		// For REQUEST method, we need to determine which instances have changed by comparing new and old events
		if ($method === self::METHOD_REQUEST) {
			$newEvents = $this->imipService->eventInstances($newObjects);
			$oldEventsByInstance = [];
			if ($oldObjects !== null) {
				foreach ($this->imipService->eventInstances($oldObjects) as $oldEvent) {
					$oldEventsByInstance[$this->imipService->instanceKey($oldEvent)] = $oldEvent;
				}
			}

			$allInstances = array_map(
				fn (VEvent $newEvent) => ['new' => $newEvent, 'old' => $oldEventsByInstance[$this->imipService->instanceKey($newEvent)] ?? null],
				$newEvents,
			);

			$changedInstances = array_values(array_filter(
				$allInstances,
				fn (array $pair) => $pair['old'] === null || $this->imipService->diffInstance($pair['new'], $pair['old']) !== [],
			));

			$modifiedInstances = $changedInstances !== [] ? $changedInstances : $allInstances;
		}
		// For CANCEL/REPLY only ever consume the new instance, so there's no old instance to diff against or compare
		else {
			$modifiedInstances = array_map(
				static fn (VEvent $event) => ['new' => $event, 'old' => null],
				$this->imipService->eventInstances($newObjects),
			);
		}

		// No VEvents in the message at all - this shouldn't happen if there is significant change yet here we are
		// The scheduling status is debatable
		if (empty($modifiedInstances)) {
			$this->logger->warning('iTip message said the change was significant but the message contained no VEvents');
			$iTipMessage->scheduleStatus = '1.0;We got the message, but it\'s not significant enough to warrant an email';
			return;
		}

		if (count($modifiedInstances) > 1) {
			$this->logger->debug('iTip message contains multiple instances; only the primary instance is reflected in the invitation email', [
				'uid' => $iTipMessage->uid,
				'instanceCount' => count($modifiedInstances),
			]);
		}

		// we (should) have one instance per message, as the ITip\Broker creates
		// one iTip message per attendee and triggers the "schedule" event once
		// per message; a message can still bundle several instances (e.g.
		// primary + overrides edited together), in which case only the primary
		// instance is reflected in the email for now
		$primaryInstance = null;
		foreach ($modifiedInstances as $instance) {
			if (!isset($instance['new']->{'RECURRENCE-ID'})) {
				$primaryInstance = $instance;
				break;
			}
		}
		$primaryInstance ??= $modifiedInstances[0];

		/** @var VEvent $vEvent */
		$vEvent = $primaryInstance['new'];
		/** @var VEvent|null $oldVevent */
		$oldVevent = $primaryInstance['old'];

		// we might not have an old event as this could be a new invitation,
		// or a new recurrence exception
		$attendee = $this->imipService->getCurrentAttendee($iTipMessage);
		if ($attendee === null) {
			$uid = $vEvent->UID ?? 'no UID found';
			$this->logger->debug('Could not find recipient ' . $recipient . ' as attendee for event with UID ' . $uid);
			$iTipMessage->scheduleStatus = '5.0; EMail delivery failed';
			return;
		}
		// Don't send emails to rooms, resources and circles
		if ($this->imipService->isRoomOrResource($attendee)
				|| $this->imipService->isCircle($attendee)) {
			$this->logger->debug('No invitation sent as recipient is room, resource or circle', [
				'attendee' => $recipient,
			]);
			$iTipMessage->scheduleStatus = '1.0;We got the message, but it\'s not significant enough to warrant an email';
			return;
		}
		$this->imipService->setL10nFromAttendee($attendee);

		$sender = substr($iTipMessage->sender, 7);

		// Due to a bug in sabre, the senderName property for an iTIP message can actually also be a VObject Property
		if (($iTipMessage->senderName instanceof Parameter) && !empty(trim($iTipMessage->senderName->getValue()))) {
			$senderName = trim($iTipMessage->senderName->getValue());
		} elseif (is_string($iTipMessage->senderName) && !empty(trim($iTipMessage->senderName))) {
			$senderName = trim($iTipMessage->senderName);
		} else {
			$senderName = $this->getSenderNameFor($sender);
		}

		$template = match ($method) {
			self::METHOD_REPLY => $this->imipService->buildReplyEmail($iTipMessage, $vEvent, $recipient, $recipientName, $sender, $senderName),
			self::METHOD_CANCEL => $this->imipService->buildCancellationEmail($vEvent, $recipient, $recipientName, $sender, $senderName),
			default => $this->imipService->buildRequestEmail($iTipMessage, $vEvent, $oldVevent, $attendee, $recipient, $recipientName, $sender, $senderName, $lastOccurrence),
		};

		$fromEMail = Util::getDefaultEmailAddress('invitations-noreply');
		$fromName = $this->imipService->getFrom($senderName, $this->defaults->getName());

		// convert iTip Message to string
		$itip_msg = $iTipMessage->message->serialize();

		$mailService = null;

		try {
			if ($this->config->getValueBool('core', 'mail_providers_enabled', true)) {
				// retrieve user object
				$user = $this->userSession->getUser();
				if ($user !== null) {
					// retrieve appropriate service with the same address as sender
					$mailService = $this->mailManager->findServiceByAddress($user->getUID(), $sender);
				}
			}

			// The display name in Nextcloud can use utf-8.
			// As the default charset for text/* is us-ascii, it's important to explicitly define it.
			// See https://www.rfc-editor.org/rfc/rfc6047.html#section-2.4.
			$contentType = 'text/calendar; method=' . $iTipMessage->method . '; charset="utf-8"';

			// evaluate if a mail service was found and has sending capabilities
			if ($mailService instanceof IMessageSend) {
				// construct mail message and set required parameters
				$message = $mailService->initiateMessage();
				$message->setFrom(
					(new Address($sender, $fromName))
				);
				$message->setTo(
					(new Address($recipient, $recipientName))
				);
				$message->setSubject($template->renderSubject());
				$message->setBodyPlain($template->renderText());
				$message->setBodyHtml($template->renderHtml());
				// Adding name=event.ics is a trick to make the invitation also appear
				// as a file attachment in mail clients like Thunderbird or Evolution.
				$message->setAttachments((new Attachment(
					$itip_msg,
					null,
					$contentType . '; name=event.ics',
					true
				)));
				// send message
				$mailService->sendMessage($message);
			} else {
				// construct symfony mailer message and set required parameters
				$message = $this->mailer->createMessage();
				$message->setFrom([$fromEMail => $fromName]);
				$message->setTo(
					(($recipientName !== null) ? [$recipient => $recipientName] : [$recipient])
				);
				$message->setReplyTo(
					(($senderName !== null) ? [$sender => $senderName] : [$sender])
				);
				$message->useTemplate($template);
				// Using a different content type because Symfony Mailer/Mime will append the name to
				// the content type header and attachInline does not allow null.
				$message->attachInline(
					$itip_msg,
					'event.ics',
					$contentType,
				);
				$failed = $this->mailer->send($message);
			}

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
	 * Messages are regularly brokered on behalf of somebody else, so the
	 * session user's name is only used when the sender address is one of
	 * theirs.
	 */
	private function getSenderNameFor(string $sender): ?string {
		$user = $this->userSession->getUser();
		if ($user !== null && $this->isAddressOfUser($sender, $user)) {
			return trim($user->getDisplayName()) ?: null;
		}

		return null;
	}

	/**
	 * Profile email addresses are part of the user's calendar-user-address-set
	 * and therefore valid sender addresses next to the system email address.
	 */
	private function isAddressOfUser(string $address, IUser $user): bool {
		if (strcasecmp((string)$user->getEMailAddress(), $address) === 0) {
			return true;
		}

		$emailCollection = $this->accountManager->getAccount($user)
			->getPropertyCollection(IAccountManager::COLLECTION_EMAIL);
		foreach ($emailCollection->getProperties() as $property) {
			if (strcasecmp($property->getValue(), $address) === 0) {
				return true;
			}
		}

		return false;
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
