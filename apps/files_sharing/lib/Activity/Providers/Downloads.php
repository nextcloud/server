<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Activity\Providers;

use OCP\Activity\IEvent;

class Downloads extends Base {
	public const SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED = 'public_shared_file_downloaded';
	public const SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED = 'public_shared_folder_downloaded';

	public const SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED = 'file_shared_with_email_downloaded';
	public const SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED = 'folder_shared_with_email_downloaded';

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseShortVersion(IEvent $event) {
		$parsedParameters = $this->getParsedParameters($event);

		if ($event->getSubject() === self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED ||
			$event->getSubject() === self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED) {
			$subject = $this->l->t('Downloaded via public link');
		} elseif ($event->getSubject() === self::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED ||
			$event->getSubject() === self::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED) {
			$subject = $this->l->t('Downloaded by {email}');
		} else {
			throw new \InvalidArgumentException();
		}

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/download.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/download.svg')));
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
	public function parseLongVersion(IEvent $event, ?IEvent $previousEvent = null) {
		$parsedParameters = $this->getParsedParameters($event);

		if ($event->getSubject() === self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED ||
			$event->getSubject() === self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED) {
			if (!isset($parsedParameters['remote-address-hash']['type'])) {
				$subject = $this->l->t('{file} downloaded via public link');
				$this->setSubjects($event, $subject, $parsedParameters);
			} else {
				$subject = $this->l->t('{file} downloaded via public link');
				$this->setSubjects($event, $subject, $parsedParameters);
				$event = $this->eventMerger->mergeEvents('file', $event, $previousEvent);
			}
		} elseif ($event->getSubject() === self::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED ||
			$event->getSubject() === self::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED) {
			$subject = $this->l->t('{email} downloaded {file}');
			$this->setSubjects($event, $subject, $parsedParameters);
		} else {
			throw new \InvalidArgumentException();
		}

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/download.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/download.svg')));
		}

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getParsedParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED:
			case self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED:
				if (isset($parameters[1])) {
					return [
						'file' => $this->getFile($parameters[0], $event),
						'remote-address-hash' => [
							'type' => 'highlight',
							'id' => $parameters[1],
							'name' => $parameters[1],
							'link' => '',
						],
					];
				}
				return [
					'file' => $this->getFile($parameters[0], $event),
				];
			case self::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED:
			case self::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED:
				return [
					'file' => $this->getFile($parameters[0], $event),
					'email' => [
						'type' => 'email',
						'id' => $parameters[1],
						'name' => $parameters[1],
					],
				];
		}

		throw new \InvalidArgumentException();
	}
}
