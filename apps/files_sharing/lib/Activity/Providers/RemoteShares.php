<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_Sharing\Activity\Providers;

use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\Contacts\IManager as IContactsManager;
use OCP\Federation\ICloudIdManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class RemoteShares extends Base {
	public const SUBJECT_REMOTE_SHARE_ACCEPTED = 'remote_share_accepted';
	public const SUBJECT_REMOTE_SHARE_DECLINED = 'remote_share_declined';
	public const SUBJECT_REMOTE_SHARE_RECEIVED = 'remote_share_received';
	public const SUBJECT_REMOTE_SHARE_UNSHARED = 'remote_share_unshared';

	public function __construct(IFactory $languageFactory,
		IURLGenerator $url,
		IManager $activityManager,
		IUserManager $userManager,
		ICloudIdManager $cloudIdManager,
		IContactsManager $contactsManager,
		IEventMerger $eventMerger) {
		parent::__construct($languageFactory, $url, $activityManager, $userManager, $cloudIdManager, $contactsManager, $eventMerger);
	}

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseShortVersion(IEvent $event) {
		$parsedParameters = $this->getParsedParameters($event);

		if ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_ACCEPTED) {
			$subject = $this->l->t('{user} accepted the remote share');
		} elseif ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_DECLINED) {
			$subject = $this->l->t('{user} declined the remote share');
		} else {
			throw new \InvalidArgumentException();
		}

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		}
		$this->setSubjects($event, $subject, $parsedParameters);

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseLongVersion(IEvent $event, IEvent $previousEvent = null) {
		$parsedParameters = $this->getParsedParameters($event);

		if ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_RECEIVED) {
			$subject = $this->l->t('You received a new remote share {file} from {user}');
		} elseif ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_ACCEPTED) {
			$subject = $this->l->t('{user} accepted the remote share of {file}');
		} elseif ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_DECLINED) {
			$subject = $this->l->t('{user} declined the remote share of {file}');
		} elseif ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_UNSHARED) {
			$subject = $this->l->t('{user} unshared {file} from you');
		} else {
			throw new \InvalidArgumentException();
		}

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		}
		$this->setSubjects($event, $subject, $parsedParameters);

		return $event;
	}

	protected function getParsedParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::SUBJECT_REMOTE_SHARE_RECEIVED:
			case self::SUBJECT_REMOTE_SHARE_UNSHARED:
				$displayName = (count($parameters) > 2) ? $parameters[2] : '';
				return [
					'file' => [
						'type' => 'pending-federated-share',
						'id' => $parameters[1],
						'name' => $parameters[1],
					],
					'user' => $this->getUser($parameters[0], $displayName)
				];
			case self::SUBJECT_REMOTE_SHARE_ACCEPTED:
			case self::SUBJECT_REMOTE_SHARE_DECLINED:
				$fileParameter = $parameters[1];
				if (!is_array($fileParameter)) {
					$fileParameter = [$event->getObjectId() => $event->getObjectName()];
				}
				return [
					'file' => $this->getFile($fileParameter),
					'user' => $this->getUser($parameters[0]),
				];
		}
		throw new \InvalidArgumentException();
	}
}
