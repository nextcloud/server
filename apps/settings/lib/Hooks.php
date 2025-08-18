<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings;

use OCA\Settings\Activity\Provider;
use OCP\Activity\IManager as IActivityManager;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;

class Hooks {

	public function __construct(
		protected IActivityManager $activityManager,
		protected IGroupManager $groupManager,
		protected IUserManager $userManager,
		protected IUserSession $userSession,
		protected IURLGenerator $urlGenerator,
		protected IMailer $mailer,
		protected IConfig $config,
		protected IFactory $languageFactory,
		protected Defaults $defaults,
	) {
	}

	/**
	 * @param string $uid
	 * @throws \InvalidArgumentException
	 * @throws \BadMethodCallException
	 * @throws \Exception
	 */
	public function onChangePassword($uid) {
		$user = $this->userManager->get($uid);

		if (!$user instanceof IUser || $user->getLastLogin() === 0) {
			// User didn't login, so don't create activities and emails.
			return;
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('settings')
			->setType('personal_settings')
			->setAffectedUser($user->getUID());

		$instanceName = $this->defaults->getName();
		$instanceUrl = $this->urlGenerator->getAbsoluteURL('/');
		$language = $this->languageFactory->getUserLanguage($user);
		$l = $this->languageFactory->get('settings', $language);

		$actor = $this->userSession->getUser();
		if ($actor instanceof IUser) {
			if ($actor->getUID() !== $user->getUID()) {
				// Admin changed the password through the user panel
				$text = $l->t('%1$s changed your password on %2$s.', [$actor->getDisplayName(), $instanceUrl]);
				$event->setAuthor($actor->getUID())
					->setSubject(Provider::PASSWORD_CHANGED_BY, [$actor->getUID()]);
			} else {
				// User changed their password themselves through settings
				$text = $l->t('Your password on %s was changed.', [$instanceUrl]);
				$event->setAuthor($actor->getUID())
					->setSubject(Provider::PASSWORD_CHANGED_SELF);
			}
		} else {
			if (\OC::$CLI) {
				// Admin used occ to reset the password
				$text = $l->t('Your password on %s was reset by an administrator.', [$instanceUrl]);
				$event->setSubject(Provider::PASSWORD_RESET);
			} else {
				// User reset their password from Lost page
				$text = $l->t('Your password on %s was reset.', [$instanceUrl]);
				$event->setSubject(Provider::PASSWORD_RESET_SELF);
			}
		}

		$this->activityManager->publish($event);

		if ($user->getEMailAddress() !== null) {
			$template = $this->mailer->createEMailTemplate('settings.PasswordChanged', [
				'displayname' => $user->getDisplayName(),
				'emailAddress' => $user->getEMailAddress(),
				'instanceUrl' => $instanceUrl,
			]);

			$template->setSubject($l->t('Password for %1$s changed on %2$s', [$user->getDisplayName(), $instanceName]));
			$template->addHeader();
			$template->addHeading($l->t('Password changed for %s', [$user->getDisplayName()]), false);
			$template->addBodyText($text . ' ' . $l->t('If you did not request this, please contact an administrator.'));
			$template->addFooter();


			$message = $this->mailer->createMessage();
			$message->setTo([$user->getEMailAddress() => $user->getDisplayName()]);
			$message->useTemplate($template);
			$this->mailer->send($message);
		}
	}

	/**
	 * @param IUser $user
	 * @param string|null $oldMailAddress
	 * @throws \InvalidArgumentException
	 * @throws \BadMethodCallException
	 */
	public function onChangeEmail(IUser $user, $oldMailAddress) {
		if ($oldMailAddress === $user->getEMailAddress()
			|| $user->getLastLogin() === 0) {
			// Email didn't really change or user didn't login,
			// so don't create activities and emails.
			return;
		}

		$event = $this->activityManager->generateEvent();
		$event->setApp('settings')
			->setType('personal_settings')
			->setAffectedUser($user->getUID());

		$instanceName = $this->defaults->getName();
		$instanceUrl = $this->urlGenerator->getAbsoluteURL('/');
		$language = $this->languageFactory->getUserLanguage($user);
		$l = $this->languageFactory->get('settings', $language);

		$actor = $this->userSession->getUser();
		if ($actor instanceof IUser) {
			$subject = Provider::EMAIL_CHANGED_SELF;
			if ($actor->getUID() !== $user->getUID()) {
				// set via the OCS API
				if ($this->config->getAppValue('settings', 'disable_activity.email_address_changed_by_admin', 'no') === 'yes') {
					return;
				}
				$subject = Provider::EMAIL_CHANGED;
			}
			$text = $l->t('Your email address on %s was changed.', [$instanceUrl]);
			$event->setAuthor($actor->getUID())
				->setSubject($subject);
		} else {
			// set with occ
			if ($this->config->getAppValue('settings', 'disable_activity.email_address_changed_by_admin', 'no') === 'yes') {
				return;
			}
			$text = $l->t('Your email address on %s was changed by an administrator.', [$instanceUrl]);
			$event->setSubject(Provider::EMAIL_CHANGED);
		}
		$this->activityManager->publish($event);


		if ($oldMailAddress !== null) {
			$template = $this->mailer->createEMailTemplate('settings.EmailChanged', [
				'displayname' => $user->getDisplayName(),
				'newEMailAddress' => $user->getEMailAddress(),
				'oldEMailAddress' => $oldMailAddress,
				'instanceUrl' => $instanceUrl,
			]);

			$template->setSubject($l->t('Email address for %1$s changed on %2$s', [$user->getDisplayName(), $instanceName]));
			$template->addHeader();
			$template->addHeading($l->t('Email address changed for %s', [$user->getDisplayName()]), false);
			$template->addBodyText($text . ' ' . $l->t('If you did not request this, please contact an administrator.'));
			if ($user->getEMailAddress()) {
				$template->addBodyText($l->t('The new email address is %s', [$user->getEMailAddress()]));
			}
			$template->addFooter();


			$message = $this->mailer->createMessage();
			$message->setTo([$oldMailAddress => $user->getDisplayName()]);
			$message->useTemplate($template);
			$this->mailer->send($message);
		}
	}
}
