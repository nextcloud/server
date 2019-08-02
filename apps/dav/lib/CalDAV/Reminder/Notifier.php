<?php
/**
 * @copyright Copyright (c) 2019 Thomas Citharel <tcit@tcit.fr>
 *
 * @author Thomas Citharel <tcit@tcit.fr>
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

namespace OCA\DAV\CalDAV\Reminder;

use OCA\DAV\AppInfo\Application;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\IURLGenerator;

class Notifier implements INotifier {

	/** @var array */
	public static $units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

	/** @var IFactory */
	protected $factory;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var IL10N */
	protected $l;

	public function __construct(IFactory $factory, IURLGenerator $urlGenerator) {
		$this->factory = $factory;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \Exception
	 */
	public function prepare(INotification $notification, string $languageCode):INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			throw new \InvalidArgumentException('Notification not from this app');
		}

		// Read the language from the notification
		$this->l = $this->factory->get('dav', $languageCode);

		if ($notification->getSubject() === 'calendar_reminder') {
			$subjectParameters = $notification->getSubjectParameters();
			$notification->setParsedSubject($this->processEventTitle($subjectParameters));

			$messageParameters = $notification->getMessageParameters();
			$notification->setParsedMessage($this->processEventDescription($messageParameters));
			$notification->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'places/calendar.svg')));
			return $notification;
		}
		// Unknown subject => Unknown notification => throw
		throw new \InvalidArgumentException('Unknown subject');
	}

	/**
	 * @param array $event
	 * @return string
	 * @throws \Exception
	 */
	private function processEventTitle(array $event):string {
		$event_datetime = new \DateTime();
		$event_datetime->setTimestamp($event['start']);
		$now = new \DateTime();

		$diff = $event_datetime->diff($now);

		foreach (self::$units as $attribute => $unit) {
            $count = $diff->$attribute;
            if (0 !== $count) {
                return $this->getPluralizedTitle($count, $diff->invert, $unit, $event['title']);
            }
        }
        return '';
	}

	/**
	 *
	 * @param int $count
	 * @param int $invert
	 * @param string $unit
	 * @param string $title
	 * @return string
	 */
	private function getPluralizedTitle(int $count, int $invert, string $unit, string $title):string {
		if ($invert) {
			return $this->l->n('%s (in one %s)', '%s (in %n %ss)', $count, [$title, $unit]);
		}
		// This should probably not show up
		return $this->l->n('%s (one %s ago)', '%s (%n %ss ago)', $count, [$title, $unit]);
	}

	/**
	 * @param array $event
	 * @return string
	 */
	private function processEventDescription(array $event):string {
		$description = [
			$this->l->t('Calendar: %s', $event['calendar']),
			$this->l->t('Date: %s', $event['when']),
		];

		if ($event['description']) {
			$description[] = $this->l->t('Description: %s', $event['description']);
		}
		if ($event['location']) {
			$description[] = $this->l->t('Where: %s', $event['location']);
		}
		return implode('<br>', $description);
	}
}
