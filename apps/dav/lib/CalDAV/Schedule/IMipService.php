<?php

declare(strict_types=1);
/*
 * DAV App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DAV\CalDAV\Schedule;

use OC\URLGenerator;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\L10N\IFactory as L10NFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Security\ISecureRandom;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property;
use Sabre\VObject\Recur\EventIterator;

class IMipService {

	private URLGenerator $urlGenerator;
	private IConfig $config;
	private IDBConnection $db;
	private ISecureRandom $random;
	private L10NFactory $l10nFactory;
	private IL10N $l10n;

	/** @var string[] */
	private const STRING_DIFF = [
		'meeting_title' => 'SUMMARY',
		'meeting_description' => 'DESCRIPTION',
		'meeting_url' => 'URL',
		'meeting_location' => 'LOCATION'
	];

	public function __construct(URLGenerator $urlGenerator,
		IConfig $config,
		IDBConnection $db,
		ISecureRandom $random,
		L10NFactory $l10nFactory) {
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->db = $db;
		$this->random = $random;
		$this->l10nFactory = $l10nFactory;
		$default = $this->l10nFactory->findGenericLanguage();
		$this->l10n = $this->l10nFactory->get('dav', $default);
	}

	/**
	 * @param string|null $senderName
	 * @param string $default
	 * @return string
	 */
	public function getFrom(?string $senderName, string $default): string {
		if ($senderName === null) {
			return $default;
		}

		return $this->l10n->t('%1$s via %2$s', [$senderName, $default]);
	}

	public static function readPropertyWithDefault(VEvent $vevent, string $property, string $default) {
		if (isset($vevent->$property)) {
			$value = $vevent->$property->getValue();
			if (!empty($value)) {
				return $value;
			}
		}
		return $default;
	}

	private function generateDiffString(VEvent $vevent, VEvent $oldVEvent, string $property, string $default): ?string {
		$strikethrough = "<span style='text-decoration: line-through'>%s</span><br />%s";
		if (!isset($vevent->$property)) {
			return $default;
		}
		$newstring = $vevent->$property->getValue();
		if(isset($oldVEvent->$property) && $oldVEvent->$property->getValue() !== $newstring) {
			$oldstring = $oldVEvent->$property->getValue();
			return sprintf($strikethrough, $oldstring, $newstring);
		}
		return $newstring;
	}

	/**
	 * Like generateDiffString() but linkifies the property values if they are urls.
	 */
	private function generateLinkifiedDiffString(VEvent $vevent, VEvent $oldVEvent, string $property, string $default): ?string {
		if (!isset($vevent->$property)) {
			return $default;
		}
		/** @var string|null $newString */
		$newString = $vevent->$property->getValue();
		$oldString = isset($oldVEvent->$property) ? $oldVEvent->$property->getValue() : null;
		if ($oldString !== $newString) {
			return sprintf(
				"<span style='text-decoration: line-through'>%s</span><br />%s",
				$this->linkify($oldString) ?? $oldString ?? '',
				$this->linkify($newString) ?? $newString ?? ''
			);
		}
		return $this->linkify($newString) ?? $newString;
	}

	/**
	 * Convert a given url to a html link element or return null otherwise.
	 */
	private function linkify(?string $url): ?string {
		if ($url === null) {
			return null;
		}
		if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
			return null;
		}

		return sprintf('<a href="%1$s">%1$s</a>', htmlspecialchars($url));
	}

	/**
	 * @param VEvent $vEvent
	 * @param VEvent|null $oldVEvent
	 * @return array
	 */
	public function buildBodyData(VEvent $vEvent, ?VEvent $oldVEvent): array {
		$defaultVal = '';
		$data = [];
		$data['meeting_when'] = $this->generateWhenString($vEvent);

		foreach(self::STRING_DIFF as $key => $property) {
			$data[$key] = self::readPropertyWithDefault($vEvent, $property, $defaultVal);
		}

		$data['meeting_url_html'] = self::readPropertyWithDefault($vEvent, 'URL', $defaultVal);

		if (($locationHtml = $this->linkify($data['meeting_location'])) !== null) {
			$data['meeting_location_html'] = $locationHtml;
		}

		if(!empty($oldVEvent)) {
			$oldMeetingWhen = $this->generateWhenString($oldVEvent);
			$data['meeting_title_html'] = $this->generateDiffString($vEvent, $oldVEvent, 'SUMMARY', $data['meeting_title']);
			$data['meeting_description_html'] = $this->generateDiffString($vEvent, $oldVEvent, 'DESCRIPTION', $data['meeting_description']);
			$data['meeting_location_html'] = $this->generateLinkifiedDiffString($vEvent, $oldVEvent, 'LOCATION', $data['meeting_location']);

			$oldUrl = self::readPropertyWithDefault($oldVEvent, 'URL', $defaultVal);
			$data['meeting_url_html'] = !empty($oldUrl) && $oldUrl !== $data['meeting_url'] ? sprintf('<a href="%1$s">%1$s</a>', $oldUrl) : $data['meeting_url'];

			$data['meeting_when_html'] =
				($oldMeetingWhen !== $data['meeting_when'] && $oldMeetingWhen !== null)
					? sprintf("<span style='text-decoration: line-through'>%s</span><br />%s", $oldMeetingWhen, $data['meeting_when'])
					: $data['meeting_when'];
		}
		return $data;
	}

	/**
	 * @param IL10N $this->l10n
	 * @param VEvent $vevent
	 * @return false|int|string
	 */
	public function generateWhenString(VEvent $vevent) {
		/** @var Property\ICalendar\DateTime $dtstart */
		$dtstart = $vevent->DTSTART;
		if (isset($vevent->DTEND)) {
			/** @var Property\ICalendar\DateTime $dtend */
			$dtend = $vevent->DTEND;
		} elseif (isset($vevent->DURATION)) {
			$isFloating = $dtstart->isFloating();
			$dtend = clone $dtstart;
			$endDateTime = $dtend->getDateTime();
			$endDateTime = $endDateTime->add(DateTimeParser::parse($vevent->DURATION->getValue()));
			$dtend->setDateTime($endDateTime, $isFloating);
		} elseif (!$dtstart->hasTime()) {
			$isFloating = $dtstart->isFloating();
			$dtend = clone $dtstart;
			$endDateTime = $dtend->getDateTime();
			$endDateTime = $endDateTime->modify('+1 day');
			$dtend->setDateTime($endDateTime, $isFloating);
		} else {
			$dtend = clone $dtstart;
		}

		/** @var Property\ICalendar\Date | Property\ICalendar\DateTime $dtstart */
		/** @var \DateTimeImmutable $dtstartDt */
		$dtstartDt = $dtstart->getDateTime();

		/** @var Property\ICalendar\Date | Property\ICalendar\DateTime $dtend */
		/** @var \DateTimeImmutable $dtendDt */
		$dtendDt = $dtend->getDateTime();

		$diff = $dtstartDt->diff($dtendDt);

		$dtstartDt = new \DateTime($dtstartDt->format(\DateTimeInterface::ATOM));
		$dtendDt = new \DateTime($dtendDt->format(\DateTimeInterface::ATOM));

		if ($dtstart instanceof Property\ICalendar\Date) {
			// One day event
			if ($diff->days === 1) {
				return $this->l10n->l('date', $dtstartDt, ['width' => 'medium']);
			}

			// DTEND is exclusive, so if the ics data says 2020-01-01 to 2020-01-05,
			// the email should show 2020-01-01 to 2020-01-04.
			$dtendDt->modify('-1 day');

			//event that spans over multiple days
			$localeStart = $this->l10n->l('date', $dtstartDt, ['width' => 'medium']);
			$localeEnd = $this->l10n->l('date', $dtendDt, ['width' => 'medium']);

			return $localeStart . ' - ' . $localeEnd;
		}

		/** @var Property\ICalendar\DateTime $dtstart */
		/** @var Property\ICalendar\DateTime $dtend */
		$isFloating = $dtstart->isFloating();
		$startTimezone = $endTimezone = null;
		if (!$isFloating) {
			$prop = $dtstart->offsetGet('TZID');
			if ($prop instanceof Parameter) {
				$startTimezone = $prop->getValue();
			}

			$prop = $dtend->offsetGet('TZID');
			if ($prop instanceof Parameter) {
				$endTimezone = $prop->getValue();
			}
		}

		$localeStart = $this->l10n->l('weekdayName', $dtstartDt, ['width' => 'abbreviated']) . ', ' .
			$this->l10n->l('datetime', $dtstartDt, ['width' => 'medium|short']);

		// always show full date with timezone if timezones are different
		if ($startTimezone !== $endTimezone) {
			$localeEnd = $this->l10n->l('datetime', $dtendDt, ['width' => 'medium|short']);

			return $localeStart . ' (' . $startTimezone . ') - ' .
				$localeEnd . ' (' . $endTimezone . ')';
		}

		// show only end time if date is the same
		if ($dtstartDt->format('Y-m-d') === $dtendDt->format('Y-m-d')) {
			$localeEnd = $this->l10n->l('time', $dtendDt, ['width' => 'short']);
		} else {
			$localeEnd = $this->l10n->l('weekdayName', $dtendDt, ['width' => 'abbreviated']) . ', ' .
				$this->l10n->l('datetime', $dtendDt, ['width' => 'medium|short']);
		}

		return $localeStart . ' - ' . $localeEnd . ' (' . $startTimezone . ')';
	}

	/**
	 * @param VEvent $vEvent
	 * @return array
	 */
	public function buildCancelledBodyData(VEvent $vEvent): array {
		$defaultVal = '';
		$strikethrough = "<span style='text-decoration: line-through'>%s</span>";

		$newMeetingWhen = $this->generateWhenString($vEvent);
		$newSummary = isset($vEvent->SUMMARY) && (string)$vEvent->SUMMARY !== '' ? (string)$vEvent->SUMMARY : $this->l10n->t('Untitled event');
		;
		$newDescription = isset($vEvent->DESCRIPTION) && (string)$vEvent->DESCRIPTION !== '' ? (string)$vEvent->DESCRIPTION : $defaultVal;
		$newUrl = isset($vEvent->URL) && (string)$vEvent->URL !== '' ? sprintf('<a href="%1$s">%1$s</a>', $vEvent->URL) : $defaultVal;
		$newLocation = isset($vEvent->LOCATION) && (string)$vEvent->LOCATION !== '' ? (string)$vEvent->LOCATION : $defaultVal;
		$newLocationHtml = $this->linkify($newLocation) ?? $newLocation;

		$data = [];
		$data['meeting_when_html'] = $newMeetingWhen === '' ?: sprintf($strikethrough, $newMeetingWhen);
		$data['meeting_when'] = $newMeetingWhen;
		$data['meeting_title_html'] = sprintf($strikethrough, $newSummary);
		$data['meeting_title'] = $newSummary !== '' ? $newSummary: $this->l10n->t('Untitled event');
		$data['meeting_description_html'] = $newDescription !== '' ? sprintf($strikethrough, $newDescription) : '';
		$data['meeting_description'] = $newDescription;
		$data['meeting_url_html'] = $newUrl !== '' ? sprintf($strikethrough, $newUrl) : '';
		$data['meeting_url'] = isset($vEvent->URL) ? (string)$vEvent->URL : '';
		$data['meeting_location_html'] = $newLocationHtml !== '' ? sprintf($strikethrough, $newLocationHtml) : '';
		$data['meeting_location'] = $newLocation;
		return $data;
	}

	/**
	 * Check if event took place in the past
	 *
	 * @param VCalendar $vObject
	 * @return int
	 */
	public function getLastOccurrence(VCalendar $vObject) {
		/** @var VEvent $component */
		$component = $vObject->VEVENT;

		if (isset($component->RRULE)) {
			$it = new EventIterator($vObject, (string)$component->UID);
			$maxDate = new \DateTime(IMipPlugin::MAX_DATE);
			if ($it->isInfinite()) {
				return $maxDate->getTimestamp();
			}

			$end = $it->getDtEnd();
			while ($it->valid() && $end < $maxDate) {
				$end = $it->getDtEnd();
				$it->next();
			}
			return $end->getTimestamp();
		}

		/** @var Property\ICalendar\DateTime $dtStart */
		$dtStart = $component->DTSTART;

		if (isset($component->DTEND)) {
			/** @var Property\ICalendar\DateTime $dtEnd */
			$dtEnd = $component->DTEND;
			return $dtEnd->getDateTime()->getTimeStamp();
		}

		if(isset($component->DURATION)) {
			/** @var \DateTime $endDate */
			$endDate = clone $dtStart->getDateTime();
			// $component->DTEND->getDateTime() returns DateTimeImmutable
			$endDate = $endDate->add(DateTimeParser::parse($component->DURATION->getValue()));
			return $endDate->getTimestamp();
		}

		if(!$dtStart->hasTime()) {
			/** @var \DateTime $endDate */
			// $component->DTSTART->getDateTime() returns DateTimeImmutable
			$endDate = clone $dtStart->getDateTime();
			$endDate = $endDate->modify('+1 day');
			return $endDate->getTimestamp();
		}

		// No computation of end time possible - return start date
		return $dtStart->getDateTime()->getTimeStamp();
	}

	/**
	 * @param Property|null $attendee
	 */
	public function setL10n(?Property $attendee = null) {
		if($attendee === null) {
			return;
		}

		$lang = $attendee->offsetGet('LANGUAGE');
		if ($lang instanceof Parameter) {
			$lang = $lang->getValue();
			$this->l10n = $this->l10nFactory->get('dav', $lang);
		}
	}

	/**
	 * @param Property|null $attendee
	 * @return bool
	 */
	public function getAttendeeRsvpOrReqForParticipant(?Property $attendee = null) {
		if($attendee === null) {
			return false;
		}

		$rsvp = $attendee->offsetGet('RSVP');
		if (($rsvp instanceof Parameter) && (strcasecmp($rsvp->getValue(), 'TRUE') === 0)) {
			return true;
		}
		$role = $attendee->offsetGet('ROLE');
		// @see https://datatracker.ietf.org/doc/html/rfc5545#section-3.2.16
		// Attendees without a role are assumed required and should receive an invitation link even if they have no RSVP set
		if ($role === null
			|| (($role instanceof Parameter) && (strcasecmp($role->getValue(), 'REQ-PARTICIPANT') === 0))
			|| (($role instanceof Parameter) && (strcasecmp($role->getValue(), 'OPT-PARTICIPANT') === 0))
		) {
			return true;
		}

		// RFC 5545 3.2.17: default RSVP is false
		return false;
	}

	/**
	 * @param IEMailTemplate $template
	 * @param string $method
	 * @param string $sender
	 * @param string $summary
	 * @param string|null $partstat
	 * @param bool $isModified
	 */
	public function addSubjectAndHeading(IEMailTemplate $template,
		string $method, string $sender, string $summary, bool $isModified, ?Property $replyingAttendee = null): void {
		if ($method === IMipPlugin::METHOD_CANCEL) {
			// TRANSLATORS Subject for email, when an invitation is cancelled. Ex: "Cancelled: {{Event Name}}"
			$template->setSubject($this->l10n->t('Cancelled: %1$s', [$summary]));
			$template->addHeading($this->l10n->t('"%1$s" has been canceled', [$summary]));
		} elseif ($method === IMipPlugin::METHOD_REPLY) {
			// TRANSLATORS Subject for email, when an invitation is replied to. Ex: "Re: {{Event Name}}"
			$template->setSubject($this->l10n->t('Re: %1$s', [$summary]));
			// Build the strings
			$partstat = (isset($replyingAttendee)) ? $replyingAttendee->offsetGet('PARTSTAT') : null;
			$partstat = ($partstat instanceof Parameter) ? $partstat->getValue() : null;
			switch ($partstat) {
				case 'ACCEPTED':
					$template->addHeading($this->l10n->t('%1$s has accepted your invitation', [$sender]));
					break;
				case 'TENTATIVE':
					$template->addHeading($this->l10n->t('%1$s has tentatively accepted your invitation', [$sender]));
					break;
				case 'DECLINED':
					$template->addHeading($this->l10n->t('%1$s has declined your invitation', [$sender]));
					break;
				case null:
				default:
					$template->addHeading($this->l10n->t('%1$s has responded to your invitation', [$sender]));
					break;
			}
		} elseif ($method === IMipPlugin::METHOD_REQUEST && $isModified) {
			// TRANSLATORS Subject for email, when an invitation is updated. Ex: "Invitation updated: {{Event Name}}"
			$template->setSubject($this->l10n->t('Invitation updated: %1$s', [$summary]));
			$template->addHeading($this->l10n->t('%1$s updated the event "%2$s"', [$sender, $summary]));
		} else {
			// TRANSLATORS Subject for email, when an invitation is sent. Ex: "Invitation: {{Event Name}}"
			$template->setSubject($this->l10n->t('Invitation: %1$s', [$summary]));
			$template->addHeading($this->l10n->t('%1$s would like to invite you to "%2$s"', [$sender, $summary]));
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getAbsoluteImagePath($path): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath('core', $path)
		);
	}

	/**
	 * addAttendees: add organizer and attendee names/emails to iMip mail.
	 *
	 * Enable with DAV setting: invitation_list_attendees (default: no)
	 *
	 * The default is 'no', which matches old behavior, and is privacy preserving.
	 *
	 * To enable including attendees in invitation emails:
	 *   % php occ config:app:set dav invitation_list_attendees --value yes
	 *
	 * @param IEMailTemplate $template
	 * @param IL10N $this->l10n
	 * @param VEvent $vevent
	 * @author brad2014 on github.com
	 */
	public function addAttendees(IEMailTemplate $template, VEvent $vevent) {
		if ($this->config->getAppValue('dav', 'invitation_list_attendees', 'no') === 'no') {
			return;
		}

		if (isset($vevent->ORGANIZER)) {
			/** @var Property | Property\ICalendar\CalAddress $organizer */
			$organizer = $vevent->ORGANIZER;
			$organizerEmail = substr($organizer->getNormalizedValue(), 7);
			/** @var string|null $organizerName */
			$organizerName = isset($organizer->CN) ? $organizer->CN->getValue() : null;
			$organizerHTML = sprintf('<a href="%s">%s</a>',
				htmlspecialchars($organizer->getNormalizedValue()),
				htmlspecialchars($organizerName ?: $organizerEmail));
			$organizerText = sprintf('%s <%s>', $organizerName, $organizerEmail);
			if(isset($organizer['PARTSTAT'])) {
				/** @var Parameter $partstat */
				$partstat = $organizer['PARTSTAT'];
				if(strcasecmp($partstat->getValue(), 'ACCEPTED') === 0) {
					$organizerHTML .= ' ✔︎';
					$organizerText .= ' ✔︎';
				}
			}
			$template->addBodyListItem($organizerHTML, $this->l10n->t('Organizer:'),
				$this->getAbsoluteImagePath('caldav/organizer.png'),
				$organizerText, '', IMipPlugin::IMIP_INDENT);
		}

		$attendees = $vevent->select('ATTENDEE');
		if (count($attendees) === 0) {
			return;
		}

		$attendeesHTML = [];
		$attendeesText = [];
		foreach ($attendees as $attendee) {
			$attendeeEmail = substr($attendee->getNormalizedValue(), 7);
			$attendeeName = isset($attendee['CN']) ? $attendee['CN']->getValue() : null;
			$attendeeHTML = sprintf('<a href="%s">%s</a>',
				htmlspecialchars($attendee->getNormalizedValue()),
				htmlspecialchars($attendeeName ?: $attendeeEmail));
			$attendeeText = sprintf('%s <%s>', $attendeeName, $attendeeEmail);
			if (isset($attendee['PARTSTAT'])) {
				/** @var Parameter $partstat */
				$partstat = $attendee['PARTSTAT'];
				if (strcasecmp($partstat->getValue(), 'ACCEPTED') === 0) {
					$attendeeHTML .= ' ✔︎';
					$attendeeText .= ' ✔︎';
				}
			}
			$attendeesHTML[] = $attendeeHTML;
			$attendeesText[] = $attendeeText;
		}

		$template->addBodyListItem(implode('<br/>', $attendeesHTML), $this->l10n->t('Attendees:'),
			$this->getAbsoluteImagePath('caldav/attendees.png'),
			implode("\n", $attendeesText), '', IMipPlugin::IMIP_INDENT);
	}

	/**
	 * @param IEMailTemplate $template
	 * @param VEVENT $vevent
	 * @param $data
	 */
	public function addBulletList(IEMailTemplate $template, VEvent $vevent, $data) {
		$template->addBodyListItem(
			$data['meeting_title_html'] ?? $data['meeting_title'], $this->l10n->t('Title:'),
			$this->getAbsoluteImagePath('caldav/title.png'), $data['meeting_title'], '', IMipPlugin::IMIP_INDENT);
		if ($data['meeting_when'] !== '') {
			$template->addBodyListItem($data['meeting_when_html'] ?? $data['meeting_when'], $this->l10n->t('Time:'),
				$this->getAbsoluteImagePath('caldav/time.png'), $data['meeting_when'], '', IMipPlugin::IMIP_INDENT);
		}
		if ($data['meeting_location'] !== '') {
			$template->addBodyListItem($data['meeting_location_html'] ?? $data['meeting_location'], $this->l10n->t('Location:'),
				$this->getAbsoluteImagePath('caldav/location.png'), $data['meeting_location'], '', IMipPlugin::IMIP_INDENT);
		}
		if ($data['meeting_url'] !== '') {
			$template->addBodyListItem($data['meeting_url_html'] ?? $data['meeting_url'], $this->l10n->t('Link:'),
				$this->getAbsoluteImagePath('caldav/link.png'), $data['meeting_url'], '', IMipPlugin::IMIP_INDENT);
		}

		$this->addAttendees($template, $vevent);

		/* Put description last, like an email body, since it can be arbitrarily long */
		if ($data['meeting_description']) {
			$template->addBodyListItem($data['meeting_description_html'] ?? $data['meeting_description'], $this->l10n->t('Description:'),
				$this->getAbsoluteImagePath('caldav/description.png'), $data['meeting_description'], '', IMipPlugin::IMIP_INDENT);
		}
	}

	/**
	 * @param Message $iTipMessage
	 * @return null|Property
	 */
	public function getCurrentAttendee(Message $iTipMessage): ?Property {
		/** @var VEvent $vevent */
		$vevent = $iTipMessage->message->VEVENT;
		$attendees = $vevent->select('ATTENDEE');
		foreach ($attendees as $attendee) {
			if ($iTipMessage->method === 'REPLY' && strcasecmp($attendee->getValue(), $iTipMessage->sender) === 0) {
				/** @var Property $attendee */
				return $attendee;
			} elseif (strcasecmp($attendee->getValue(), $iTipMessage->recipient) === 0) {
				/** @var Property $attendee */
				return $attendee;
			}
		}
		return null;
	}

	/**
	 * @param Message $iTipMessage
	 * @param VEvent $vevent
	 * @param int $lastOccurrence
	 * @return string
	 */
	public function createInvitationToken(Message $iTipMessage, VEvent $vevent, int $lastOccurrence): string {
		$token = $this->random->generate(60, ISecureRandom::CHAR_ALPHANUMERIC);

		$attendee = $iTipMessage->recipient;
		$organizer = $iTipMessage->sender;
		$sequence = $iTipMessage->sequence;
		$recurrenceId = isset($vevent->{'RECURRENCE-ID'}) ?
			$vevent->{'RECURRENCE-ID'}->serialize() : null;
		$uid = $vevent->{'UID'};

		$query = $this->db->getQueryBuilder();
		$query->insert('calendar_invitations')
			->values([
				'token' => $query->createNamedParameter($token),
				'attendee' => $query->createNamedParameter($attendee),
				'organizer' => $query->createNamedParameter($organizer),
				'sequence' => $query->createNamedParameter($sequence),
				'recurrenceid' => $query->createNamedParameter($recurrenceId),
				'expiration' => $query->createNamedParameter($lastOccurrence),
				'uid' => $query->createNamedParameter($uid)
			])
			->execute();

		return $token;
	}

	/**
	 * Create a valid VCalendar object out of the details of
	 * a VEvent and its associated iTip Message
	 *
	 * We do this to filter out all unchanged VEvents
	 * This is especially important in iTip Messages with recurrences
	 * and recurrence exceptions
	 *
	 * @param Message $iTipMessage
	 * @param VEvent $vEvent
	 * @return VCalendar
	 */
	public function generateVCalendar(Message $iTipMessage, VEvent $vEvent): VCalendar {
		$vCalendar = new VCalendar();
		$vCalendar->add('METHOD', $iTipMessage->method);
		foreach ($iTipMessage->message->getComponents() as $component) {
			if ($component instanceof VEvent) {
				continue;
			}
			$vCalendar->add(clone $component);
		}
		$vCalendar->add($vEvent);
		return $vCalendar;
	}

	/**
	 * @param IEMailTemplate $template
	 * @param $token
	 */
	public function addResponseButtons(IEMailTemplate $template, $token) {
		$template->addBodyButtonGroup(
			$this->l10n->t('Accept'),
			$this->urlGenerator->linkToRouteAbsolute('dav.invitation_response.accept', [
				'token' => $token,
			]),
			$this->l10n->t('Decline'),
			$this->urlGenerator->linkToRouteAbsolute('dav.invitation_response.decline', [
				'token' => $token,
			])
		);
	}

	public function addMoreOptionsButton(IEMailTemplate $template, $token) {
		$moreOptionsURL = $this->urlGenerator->linkToRouteAbsolute('dav.invitation_response.options', [
			'token' => $token,
		]);
		$html = vsprintf('<small><a href="%s">%s</a></small>', [
			$moreOptionsURL, $this->l10n->t('More options …')
		]);
		$text = $this->l10n->t('More options at %s', [$moreOptionsURL]);

		$template->addBodyText($html, $text);
	}

	public function getReplyingAttendee(Message $iTipMessage): ?Property {
		/** @var VEvent $vevent */
		$vevent = $iTipMessage->message->VEVENT;
		$attendees = $vevent->select('ATTENDEE');
		foreach ($attendees as $attendee) {
			/** @var Property $attendee */
			if (strcasecmp($attendee->getValue(), $iTipMessage->sender) === 0) {
				return $attendee;
			}
		}
		return null;
	}

	public function isRoomOrResource(Property $attendee): bool {
		$cuType = $attendee->offsetGet('CUTYPE');
		if(!$cuType instanceof Parameter) {
			return false;
		}
		$type = $cuType->getValue() ?? 'INDIVIDUAL';
		if (\in_array(strtoupper($type), ['RESOURCE', 'ROOM'], true)) {
			// Don't send emails to things
			return true;
		}
		return false;
	}
}
