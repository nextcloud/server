<?php

declare(strict_types=1);

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
	protected IL10N $l;
	protected array $contactNames = [];

	public const SUBJECT_SHARED_EMAIL_SELF = 'shared_with_email_self';
	public const SUBJECT_SHARED_EMAIL_BY = 'shared_with_email_by';
	public const SUBJECT_SHARED_EMAIL_PASSWORD_SEND = 'shared_with_email_password_send';
	public const SUBJECT_SHARED_EMAIL_PASSWORD_SEND_SELF = 'shared_with_email_password_send_self';
	public const SUBJECT_UNSHARED_EMAIL_SELF = 'unshared_with_email_self';
	public const SUBJECT_UNSHARED_EMAIL_BY = 'unshared_with_email_by';

	public function __construct(
		protected IFactory $languageFactory,
		protected IURLGenerator $url,
		protected IManager $activityManager,
		protected IUserManager $userManager,
		protected IContactsManager $contactsManager,
	) {
	}

	/**
	 * @throws UnknownActivityException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null): IEvent {
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
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseShortVersion(IEvent $event): IEvent {
		$parsedParameters = $this->getParsedParameters($event);

		$iconFile = $this->activityManager->getRequirePNG() ? 'actions/share.png' : 'actions/share.svg';
		$iconUrl = $this->url->getAbsoluteURL($this->url->imagePath('core', $iconFile));

		[$subject, $params] = match ($event->getSubject()) {
			self::SUBJECT_SHARED_EMAIL_SELF => [
				$this->l->t('Shared with {email}'),
				['email' => $parsedParameters['email']]
			],
			self::SUBJECT_SHARED_EMAIL_BY => [
				$this->l->t('Shared with {email} by {actor}'),
				['email' => $parsedParameters['email'], 'actor' => $parsedParameters['actor']]
			],
			self::SUBJECT_UNSHARED_EMAIL_SELF => [
				$this->l->t('Unshared from {email}'),
				['email' => $parsedParameters['email']]
			],
			self::SUBJECT_UNSHARED_EMAIL_BY => [
				$this->l->t('Unshared from {email} by {actor}'),
				['email' => $parsedParameters['email'], 'actor' => $parsedParameters['actor']]
			],
			self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND => [
				$this->l->t('Password for mail share sent to {email}'),
				['email' => $parsedParameters['email']]
			],
			self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND_SELF => [
				$this->l->t('Password for mail share sent to you'),
				[]
			],
			default => throw new UnknownActivityException(),
		};

		$event->setRichSubject($subject, $params);
		$event->setIcon($iconUrl);

		return $event;
	}

	/**
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseLongVersion(IEvent $event): IEvent {
		$parsedParameters = $this->getParsedParameters($event);

		$iconFile = $this->activityManager->getRequirePNG() ? 'actions/share.png' : 'actions/share.svg';
		$iconUrl = $this->url->getAbsoluteURL($this->url->imagePath('core', $iconFile));

		$subject = match ($event->getSubject()) {
			self::SUBJECT_SHARED_EMAIL_SELF
				=> $this->l->t('You shared {file} with {email} by mail'),
			self::SUBJECT_SHARED_EMAIL_BY
				=> $this->l->t('{actor} shared {file} with {email} by mail'),
			self::SUBJECT_UNSHARED_EMAIL_SELF
				=> $this->l->t('You unshared {file} from {email} by mail'),
			self::SUBJECT_UNSHARED_EMAIL_BY
				=> $this->l->t('{actor} unshared {file} from {email} by mail'),
			self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND
				=> $this->l->t('Password to access {file} was sent to {email}'),
			self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND_SELF
				=> $this->l->t('Password to access {file} was sent to you'),
			default => throw new UnknownActivityException(),
		};

		$event->setRichSubject($subject, $parsedParameters);
		$event->setIcon($iconUrl);

		return $event;
	}

	protected function getParsedParameters(IEvent $event): array {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();
		$objectId = $event->getObjectId();

		return match ($subject) {
			self::SUBJECT_SHARED_EMAIL_SELF,
			self::SUBJECT_UNSHARED_EMAIL_SELF,
			self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND => [
				'file' => $this->generateFileParameter($objectId, $parameters[0]),
				'email' => $this->generateEmailParameter($parameters[1]),
			],

			self::SUBJECT_SHARED_EMAIL_BY,
			self::SUBJECT_UNSHARED_EMAIL_BY => [
				'file' => $this->generateFileParameter($objectId, $parameters[0]),
				'email' => $this->generateEmailParameter($parameters[1]),
				'actor' => $this->generateUserParameter($parameters[2]),
			],

			self::SUBJECT_SHARED_EMAIL_PASSWORD_SEND_SELF => [
				'file' => $this->generateFileParameter($objectId, $parameters[0]),
			],

			default => throw new UnknownActivityException(),
		};
	}

	/**
	 * @return array<string,string>
	 */
	protected function generateFileParameter(int $id, string $path): array {
		return [
			'type' => 'file',
			'id' => (string)$id,
			'name' => basename($path),
			'path' => trim($path, '/'),
			'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $id]),
		];
	}

	protected function generateEmailParameter(string $email): array {
		if (!isset($this->contactNames[$email])) {
			$this->contactNames[$email] = $this->getContactName($email);
		}

		return [
			'type' => 'email',
			'id' => $email,
			'name' => $this->contactNames[$email],
		];
	}

	protected function generateUserParameter(string $uid): array {
		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->userManager->getDisplayName($uid) ?? $uid,
		];
	}

	protected function getContactName(string $email): string {
		$addressBookContacts = $this->contactsManager->search($email, ['EMAIL'], [
			'limit' => 1,
			'enumeration' => false,
			'fullmatch' => false,
			'strict_search' => true,
		]);

		foreach ($addressBookContacts as $contact) {
			if (isset($contact['isLocalSystemBook']) || isset($contact['isVirtualAddressbook'])) {
				continue;
			}

			if (in_array($email, $contact['EMAIL'])) {
				return $contact['FN'];
			}
		}

		return $email;
	}
}
