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

namespace OCA\Files_Sharing\Activity\Providers;

use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class PublicLinks implements IProvider {

	/** @var IFactory */
	protected $languageFactory;

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

	const SUBJECT_SHARED_LINK_SELF = 'shared_link_self';
	const SUBJECT_RESHARED_LINK_BY = 'reshared_link_by';
	const SUBJECT_UNSHARED_LINK_SELF = 'unshared_link_self';
	const SUBJECT_UNSHARED_LINK_BY = 'unshared_link_by';
	const SUBJECT_LINK_EXPIRED = 'link_expired';
	const SUBJECT_LINK_BY_EXPIRED = 'link_by_expired';


	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param IUserManager $userManager
	 */
	public function __construct(IFactory $languageFactory, IURLGenerator $url, IManager $activityManager, IUserManager $userManager) {
		$this->languageFactory = $languageFactory;
		$this->url = $url;
		$this->activityManager = $activityManager;
		$this->userManager = $userManager;
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, IEvent $previousEvent = null) {
		if ($event->getApp() !== 'files_sharing') {
			throw new \InvalidArgumentException();
		}

		$this->l = $this->languageFactory->get('files_sharing', $language);

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

		if ($event->getSubject() === self::SUBJECT_SHARED_LINK_SELF) {
			$event->setParsedSubject($this->l->t('Shared as public link'))
				->setRichSubject($this->l->t('Shared as public link'))
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_UNSHARED_LINK_SELF) {
			$event->setParsedSubject($this->l->t('Removed public link'))
				->setRichSubject($this->l->t('Removed public link'))
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
//		} else if ($event->getSubject() === self::SUBJECT_LINK_EXPIRED) {
//			$event->setParsedSubject($this->l->t('Public link expired'))
//				->setRichSubject($this->l->t('Public link expired'))
//				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_RESHARED_LINK_BY) {
			$event->setParsedSubject($this->l->t('%1$s shared as public link', [
					$parsedParameters['actor']['name'],
				]))
				->setRichSubject($this->l->t('{actor} shared as public link'), [
					'actor' => $parsedParameters['actor'],
				])
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_UNSHARED_LINK_BY) {
			$event->setParsedSubject($this->l->t('%1$s removed public link', [
					$parsedParameters['actor']['name'],
				]))
				->setRichSubject($this->l->t('{actor} removed public link'), [
					'actor' => $parsedParameters['actor'],
				])
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
//		} else if ($event->getSubject() === self::SUBJECT_LINK_BY_EXPIRED) {
//			$event->setParsedSubject($this->l->t('Public link of %1$s expired', [
//					$parsedParameters['actor']['name'],
//				]))
//				->setRichSubject($this->l->t('Public link of {actor} expired',[
//					'actor' => $parsedParameters['actor'],
//				]))
//				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));

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

		if ($event->getSubject() === self::SUBJECT_SHARED_LINK_SELF) {
			$event->setParsedSubject($this->l->t('You shared %1$s as public link', [
					$parsedParameters['file']['path'],
				]))
				->setRichSubject($this->l->t('You shared {file} as public link'), $parsedParameters)
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_UNSHARED_LINK_SELF) {
			$event->setParsedSubject($this->l->t('You removed public link for %1$s', [
					$parsedParameters['file']['path'],
				]))
				->setRichSubject($this->l->t('You removed public link for {file}'), $parsedParameters)
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
//		} else if ($event->getSubject() === self::SUBJECT_LINK_EXPIRED) {
//			$event->setParsedSubject($this->l->t('Public link expired'))
//				->setRichSubject($this->l->t('Public link expired'))
//				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_RESHARED_LINK_BY) {
			$event->setParsedSubject($this->l->t('%2$s shared %1$s as public link', [
					$parsedParameters['file']['path'],
					$parsedParameters['actor']['name'],
				]))
				->setRichSubject($this->l->t('{actor} shared {file} as public link'), $parsedParameters)
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_UNSHARED_LINK_BY) {
			$event->setParsedSubject($this->l->t('%2$s removed public link for %1$s', [
					$parsedParameters['file']['path'],
					$parsedParameters['actor']['name'],
				]))
				->setRichSubject($this->l->t('{actor} removed public link for {file}'), $parsedParameters)
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
//		} else if ($event->getSubject() === self::SUBJECT_LINK_BY_EXPIRED) {
//			$event->setParsedSubject($this->l->t('Public link of %1$s expired', [
//					$parsedParameters['actor']['name'],
//				]))
//				->setRichSubject($this->l->t('Public link of {actor} expired',[
//					'actor' => $parsedParameters['actor'],
//				]))
//				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));

		} else {
			throw new \InvalidArgumentException();
		}

		return $event;
	}

	protected function getParsedParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::SUBJECT_SHARED_LINK_SELF:
			case self::SUBJECT_UNSHARED_LINK_SELF:
			case self::SUBJECT_LINK_EXPIRED:
				$id = key($parameters[0]);
				return [
					'file' => $this->generateFileParameter($id, $parameters[0][$id]),
				];
			case self::SUBJECT_RESHARED_LINK_BY:
			case self::SUBJECT_UNSHARED_LINK_BY:
			case self::SUBJECT_LINK_BY_EXPIRED:
				$id = key($parameters[0]);
				return [
					'file' => $this->generateFileParameter($id, $parameters[0][$id]),
					'actor' => $this->generateUserParameter($parameters[1]),
				];
		}
		return [];
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
