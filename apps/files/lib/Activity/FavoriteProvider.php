<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Activity;

use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

class FavoriteProvider implements IProvider {
	public const SUBJECT_ADDED = 'added_favorite';
	public const SUBJECT_REMOVED = 'removed_favorite';

	/** @var IFactory */
	protected $languageFactory;

	/** @var IL10N */
	protected $l;

	/** @var IURLGenerator */
	protected $url;

	/** @var IManager */
	protected $activityManager;

	/** @var IEventMerger */
	protected $eventMerger;

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param IEventMerger $eventMerger
	 */
	public function __construct(IFactory $languageFactory, IURLGenerator $url, IManager $activityManager, IEventMerger $eventMerger) {
		$this->languageFactory = $languageFactory;
		$this->url = $url;
		$this->activityManager = $activityManager;
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
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== 'files' || $event->getType() !== 'favorite') {
			throw new \InvalidArgumentException();
		}

		$this->l = $this->languageFactory->get('files', $language);

		if ($this->activityManager->isFormattingFilteredObject()) {
			try {
				return $this->parseShortVersion($event);
			} catch (\InvalidArgumentException $e) {
				// Ignore and simply use the long version...
			}
		}

		return $this->parseLongVersion($event, $previousEvent);
	}

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseShortVersion(IEvent $event) {
		if ($event->getSubject() === self::SUBJECT_ADDED) {
			$event->setParsedSubject($this->l->t('Added to favorites'));
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/starred.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/starred.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_REMOVED) {
			$event->setType('unfavorite');
			$event->setParsedSubject($this->l->t('Removed from favorites'));
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/star.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/star.svg')));
			}
		} else {
			throw new \InvalidArgumentException();
		}

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseLongVersion(IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getSubject() === self::SUBJECT_ADDED) {
			$subject = $this->l->t('You added {file} to your favorites');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/starred.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/starred.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_REMOVED) {
			$event->setType('unfavorite');
			$subject = $this->l->t('You removed {file} from your favorites');
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/star.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/star.svg')));
			}
		} else {
			throw new \InvalidArgumentException();
		}

		$this->setSubjects($event, $subject);
		$event = $this->eventMerger->mergeEvents('file', $event, $previousEvent);
		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param string $subject
	 */
	protected function setSubjects(IEvent $event, $subject) {
		$subjectParams = $event->getSubjectParameters();
		if (empty($subjectParams)) {
			// Try to fall back to the old way, but this does not work for emails.
			// But at least old activities still work.
			$subjectParams = [
				'id' => $event->getObjectId(),
				'path' => $event->getObjectName(),
			];
		}
		$parameter = [
			'type' => 'file',
			'id' => $subjectParams['id'],
			'name' => basename($subjectParams['path']),
			'path' => trim($subjectParams['path'], '/'),
			'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $subjectParams['id']]),
		];

		$event->setRichSubject($subject, ['file' => $parameter]);
	}
}
