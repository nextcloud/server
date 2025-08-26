<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCP\Contacts\Events\ContactInteractedWithEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;
use Throwable;
use function strlen;
use function substr;

/** @template-implements IEventListener<CalendarObjectCreatedEvent|CalendarObjectUpdatedEvent|CalendarShareUpdatedEvent> */
class CalendarContactInteractionListener implements IEventListener {
	private const URI_USERS = 'principals/users/';

	public function __construct(
		private IEventDispatcher $dispatcher,
		private IUserSession $userSession,
		private Principal $principalConnector,
		private IMailer $mailer,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (($user = $this->userSession->getUser()) === null) {
			// Without user context we can't do anything
			return;
		}

		if ($event instanceof CalendarObjectCreatedEvent || $event instanceof CalendarObjectUpdatedEvent) {
			// users: href => principal:principals/users/admin
			foreach ($event->getShares() as $share) {
				if (!isset($share['href'])) {
					continue;
				}
				$this->emitFromUri($share['href'], $user);
			}

			// emit interaction for email attendees as well
			if (isset($event->getObjectData()['calendardata'])) {
				try {
					$calendar = Reader::read($event->getObjectData()['calendardata']);
					if ($calendar->VEVENT) {
						foreach ($calendar->VEVENT as $calendarEvent) {
							$this->emitFromObject($calendarEvent, $user);
						}
					}
				} catch (Throwable $e) {
					$this->logger->warning('Could not read calendar data for interaction events: ' . $e->getMessage(), [
						'exception' => $e,
					]);
				}
			}
		}

		if ($event instanceof CalendarShareUpdatedEvent && !empty($event->getAdded())) {
			// group: href => principal:principals/groups/admin
			// users: href => principal:principals/users/admin
			foreach ($event->getAdded() as $added) {
				if (!isset($added['href'])) {
					// Nothing to work with
					continue;
				}
				$this->emitFromUri($added['href'], $user);
			}
		}
	}

	private function emitFromUri(string $uri, IUser $user): void {
		$principal = $this->principalConnector->findByUri(
			$uri,
			$this->principalConnector->getPrincipalPrefix()
		);
		if ($principal === null) {
			// Invalid principal
			return;
		}
		if (!str_starts_with($principal, self::URI_USERS)) {
			// Not a user principal
			return;
		}

		$uid = substr($principal, strlen(self::URI_USERS));
		$this->dispatcher->dispatchTyped(
			(new ContactInteractedWithEvent($user))->setUid($uid)
		);
	}

	private function emitFromObject(VEvent $vevent, IUser $user): void {
		if (!$vevent->ATTENDEE) {
			// Nothing left to do
			return;
		}

		foreach ($vevent->ATTENDEE as $attendee) {
			if (!($attendee instanceof Property)) {
				continue;
			}

			$cuType = $attendee->offsetGet('CUTYPE');
			if ($cuType instanceof Parameter && $cuType->getValue() !== 'INDIVIDUAL') {
				// Don't care about those
				continue;
			}

			$mailTo = $attendee->getValue();
			if (!str_starts_with($mailTo, 'mailto:')) {
				// Doesn't look like an email
				continue;
			}
			$email = substr($mailTo, strlen('mailto:'));
			if (!$this->mailer->validateMailAddress($email)) {
				// This really isn't a valid email
				continue;
			}

			$this->dispatcher->dispatchTyped(
				(new ContactInteractedWithEvent($user))->setEmail($email)
			);
		}
	}
}
