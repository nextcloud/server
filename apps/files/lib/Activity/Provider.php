<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
	/** @var IL10N */
	protected $activityLang;

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
		$this->activityLang = $this->languageFactory->get('activity', $language);

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
		$parsedParameters = $this->getParameters($event);

		if ($event->getSubject() === 'created_by') {
			$subject = $this->l->t('Created by {user}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.svg')));
			}
		} else if ($event->getSubject() === 'changed_by') {
			$subject = $this->l->t('Changed by {user}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
			}
		} else if ($event->getSubject() === 'deleted_by') {
			$subject = $this->l->t('Deleted by {user}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'delete-color.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'delete-color.svg')));
			}
		} else if ($event->getSubject() === 'restored_by') {
			$subject = $this->l->t('Restored by {user}');
		} else if ($event->getSubject() === 'renamed_by') {
			$subject = $this->l->t('Renamed by {user}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
			}
		} else if ($event->getSubject() === 'moved_by') {
			$subject = $this->l->t('Moved by {user}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
			}
		} else {
			throw new \InvalidArgumentException();
		}

		if (!isset($parsedParameters['user'])) {
			// External user via public link share
			$subject = str_replace('{user}', $this->activityLang->t('"remote user"'), $subject);
		}

		$this->setSubjects($event, $subject, $parsedParameters);

		return $this->eventMerger->mergeEvents('user', $event, $previousEvent);
	}

	/**
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseLongVersion(IEvent $event, IEvent $previousEvent = null) {
		$parsedParameters = $this->getParameters($event);

		if ($event->getSubject() === 'created_self') {
			$subject = $this->l->t('You created {file}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.svg')));
			}
		} else if ($event->getSubject() === 'created_by') {
			$subject = $this->l->t('{user} created {file}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.svg')));
			}
		} else if ($event->getSubject() === 'created_public') {
			$subject = $this->l->t('{file} was created in a public folder');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'add-color.svg')));
			}
		} else if ($event->getSubject() === 'changed_self') {
			$subject = $this->l->t('You changed {file}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
			}
		} else if ($event->getSubject() === 'changed_by') {
			$subject = $this->l->t('{user} changed {file}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
			}
		} else if ($event->getSubject() === 'deleted_self') {
			$subject = $this->l->t('You deleted {file}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'delete-color.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'delete-color.svg')));
			}
		} else if ($event->getSubject() === 'deleted_by') {
			$subject = $this->l->t('{user} deleted {file}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'delete-color.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'delete-color.svg')));
			}
		} else if ($event->getSubject() === 'restored_self') {
			$subject = $this->l->t('You restored {file}');
		} else if ($event->getSubject() === 'restored_by') {
			$subject = $this->l->t('{user} restored {file}');
		} else if ($event->getSubject() === 'renamed_self') {
			$subject = $this->l->t('You renamed {oldfile} to {newfile}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
			}
		} else if ($event->getSubject() === 'renamed_by') {
			$subject = $this->l->t('{user} renamed {oldfile} to {newfile}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
			}
		} else if ($event->getSubject() === 'moved_self') {
			$subject = $this->l->t('You moved {oldfile} to {newfile}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
			}
		} else if ($event->getSubject() === 'moved_by') {
			$subject = $this->l->t('{user} moved {oldfile} to {newfile}');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('files', 'change.svg')));
			}
		} else {
			throw new \InvalidArgumentException();
		}

		if (!isset($parsedParameters['user'])) {
			// External user via public link share
			$subject = str_replace('{user}', $this->activityLang->t('"remote user"'), $subject);
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
				$replacements[] = $parameter['path'];
			} else {
				$replacements[] = $parameter['name'];
			}
		}

		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $parameters);
	}

	/**
	 * @param IEvent $event
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getParameters(IEvent $event) {
		$parameters = $event->getSubjectParameters();
		switch ($event->getSubject()) {
			case 'created_self':
			case 'created_public':
			case 'changed_self':
			case 'deleted_self':
			case 'restored_self':
				return [
					'file' => $this->getFile($parameters[0], $event),
				];
			case 'created_by':
			case 'changed_by':
			case 'deleted_by':
			case 'restored_by':
				if ($parameters[1] === '') {
					// External user via public link share
					return [
						'file' => $this->getFile($parameters[0], $event),
					];
				}
				return [
					'file' => $this->getFile($parameters[0], $event),
					'user' => $this->getUser($parameters[1]),
				];
			case 'renamed_self':
			case 'moved_self':
				return [
					'newfile' => $this->getFile($parameters[0]),
					'oldfile' => $this->getFile($parameters[1]),
				];
			case 'renamed_by':
			case 'moved_by':
				if ($parameters[1] === '') {
					// External user via public link share
					return [
						'newfile' => $this->getFile($parameters[0]),
						'oldfile' => $this->getFile($parameters[2]),
					];
				}
				return [
					'newfile' => $this->getFile($parameters[0]),
					'user' => $this->getUser($parameters[1]),
					'oldfile' => $this->getFile($parameters[2]),
				];
		}
		return [];
	}

	/**
	 * @param array|string $parameter
	 * @param IEvent|null $event
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getFile($parameter, IEvent $event = null) {
		if (is_array($parameter)) {
			$path = reset($parameter);
			$id = (string) key($parameter);
		} else if ($event !== null) {
			// Legacy from before ownCloud 8.2
			$path = $parameter;
			$id = $event->getObjectId();
		} else {
			throw new \InvalidArgumentException('Could not generate file parameter');
		}

		return [
			'type' => 'file',
			'id' => $id,
			'name' => basename($path),
			'path' => trim($path, '/'),
			'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $id]),
		];
	}

	/**
	 * @param string $uid
	 * @return array
	 */
	protected function getUser($uid) {
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
