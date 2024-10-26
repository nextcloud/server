<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ShareByMail;

use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\Contacts\IManager as IContactsManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class Activity implements IProvider {
	/** @var IL10N */
	protected $l;

	/** @var array */
	protected $contactNames = [];

	public const SUBJECT_SHARED_EMAIL_SELF = 'shared_with_email_self';
	public const SUBJECT_SHARED_EMAIL_BY = 'shared_with_email_by';
	public const SUBJECT_SHARED_EMAIL_PASSWORD_SEND = 'shared_with_email_password_send';
	public const SUBJECT_SHARED_EMAIL_PASSWORD_SEND_SELF = 'shared_with_email_password_send_self';
	public const SUBJECT_UNSHARED_EMAIL_SELF = 'unshared_with_email_self';
	public const SUBJECT_UNSHARED_EMAIL_BY = 'unshared_with_email_by';

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param IUserManager $userManager
	 * @param IContactsManager $contactsManager
	 */
	public function __construct(
		protected IFactory $languageFactory,
		protected IURLGenerator $url,
		protected IManager $activityManager,
		protected IUserManager $userManager,
		protected IContactsManager $contactsManager,
	) {
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws UnknownActivityException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== 'sharebymail') {
			throw new UnknownActivityException();
		}

		$this->l = $this->languageFactory->get('sharebymail', $language);

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
			$event->setRichSubject($this->l->t('Shared with {email}'), [
				'email' => $parsedParameters['email'],
			]);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_BY) {
			$event->setRichSubject($this->l->t('Shared with {email} by {actor}'), [
				'email' => $parsedParameters['email'],
				'actor' => $parsedParameters['actor'],
			]);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_EMAIL_SELF) {
			$event->setRichSubject($this->l->t('Unshared from {email}'), [
				'email' => $parsedParameters['email'],
			]);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_EMAIL_BY) {
			$event->setRichSubject($this->l->t('Unshared from {email} by {actor}'), [
				'email' => $parsedParameters['email'],
				'actor' => $parsedParameters['actor'],
			]);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND) {
			$event->setRichSubject($this->l->t('Password for mail share sent to {email}'), [
				'email' => $parsedParameters['email']
			]);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND_SELF) {
			$event->setRichSubject($this->l->t('Password for mail share sent to you'));
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
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
			$event->setRichSubject($this->l->t('You shared {file} with {email} by mail'), $parsedParameters);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_BY) {
			$event->setRichSubject($this->l->t('{actor} shared {file} with {email} by mail'), $parsedParameters);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_EMAIL_SELF) {
			$event->setRichSubject($this->l->t('You unshared {file} from {email} by mail'), $parsedParameters);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_EMAIL_BY) {
			$event->setRichSubject($this->l->t('{actor} unshared {file} from {email} by mail'), $parsedParameters);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND) {
			$event->setRichSubject($this->l->t('Password to access {file} was sent to {email}'), $parsedParameters);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} elseif ($event->getSubject() === self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND_SELF) {
			$event->setRichSubject($this->l->t('Password to access {file} was sent to you'), $parsedParameters);
			if ($this->activityManager->getRequirePNG()) {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
			} else {
				$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
			}
		} else {
			throw new \InvalidArgumentException();
		}

		return $event;
	}

	protected function getParsedParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::SUBJECT_SHARED_EMAIL_SELF:
			case self::SUBJECT_UNSHARED_EMAIL_SELF:
				return [
					'file' => $this->generateFileParameter($event->getObjectId(), $parameters[0]),
					'email' => $this->generateEmailParameter($parameters[1]),
				];
			case self::SUBJECT_SHARED_EMAIL_BY:
			case self::SUBJECT_UNSHARED_EMAIL_BY:
				return [
					'file' => $this->generateFileParameter($event->getObjectId(), $parameters[0]),
					'email' => $this->generateEmailParameter($parameters[1]),
					'actor' => $this->generateUserParameter($parameters[2]),
				];
			case self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND:
				return [
					'file' => $this->generateFileParameter($event->getObjectId(), $parameters[0]),
					'email' => $this->generateEmailParameter($parameters[1]),
				];
			case self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND_SELF:
				return [
					'file' => $this->generateFileParameter($event->getObjectId(), $parameters[0]),
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
			'path' => trim($path, '/'),
			'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $id]),
		];
	}

	/**
	 * @param string $email
	 * @return array
	 */
	protected function generateEmailParameter($email) {
		if (!isset($this->contactNames[$email])) {
			$this->contactNames[$email] = $this->getContactName($email);
		}

		return [
			'type' => 'email',
			'id' => $email,
			'name' => $this->contactNames[$email],
		];
	}

	/**
	 * @param string $uid
	 * @return array
	 */
	protected function generateUserParameter($uid) {
		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->userManager->getDisplayName($uid) ?? $uid,
		];
	}

	/**
	 * @param string $email
	 * @return string
	 */
	protected function getContactName($email) {
		$addressBookContacts = $this->contactsManager->search($email, ['EMAIL'], [
			'limit' => 1,
			'enumeration' => false,
			'fullmatch' => false,
			'strict_search' => true,
		]);

		foreach ($addressBookContacts as $contact) {
			if (isset($contact['isLocalSystemBook'])) {
				continue;
			}

			if (in_array($email, $contact['EMAIL'])) {
				return $contact['FN'];
			}
		}

		return $email;
	}
}
