<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Activity;

use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class Provider implements IProvider {
	public const PASSWORD_CHANGED_BY = 'password_changed_by';
	public const PASSWORD_CHANGED_SELF = 'password_changed_self';
	public const PASSWORD_RESET = 'password_changed';
	public const PASSWORD_RESET_SELF = 'password_reset_self';
	public const EMAIL_CHANGED_BY = 'email_changed_by';
	public const EMAIL_CHANGED_SELF = 'email_changed_self';
	public const EMAIL_CHANGED = 'email_changed';
	public const APP_TOKEN_CREATED = 'app_token_created';
	public const APP_TOKEN_DELETED = 'app_token_deleted';
	public const APP_TOKEN_RENAMED = 'app_token_renamed';
	public const APP_TOKEN_FILESYSTEM_GRANTED = 'app_token_filesystem_granted';
	public const APP_TOKEN_FILESYSTEM_REVOKED = 'app_token_filesystem_revoked';

	/** @var IL10N */
	protected $l;

	public function __construct(
		protected IFactory $languageFactory,
		protected IURLGenerator $url,
		protected IUserManager $userManager,
		private IManager $activityManager,
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
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null): IEvent {
		if ($event->getApp() !== 'settings') {
			throw new UnknownActivityException('Unknown app');
		}

		$this->l = $this->languageFactory->get('settings', $language);

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('settings', 'personal.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('settings', 'personal.svg')));
		}

		if ($event->getSubject() === self::PASSWORD_CHANGED_BY) {
			$subject = $this->l->t('{actor} changed your password');
		} elseif ($event->getSubject() === self::PASSWORD_CHANGED_SELF) {
			$subject = $this->l->t('You changed your password');
		} elseif ($event->getSubject() === self::PASSWORD_RESET) {
			$subject = $this->l->t('Your password was reset by an administrator');
		} elseif ($event->getSubject() === self::PASSWORD_RESET_SELF) {
			$subject = $this->l->t('Your password was reset');
		} elseif ($event->getSubject() === self::EMAIL_CHANGED_BY) {
			$subject = $this->l->t('{actor} changed your email address');
		} elseif ($event->getSubject() === self::EMAIL_CHANGED_SELF) {
			$subject = $this->l->t('You changed your email address');
		} elseif ($event->getSubject() === self::EMAIL_CHANGED) {
			$subject = $this->l->t('Your email address was changed by an administrator');
		} elseif ($event->getSubject() === self::APP_TOKEN_CREATED) {
			if ($event->getAffectedUser() === $event->getAuthor()) {
				$subject = $this->l->t('You created an app password for a session named "{token}"');
			} else {
				$subject = $this->l->t('An administrator created an app password for a session named "{token}"');
			}
		} elseif ($event->getSubject() === self::APP_TOKEN_DELETED) {
			$subject = $this->l->t('You deleted app password "{token}"');
		} elseif ($event->getSubject() === self::APP_TOKEN_RENAMED) {
			$subject = $this->l->t('You renamed app password "{token}" to "{newToken}"');
		} elseif ($event->getSubject() === self::APP_TOKEN_FILESYSTEM_GRANTED) {
			$subject = $this->l->t('You granted filesystem access to app password "{token}"');
		} elseif ($event->getSubject() === self::APP_TOKEN_FILESYSTEM_REVOKED) {
			$subject = $this->l->t('You revoked filesystem access from app password "{token}"');
		} else {
			throw new UnknownActivityException('Unknown subject');
		}

		$parsedParameters = $this->getParameters($event);
		$this->setSubjects($event, $subject, $parsedParameters);

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @return array
	 * @throws UnknownActivityException
	 */
	protected function getParameters(IEvent $event): array {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::PASSWORD_CHANGED_SELF:
			case self::PASSWORD_RESET:
			case self::PASSWORD_RESET_SELF:
			case self::EMAIL_CHANGED_SELF:
			case self::EMAIL_CHANGED:
				return [];
			case self::PASSWORD_CHANGED_BY:
			case self::EMAIL_CHANGED_BY:
				return [
					'actor' => $this->generateUserParameter($parameters[0]),
				];
			case self::APP_TOKEN_CREATED:
			case self::APP_TOKEN_DELETED:
			case self::APP_TOKEN_FILESYSTEM_GRANTED:
			case self::APP_TOKEN_FILESYSTEM_REVOKED:
				return [
					'token' => [
						'type' => 'highlight',
						'id' => (string)$event->getObjectId(),
						'name' => $parameters['name'],
					]
				];
			case self::APP_TOKEN_RENAMED:
				return [
					'token' => [
						'type' => 'highlight',
						'id' => (string)$event->getObjectId(),
						'name' => $parameters['name'],
					],
					'newToken' => [
						'type' => 'highlight',
						'id' => (string)$event->getObjectId(),
						'name' => $parameters['newName'],
					]
				];
		}

		throw new UnknownActivityException('Unknown subject');
	}

	protected function setSubjects(IEvent $event, string $subject, array $parameters): void {
		$event->setRichSubject($subject, $parameters);
	}

	protected function generateUserParameter(string $uid): array {
		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->userManager->getDisplayName($uid) ?? $uid,
		];
	}
}
