<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

namespace OCA\ShareByMail;

use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;

class Activity implements IProvider {

	/** @var IL10N */
	protected $l;

	/** @var IURLGenerator */
	protected $url;

	/** @var IManager */
	protected $activityManager;

	/** @var IUserManager */
	protected $userManager;

	/** @var array */
	protected $displayNames = [];

	const SUBJECT_SHARED_EMAIL_SELF = 'shared_with_email_self';
	const SUBJECT_SHARED_EMAIL_BY = 'shared_with_email_by';

	/**
	 * @param IL10N $l
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param IUserManager $userManager
	 */
	public function __construct(IL10N $l, IURLGenerator $url, IManager $activityManager, IUserManager $userManager) {
		$this->l = $l;
		$this->url = $url;
		$this->activityManager = $activityManager;
		$this->userManager = $userManager;
	}

	/**
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parse(IEvent $event, IEvent $previousEvent = null) {
		if ($event->getApp() !== 'sharebymail') {
			throw new \InvalidArgumentException();
		}

		if ($this->activityManager->isFormattingFilteredObject()) {
			try {
				return $this->parseShortVersion($event);
			} catch (\InvalidArgumentException $e) {
				// Ignore and simply use the long version...
			}
		}

		return $this->parseLongVersion($event);
	}

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseShortVersion(IEvent $event) {
		$parsedParameters = $this->getParsedParameters($event);

		if ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_SELF) {
			$event->setParsedSubject($this->l->t('Shared with %1$s', [
					$parsedParameters['email']['name'],
				]))
				->setRichSubject($this->l->t('Shared with {email}'), [
					'email' => $parsedParameters['email'],
				])
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_BY) {
			$event->setParsedSubject($this->l->t('Shared with %1$s by %2$s', [
					$parsedParameters['email']['name'],
					$parsedParameters['actor']['name'],
				]))
				->setRichSubject($this->l->t('Shared with {email} by {actor}'), [
					'email' => $parsedParameters['email'],
					'actor' => $parsedParameters['actor'],
				])
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));

		} else {
			throw new \InvalidArgumentException();
		}

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseLongVersion(IEvent $event) {
		$parsedParameters = $this->getParsedParameters($event);

		if ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_SELF) {
			$event->setParsedSubject($this->l->t('You shared %1$s with %2$s by mail', [
					$parsedParameters['file']['path'],
					$parsedParameters['email']['name'],
				]))
				->setRichSubject($this->l->t('You shared {file} with {email} by mail'), $parsedParameters)
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_BY) {
			$event->setParsedSubject($this->l->t('%3$s shared %1$s with %2$s by mail', [
					$parsedParameters['file']['path'],
					$parsedParameters['email']['name'],
					$parsedParameters['actor']['name'],
				]))
				->setRichSubject($this->l->t('{actor} shared {file} with {email} by mail'), $parsedParameters)
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else {
			throw new \InvalidArgumentException();
		}

		return $event;
	}

	protected function getParsedParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::SUBJECT_SHARED_EMAIL_BY:
				return [
					'file' => $this->generateFileParameter((int) $event->getObjectId(), $parameters[0]),
					'email' => $this->generateEmailParameter($parameters[1]),
				];
			case self::SUBJECT_SHARED_EMAIL_SELF:
				return [
					'file' => $this->generateFileParameter((int) $event->getObjectId(), $parameters[0]),
					'email' => $this->generateEmailParameter($parameters[1]),
					'actor' => $this->generateUserParameter($parameters[2]),
				];
		}
		throw new \InvalidArgumentException();
	}

	/**
	 * @param int $id
	 * @param string $path
	 * @return array
	 */
	protected function generateFileParameter($id, $path) {
		return [
			'type' => 'file',
			'id' => $id,
			'name' => basename($path),
			'path' => $path,
			'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $id]),
		];
	}

	/**
	 * @param string $email
	 * @return array
	 */
	protected function generateEmailParameter($email) {
		return [
			'type' => 'email',
			'id' => $email,
			'name' => $email, // TODO ask contacts
		];
	}

	/**
	 * @param string $uid
	 * @return array
	 */
	protected function generateUserParameter($uid) {
		if (!isset($this->displayNames[$uid])) {
			$this->displayNames[$uid] = $this->getDisplayName($uid);
		}

		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->displayNames[$uid],
		];
	}

	/**
	 * @param string $uid
	 * @return string
	 */
	protected function getDisplayName($uid) {
		$user = $this->userManager->get($uid);
		if ($user instanceof IUser) {
			return $user->getDisplayName();
		} else {
			return $uid;
		}
	}
}
