<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Mailer;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\Headers\AutoSubmitted;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

class NewUserMailHelper {
	/**
	 * @param Defaults $themingDefaults
	 * @param IURLGenerator $urlGenerator
	 * @param IFactory $l10nFactory
	 * @param IMailer $mailer
	 * @param ISecureRandom $secureRandom
	 * @param ITimeFactory $timeFactory
	 * @param IConfig $config
	 * @param ICrypto $crypto
	 * @param string $fromAddress
	 */
	public function __construct(
		private Defaults $themingDefaults,
		private IURLGenerator $urlGenerator,
		private IFactory $l10nFactory,
		private IMailer $mailer,
		private ISecureRandom $secureRandom,
		private ITimeFactory $timeFactory,
		private IConfig $config,
		private ICrypto $crypto,
		private $fromAddress,
	) {
	}

	/**
	 * @param IUser $user
	 * @param bool $generatePasswordResetToken
	 * @return IEMailTemplate
	 */
	public function generateTemplate(IUser $user, $generatePasswordResetToken = false) {
		$userId = $user->getUID();
		$lang = $this->l10nFactory->getUserLanguage($user);
		$l10n = $this->l10nFactory->get('settings', $lang);

		if ($generatePasswordResetToken) {
			$token = $this->secureRandom->generate(
				21,
				ISecureRandom::CHAR_ALPHANUMERIC
			);
			$tokenValue = $this->timeFactory->getTime() . ':' . $token;
			$mailAddress = ($user->getEMailAddress() !== null) ? $user->getEMailAddress() : '';
			$encryptedValue = $this->crypto->encrypt($tokenValue, $mailAddress . $this->config->getSystemValue('secret'));
			$this->config->setUserValue($user->getUID(), 'core', 'lostpassword', $encryptedValue);
			$link = $this->urlGenerator->linkToRouteAbsolute('core.lost.resetform', ['userId' => $user->getUID(), 'token' => $token]);
		} else {
			$link = $this->urlGenerator->getAbsoluteURL('/');
		}
		$displayName = $user->getDisplayName();

		$emailTemplate = $this->mailer->createEMailTemplate('settings.Welcome', [
			'link' => $link,
			'displayname' => $displayName,
			'userid' => $userId,
			'instancename' => $this->themingDefaults->getName(),
			'resetTokenGenerated' => $generatePasswordResetToken,
		]);

		$emailTemplate->setSubject($l10n->t('Your %s account was created', [$this->themingDefaults->getName()]));
		$emailTemplate->addHeader();
		if ($displayName === $userId) {
			$emailTemplate->addHeading($l10n->t('Welcome aboard'));
		} else {
			$emailTemplate->addHeading($l10n->t('Welcome aboard %s', [$displayName]));
		}
		$emailTemplate->addBodyText($l10n->t('Welcome to your %s account, you can add, protect, and share your data.', [$this->themingDefaults->getName()]));
		if ($user->getBackendClassName() !== 'LDAP') {
			$emailTemplate->addBodyText($l10n->t('Your Login is: %s', [$userId]));
		}
		if ($generatePasswordResetToken) {
			$leftButtonText = $l10n->t('Set your password');
		} else {
			$leftButtonText = $l10n->t('Go to %s', [$this->themingDefaults->getName()]);
		}

		$clientDownload = $this->config->getSystemValue('customclient_desktop', 'https://nextcloud.com/install/#install-clients');
		if ($clientDownload === '') {
			$emailTemplate->addBodyButton(
				$leftButtonText,
				$link
			);
		} else {
			$emailTemplate->addBodyButtonGroup(
				$leftButtonText,
				$link,
				$l10n->t('Install Client'),
				$clientDownload
			);
		}

		$emailTemplate->addFooter('', $lang);

		return $emailTemplate;
	}

	/**
	 * Sends a welcome mail to $user
	 *
	 * @param IUser $user
	 * @param IEmailTemplate $emailTemplate
	 * @throws \Exception If mail could not be sent
	 */
	public function sendMail(IUser $user,
		IEMailTemplate $emailTemplate): void {

		// Be sure to never try to send to an empty e-mail
		$email = $user->getEMailAddress();
		if ($email === null) {
			return;
		}

		$message = $this->mailer->createMessage();
		$message->setTo([$email => $user->getDisplayName()]);
		$message->setFrom([$this->fromAddress => $this->themingDefaults->getName()]);
		$message->useTemplate($emailTemplate);
		$message->setAutoSubmitted(AutoSubmitted::VALUE_AUTO_GENERATED);
		$this->mailer->send($message);
	}
}
