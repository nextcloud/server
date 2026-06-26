<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CalDAV\Schedule\IMipService;
use OCP\Calendar\Events\CalendarObjectCreatedEvent;
use OCP\Calendar\Events\CalendarObjectDeletedEvent;
use OCP\Calendar\Events\CalendarObjectMovedToTrashEvent;
use OCP\Calendar\Events\CalendarObjectRestoredEvent;
use OCP\Calendar\Events\CalendarObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Mail\IMailer;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;
use Throwable;

/**
 * Sends an iMIP-style notification email to a calendar owner whenever one
 * of their calendar-proxy delegates creates, modifies, deletes, trashes,
 * or restores an event on their behalf.
 *
 * The email body is built with IMipService so the owner sees the same rich
 * bullet-list rendering used for regular invitations, including diff
 * strike-throughs on update.
 *
 * @template-implements IEventListener<CalendarObjectCreatedEvent|CalendarObjectUpdatedEvent|CalendarObjectDeletedEvent|CalendarObjectMovedToTrashEvent|CalendarObjectRestoredEvent>
 */
class CalendarDelegateActionListener implements IEventListener {

	private const ACTION_CREATE = 'create';
	private const ACTION_UPDATE = 'update';
	private const ACTION_DELETE = 'delete';
	private const ACTION_TRASH = 'trash';
	private const ACTION_RESTORE = 'restore';

	public function __construct(
		private readonly IUserSession $userSession,
		private readonly IUserManager $userManager,
		private readonly ProxyMapper $proxyMapper,
		private readonly IMailer $mailer,
		private readonly IL10NFactory $l10nFactory,
		private readonly IMipService $imipService,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		$action = match (true) {
			$event instanceof CalendarObjectCreatedEvent => self::ACTION_CREATE,
			$event instanceof CalendarObjectUpdatedEvent => self::ACTION_UPDATE,
			$event instanceof CalendarObjectDeletedEvent => self::ACTION_DELETE,
			$event instanceof CalendarObjectMovedToTrashEvent => self::ACTION_TRASH,
			$event instanceof CalendarObjectRestoredEvent => self::ACTION_RESTORE,
			default => null,
		};
		if ($action === null) {
			return;
		}

		$actor = $this->userSession->getUser();
		if ($actor === null) {
			return;
		}

		$calendarInfo = $event->getCalendarData();
		$ownerPrincipalUri = $calendarInfo['principaluri'] ?? null;
		if (!is_string($ownerPrincipalUri) || !str_starts_with($ownerPrincipalUri, 'principals/users/')) {
			return;
		}

		[, $ownerUid] = \Sabre\Uri\split($ownerPrincipalUri);
		if ($ownerUid === $actor->getUID()) {
			return;
		}

		if (!$this->actorIsProxyOf($actor->getUID(), $ownerPrincipalUri)) {
			return;
		}

		$owner = $this->userManager->get($ownerUid);
		if ($owner === null) {
			return;
		}
		$ownerEmail = $owner->getEMailAddress();
		if ($ownerEmail === null || $ownerEmail === '') {
			return;
		}

		// Only an update carries a meaningful previous version to diff against.
		$oldObjectData = $event instanceof CalendarObjectUpdatedEvent ? $event->getOldObjectData() : [];

		try {
			$this->sendNotification($action, $actor, $owner, $ownerEmail, $calendarInfo, $event->getObjectData(), $oldObjectData);
		} catch (Throwable $e) {
			$this->logger->warning('Could not send delegate-action notification to calendar owner', [
				'app' => 'dav',
				'owner' => $ownerUid,
				'actor' => $actor->getUID(),
				'action' => $action,
				'exception' => $e,
			]);
		}
	}

	private function actorIsProxyOf(string $actorUid, string $ownerPrincipalUri): bool {
		$actorPrincipalUri = 'principals/users/' . $actorUid;
		foreach ($this->proxyMapper->getProxiesOf($ownerPrincipalUri) as $proxy) {
			if ($proxy->getProxyId() === $actorPrincipalUri) {
				return true;
			}
		}
		return false;
	}

