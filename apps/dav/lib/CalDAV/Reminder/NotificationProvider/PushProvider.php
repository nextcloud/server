<?php
/**
 * @copyright Copyright (c) 2018 Thomas Citharel <tcit@tcit.fr>
 *
 * @author Thomas Citharel <tcit@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\CalDAV\Reminder\NotificationProvider;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\Reminder\AbstractNotificationProvider;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10NFactory;
use OCP\Notification\IManager;
use OCP\IUser;
use OCP\Notification\INotification;
use Sabre\VObject\Component\VCalendar;
use OCP\AppFramework\Utility\ITimeFactory;

class PushProvider extends AbstractNotificationProvider
{

	public const NOTIFICATION_TYPE = 'DISPLAY';

    /**
     * @var IManager
     */
	private $manager;

	/**
	 * @var ITimeFactory
	 */
	private $timeFactory;

	/**
	 * @param IConfig $config
	 * @param IManager $manager
	 * @param ILogger $logger
	 * @param L10NFactory $l10nFactory
	 * @param IUrlGenerator $urlGenerator
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(IConfig $config, IManager $manager, ILogger $logger,
								L10NFactory $l10nFactory,
								IURLGenerator $urlGenerator, ITimeFactory $timeFactory) {
		parent::__construct($logger, $l10nFactory, $urlGenerator, $config);
		$this->manager = $manager;
		$this->timeFactory = $timeFactory;
    }

	/**
	 * Send notification
	 *
	 * @param VCalendar $vcalendar
	 * @param string $calendarDisplayName
	 * @param IUser $user
	 * @return void
	 * @throws \Exception
	 */
    public function send(VCalendar $vcalendar, string $calendarDisplayName, IUser $user): void
    {

		$lang = $this->config->getUserValue($user->getUID(), 'core', 'lang', $this->l10nFactory->findLanguage());
		$this->l10n = $this->l10nFactory->get('dav', $lang);

		$event = $this->extractEventDetails($vcalendar);
		/** @var INotification $notification */
		$notification = $this->manager->createNotification();
		$notification->setApp(Application::APP_ID)
			->setUser($user->getUID())
			->setDateTime($this->timeFactory->getDateTime())
			->setObject(Application::APP_ID, $event['uid']) // $type and $id
			->setSubject('calendar_reminder', ['title' => $event['title'], 'start' => $event['start']->getTimestamp()]) // $subject and $parameters
			->setMessage('calendar_reminder', [
				'when' => $event['when'],
				'description' => $event['description'],
				'location' => $event['location'],
				'calendar' => $calendarDisplayName
			])
		;
		$this->manager->notify($notification);
    }
}
