<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OC\Settings\Activity;

use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class Provider implements IProvider {

	const PASSWORD_CHANGED_BY = 'password_changed_by';
	const PASSWORD_CHANGED_SELF = 'password_changed_self';
	const PASSWORD_RESET = 'password_changed';
	const EMAIL_CHANGED_BY = 'email_changed_by';
	const EMAIL_CHANGED_SELF = 'email_changed_self';
	const EMAIL_CHANGED = 'email_changed';
	public const APP_TOKEN_CREATED = 'app_token_created';
	public const APP_TOKEN_UPDATED = 'app_token_updated';
	public const APP_TOKEN_DELETED = 'app_token_deleted';

	/** @var IFactory */
	protected $languageFactory;

	/** @var IL10N */
	protected $l;

	/** @var IURLGenerator */
	protected $url;

	/** @var IUserManager */
	protected $userManager;

	/** @var IManager */
	private $activityManager;

	/** @var string[] cached displayNames - key is the UID and value the displayname */
	protected $displayNames = [];

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $url
	 * @param IUserManager $userManager
	 * @param IManager $activityManager
	 */
	public function __construct(IFactory $languageFactory, IURLGenerator $url, IUserManager $userManager, IManager $activityManager) {
		$this->languageFactory = $languageFactory;
		$this->url = $url;
		$this->userManager = $userManager;
		$this->activityManager = $activityManager;
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
		if ($event->getApp() !== 'settings') {
			throw new \InvalidArgumentException();
		}

		$this->l = $this->languageFactory->get('settings', $language);

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('settings', 'personal.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('settings', 'personal.svg')));
		}

		if ($event->getSubject() === self::PASSWORD_CHANGED_BY) {
			$subject = $this->l->t('{actor} changed your password');
		} else if ($event->getSubject() === self::PASSWORD_CHANGED_SELF) {
			$subject = $this->l->t('You changed your password');
		} else if ($event->getSubject() === self::PASSWORD_RESET) {
			$subject = $this->l->t('Your password was reset by an administrator');

		} else if ($event->getSubject() === self::EMAIL_CHANGED_BY) {
			$subject = $this->l->t('{actor} changed your email address');
		} else if ($event->getSubject() === self::EMAIL_CHANGED_SELF) {
			$subject = $this->l->t('You changed your email address');
		} else if ($event->getSubject() === self::EMAIL_CHANGED) {
			$subject = $this->l->t('Your email address was changed by an administrator');

		} else if ($event->getSubject() === self::APP_TOKEN_CREATED) {
			$subject = $this->l->t('You created app password "%1$s"', $event->getSubjectParameters());
		} else if ($event->getSubject() === self::APP_TOKEN_UPDATED) {
			$subject = $this->l->t('You updated app password "%1$s"', $event->getSubjectParameters());
		} else if ($event->getSubject() === self::APP_TOKEN_DELETED) {
			$subject = $this->l->t('You deleted an app token');

		} else {
			throw new \InvalidArgumentException();
		}

		$parsedParameters = $this->getParameters($event);
		$this->setSubjects($event, $subject, $parsedParameters);

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::PASSWORD_CHANGED_SELF:
			case self::PASSWORD_RESET:
			case self::EMAIL_CHANGED_SELF:
			case self::EMAIL_CHANGED:
			case self::APP_TOKEN_CREATED:
			case self::APP_TOKEN_UPDATED:
			case self::APP_TOKEN_DELETED:
				return [];
			case self::PASSWORD_CHANGED_BY:
			case self::EMAIL_CHANGED_BY:
				return [
					'actor' => $this->generateUserParameter($parameters[0]),
				];
		}

		throw new \InvalidArgumentException();
	}

	/**
	 * @param IEvent $event
	 * @param string $subject
	 * @param array $parameters
	 * @throws \InvalidArgumentException
	 */
	protected function setSubjects(IEvent $event, $subject, array $parameters) {
		$placeholders = $replacements = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			$replacements[] = $parameter['name'];
		}

		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $parameters);
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
		}

		return $uid;
	}
}