	private function sendNotification(
		string $action,
		IUser $actor,
		IUser $owner,
		string $ownerEmail,
		array $calendarInfo,
		array $objectData,
		array $oldObjectData,
	): void {
		$l = $this->l10nFactory->get('dav', $this->l10nFactory->getUserLanguage($owner));

		$newVCalendar = $this->readVCalendar($objectData['calendardata'] ?? null);
		$newVEvent = $this->firstVEvent($newVCalendar);
		if ($newVEvent === null) {
			// Without a VEVENT there is nothing meaningful to describe.
			return;
		}

		$oldVCalendar = $this->readVCalendar($oldObjectData['calendardata'] ?? null);
		$oldVEvent = $this->firstVEvent($oldVCalendar);

		$actorName = $actor->getDisplayName() ?: $actor->getUID();
		$calendarName = (string)($calendarInfo['{DAV:}displayname'] ?? $calendarInfo['uri'] ?? 'calendar');

		// Build the same data payload IMipPlugin uses, so addBulletList renders
		// the familiar title/when/location/url/description list — with diff
		// strikethroughs when an old version is available.
		$isCancellation = $action === self::ACTION_DELETE || $action === self::ACTION_TRASH;
		$data = $isCancellation
			? $this->imipService->buildCancelledBodyData($newVEvent)
			: $this->imipService->buildBodyData($newVEvent, $action === self::ACTION_UPDATE ? $oldVEvent : null);

		$summary = (string)($newVEvent->SUMMARY ?? $l->t('Untitled event'));

		[$subject, $heading] = $this->subjectAndHeading($l, $action, $actorName, $summary, $calendarName);

		$template = $this->mailer->createEMailTemplate('dav.delegateAction.' . $action, [
			'actor' => $actorName,
			'calendar' => $calendarName,
			'event' => $summary,
		]);
		$template->addHeader();
		$template->setSubject($subject);
		$template->addHeading($heading);

		// Attribution row (who did it, on which calendar) — sits above the
		// event details so the owner immediately sees the responsible delegate.
		$template->addBodyListItem($actorName, $l->t('Delegate:'));
		$template->addBodyListItem($calendarName, $l->t('Calendar:'));

		$this->imipService->addBulletList($template, $newVEvent, $data);

		$template->addFooter();

		$message = $this->mailer->createMessage();
		$message->setFrom([Util::getDefaultEmailAddress('invitations-noreply') => $actorName]);
		$message->setTo([$ownerEmail => $owner->getDisplayName() ?: $owner->getUID()]);
		$message->setSubject($subject);
		$message->useTemplate($template);

		// Attach the raw iCalendar so the owner's client can pick up the change.
		if ($action !== self::ACTION_DELETE) {
			$calendarData = $objectData['calendardata'] ?? null;
			if (is_resource($calendarData)) {
				$calendarData = stream_get_contents($calendarData);
			}
			if (is_string($calendarData) && $calendarData !== '') {
				$message->attachInline(
					$calendarData,
					'event.ics',
					'text/calendar; charset="utf-8"',
				);
			}
		}

		$this->mailer->send($message);
	}

	/**
	 * @return array{0: string, 1: string} [subject, heading]
	 */
	private function subjectAndHeading(\OCP\IL10N $l, string $action, string $actorName, string $summary, string $calendarName): array {
		return match ($action) {
			self::ACTION_CREATE => [
				$l->t('%1$s created "%2$s" on your behalf', [$actorName, $summary]),
				$l->t('%1$s created "%2$s" on your calendar "%3$s"', [$actorName, $summary, $calendarName]),
			],
			self::ACTION_UPDATE => [
				$l->t('%1$s updated "%2$s" on your behalf', [$actorName, $summary]),
				$l->t('%1$s updated "%2$s" on your calendar "%3$s"', [$actorName, $summary, $calendarName]),
			],
			self::ACTION_DELETE => [
				$l->t('%1$s deleted "%2$s" on your behalf', [$actorName, $summary]),
				$l->t('%1$s permanently deleted "%2$s" from your calendar "%3$s"', [$actorName, $summary, $calendarName]),
			],
			self::ACTION_TRASH => [
				$l->t('%1$s moved "%2$s" to the trash on your behalf', [$actorName, $summary]),
				$l->t('%1$s moved "%2$s" to the trash on your calendar "%3$s"', [$actorName, $summary, $calendarName]),
			],
			self::ACTION_RESTORE => [
				$l->t('%1$s restored "%2$s" on your behalf', [$actorName, $summary]),
				$l->t('%1$s restored "%2$s" on your calendar "%3$s"', [$actorName, $summary, $calendarName]),
			],
		};
	}

	private function readVCalendar(mixed $calendarData): ?VCalendar {
		if (is_resource($calendarData)) {
			$calendarData = stream_get_contents($calendarData);
		}
		if (!is_string($calendarData) || $calendarData === '') {
			return null;
		}
		try {
			$vCalendar = Reader::read($calendarData);
		} catch (Throwable) {
			return null;
		}
		return $vCalendar instanceof VCalendar ? $vCalendar : null;
	}

	private function firstVEvent(?VCalendar $vCalendar): ?VEvent {
		if ($vCalendar === null) {
			return null;
		}
		foreach ($vCalendar->VEVENT ?? [] as $vEvent) {
			if ($vEvent instanceof VEvent) {
				return $vEvent;
			}
		}
		return null;
	}
}
