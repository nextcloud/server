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

class RemoteShares implements IProvider {

	/** @var IL10N */
	protected $l;

	/** @var IURLGenerator */
	protected $url;

	/** @var IManager */
	protected $activityManager;

	const SUBJECT_REMOTE_SHARE_ACCEPTED = 'remote_share_accepted';
	const SUBJECT_REMOTE_SHARE_DECLINED = 'remote_share_declined';
	const SUBJECT_REMOTE_SHARE_RECEIVED = 'remote_share_received';
	const SUBJECT_REMOTE_SHARE_UNSHARED = 'remote_share_unshared';

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

		if ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_ACCEPTED) {
			$event->setParsedSubject($this->l->t('%1$s accepted the remote share', [
					$parsedParameters['user']['name'],
				]))
				->setRichSubject($this->l->t('{user} accepted the remote share'), [
					'user' => $parsedParameters['user']
				])
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_DECLINED) {
			$event->setParsedSubject($this->l->t('%1$s declined the remote share', [
					$parsedParameters['user']['name'],
				]))
				->setRichSubject($this->l->t('{user} declined the remote share'), [
					'user' => $parsedParameters['user']
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

		if ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_RECEIVED) {
				$event->setParsedSubject($this->l->t('You received a new remote share %1$s from %2$s', [
					$parsedParameters['file']['name'],
					$parsedParameters['user']['name'],
				]))
				->setRichSubject($this->l->t('You received a new remote share {file} from {user}'), $parsedParameters)
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_ACCEPTED) {
				$event->setParsedSubject($this->l->t('%2$s accepted the remote share of %1$s', [
					$parsedParameters['file']['name'],
					$parsedParameters['user']['name'],
				]))
				->setRichSubject($this->l->t('{user} accepted the remote share of {file}'), $parsedParameters)
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_DECLINED) {
				$event->setParsedSubject($this->l->t('%2$s declined the remote share of %1$s', [
					$parsedParameters['file']['name'],
					$parsedParameters['user']['name'],
				]))
				->setRichSubject($this->l->t('{user} declined the remote share of {file}'), $parsedParameters)
				->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		} else if ($event->getSubject() === self::SUBJECT_REMOTE_SHARE_UNSHARED) {
				$event->setParsedSubject($this->l->t('%2$s unshared %1$s from you', [
					$parsedParameters['file']['name'],
					$parsedParameters['user']['name'],
				]))
				->setRichSubject($this->l->t('{user} unshared {file} from you'), $parsedParameters)
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
			case self::SUBJECT_REMOTE_SHARE_RECEIVED:
			case self::SUBJECT_REMOTE_SHARE_UNSHARED:
				$remoteUser = explode('@', $parameters[0], 2);
				return [
					'file' => [
						'type' => 'pending-federated-share',
						'id' => $parameters[1],
						'name' => $parameters[1],
					],
					'user' => [
						'type' => 'user',
						'id' => $remoteUser[0],
						'name' => $parameters[0],// Todo display name from contacts
						'server' => $remoteUser[1],
					],
				];
			case self::SUBJECT_REMOTE_SHARE_ACCEPTED:
			case self::SUBJECT_REMOTE_SHARE_DECLINED:
				$remoteUser = explode('@', $parameters[0], 2);
				return [
					'file' => $this->generateFileParameter($event->getObjectId(), $event->getObjectName()),
					'user' => [
						'type' => 'user',
						'id' => $remoteUser[0],
						'name' => $parameters[0],// Todo display name from contacts
						'server' => $remoteUser[1],
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
