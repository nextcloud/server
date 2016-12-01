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

namespace OCA\Files\Activity;

use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class Provider implements IProvider {

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

	/** @var IEventMerger */
	protected $eventMerger;

	/** @var string[] cached displayNames - key is the UID and value the displayname */
	protected $displayNames = [];

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param IUserManager $userManager
	 * @param IEventMerger $eventMerger
	 */
	public function __construct(IFactory $languageFactory, IURLGenerator $url, IManager $activityManager, IUserManager $userManager, IEventMerger $eventMerger) {
		$this->languageFactory = $languageFactory;
		$this->url = $url;
		$this->activityManager = $activityManager;
		$this->userManager = $userManager;
		$this->eventMerger = $eventMerger;
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
		if ($event->getApp() !== 'files') {
			throw new \InvalidArgumentException();
		}

		$this->l = $this->languageFactory->get('files', $language);

		if ($this->activityManager->isFormattingFilteredObject()) {
			try {
				return $this->parseShortVersion($event, $previousEvent);
			} catch (\InvalidArgumentException $e) {
				// Ignore and simply use the long version...
			}
		}

		return $this->parseLongVersion($event, $previousEvent);
	}

	/**
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseShortVersion(IEvent $event, IEvent $previousEvent = null) {
		$parsedParameters = $this->getParameters($event->getSubject(), $event->getSubjectParameters());

		if ($event->getSubject() === 'created_by') {
			$subject = $this->l->t('Created by {user}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.svg')));
		} else if ($event->getSubject() === 'changed_by') {
			$subject = $this->l->t('Changed by {user}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
		} else if ($event->getSubject() === 'deleted_by') {
			$subject = $this->l->t('Deleted by {user}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'delete-color.svg')));
		} else if ($event->getSubject() === 'restored_by') {
			$subject = $this->l->t('Restored by {user}');
		} else if ($event->getSubject() === 'renamed_by') {
			$subject = $this->l->t('Renamed by {user}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
		} else if ($event->getSubject() === 'moved_by') {
			$subject = $this->l->t('Moved by {user}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
		} else {
			throw new \InvalidArgumentException();
		}

		$this->setSubjects($event, $subject, $parsedParameters);

		$event = $this->eventMerger->mergeEvents('user', $event, $previousEvent);

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
		$parsedParameters = $this->getParameters($event->getSubject(), $event->getSubjectParameters());

		if ($event->getSubject() === 'created_self') {
			$subject = $this->l->t('You created {file}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.svg')));
		} else if ($event->getSubject() === 'created_by') {
			$subject = $this->l->t('{user} created {file}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.svg')));
		} else if ($event->getSubject() === 'created_public') {
			$subject = $this->l->t('{file} was created in a public folder');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.svg')));
		} else if ($event->getSubject() === 'changed_self') {
			$subject = $this->l->t('You changed {file}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
		} else if ($event->getSubject() === 'changed_by') {
			$subject = $this->l->t('{user} changed {file}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
		} else if ($event->getSubject() === 'deleted_self') {
			$subject = $this->l->t('You deleted {file}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'delete-color.svg')));
		} else if ($event->getSubject() === 'deleted_by') {
			$subject = $this->l->t('{user} deleted {file}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'delete-color.svg')));
		} else if ($event->getSubject() === 'restored_self') {
			$subject = $this->l->t('You restored {file}');
		} else if ($event->getSubject() === 'restored_by') {
			$subject = $this->l->t('{user} restored {file}');
		} else if ($event->getSubject() === 'renamed_self') {
			$subject = $this->l->t('You renamed {oldfile} to {newfile}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
		} else if ($event->getSubject() === 'renamed_by') {
			$subject = $this->l->t('{user} renamed {oldfile} to {newfile}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
		} else if ($event->getSubject() === 'moved_self') {
			$subject = $this->l->t('You moved {oldfile} to {newfile}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
		} else if ($event->getSubject() === 'moved_by') {
			$subject = $this->l->t('{user} moved {oldfile} to {newfile}');
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
		} else {
			throw new \InvalidArgumentException();
		}

		$this->setSubjects($event, $subject, $parsedParameters);

		$event = $this->eventMerger->mergeEvents('file', $event, $previousEvent);

		if ($event->getChildEvent() === null) {
			// Couldn't group by file, maybe we can group by user
			$event = $this->eventMerger->mergeEvents('user', $event, $previousEvent);
		}

		return $event;
	}

	protected function setSubjects(IEvent $event, $subject, array $parameters) {
		$placeholders = $replacements = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			if ($parameter['type'] === 'file') {
				$replacements[] = trim($parameter['path'], '/');
			} else {
				$replacements[] = $parameter['name'];
			}
		}

		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $parameters);
	}

	protected function getParameters($subject, array $parameters) {
		switch ($subject) {
			case 'created_self':
			case 'created_public':
			case 'changed_self':
			case 'deleted_self':
			case 'restored_self':
				return [
					'file' => $this->getRichFileParameter($parameters[0]),
				];
			case 'created_by':
			case 'changed_by':
			case 'deleted_by':
			case 'restored_by':
				return [
					'file' => $this->getRichFileParameter($parameters[0]),
					'user' => $this->getRichUserParameter($parameters[1]),
				];
			case 'renamed_self':
			case 'moved_self':
				return [
					'newfile' => $this->getRichFileParameter($parameters[0]),
					'oldfile' => $this->getRichFileParameter($parameters[1]),
				];
			case 'renamed_by':
			case 'moved_by':
				return [
					'newfile' => $this->getRichFileParameter($parameters[0]),
					'user' => $this->getRichUserParameter($parameters[1]),
					'oldfile' => $this->getRichFileParameter($parameters[2]),
				];
		}
		return [];
	}

	protected function getRichFileParameter($parameter) {
		$path = reset($parameter);
		$id = key($parameter);
		return [
			'type' => 'file',
			'id' => $id,
			'name' => basename($path),
			'path' => $path,
		];
	}

	protected function getRichUserParameter($uid) {
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
