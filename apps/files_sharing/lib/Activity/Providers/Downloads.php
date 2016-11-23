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

class Downloads implements IProvider {

	/** @var IL10N */
	protected $l;

	/** @var IURLGenerator */
	protected $url;

	/** @var IManager */
	protected $activityManager;

	const SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED = 'public_shared_file_downloaded';
	const SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED = 'public_shared_folder_downloaded';

	const SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED = 'file_shared_with_email_downloaded';
	const SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED = 'folder_shared_with_email_downloaded';

	/**
	 * @param IL10N $l
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 */
	public function __construct(IL10N $l, IURLGenerator $url, IManager $activityManager) {
		$this->l = $l;
		$this->url = $url;
		$this->activityManager = $activityManager;
	}

	/**
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parse(IEvent $event, IEvent $previousEvent = null) {
		if ($event->getApp() !== 'files_sharing') {
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

		if ($event->getSubject() === self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED ||
			$event->getSubject() === self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED) {
			$event->setParsedSubject($this->l->t('Downloaded via public link'))
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/download.svg')));
		} else if ($event->getSubject() === self::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED ||
			$event->getSubject() === self::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED) {
			$event->setParsedSubject($this->l->t('Downloaded by %1$s', $parsedParameters['email']['name']))
				->setRichSubject($this->l->t('Downloaded by {email}'), [
					'email' => $parsedParameters['email'],
				])
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/download.svg')));
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

		if ($event->getSubject() === self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED ||
			$event->getSubject() === self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED) {
			$event->setParsedSubject($this->l->t('%1$s downloaded via public link', [
					$parsedParameters['file']['path'],
				]))
				->setRichSubject($this->l->t('{file} downloaded via public link'), [
					'file' => $parsedParameters['file'],
				])
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/download.svg')));
		} else if ($event->getSubject() === self::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED ||
			$event->getSubject() === self::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED) {
			$event->setParsedSubject($this->l->t('%1$s downloaded %2$s', [
					$parsedParameters['email']['name'],
					$parsedParameters['file']['path'],
				]))
				->setRichSubject($this->l->t('{email} downloaded {file}'), [
					'email' => $parsedParameters['email'],
					'file' => $parsedParameters['file'],
				])
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/download.svg')));
		} else {
			throw new \InvalidArgumentException();
		}

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @return array
	 */
	protected function getParsedParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED:
			case self::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED:
				return [
					'file' => $this->generateFileParameter($event->getObjectId(), $parameters[0]),
				];
			case self::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED:
			case self::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED:
				return [
					'file' => $this->generateFileParameter($event->getObjectId(), $parameters[0]),
					'email' => [
						'type' => 'email',
						'id' => $parameters[1],
						'name' => $parameters[1],
					],
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
}
