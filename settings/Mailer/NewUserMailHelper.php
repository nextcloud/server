<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Leon Klingele <leon@struktur.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OC\Settings\Mailer;

use OCP\Mail\IEMailTemplate;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Mail\IMailer;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

class NewUserMailHelper {
	/** @var Defaults */
	private $themingDefaults;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** @var IMailer */
	private $mailer;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var IConfig */
	private $config;
	/** @var ICrypto */
	private $crypto;
	/** @var string */
	private $fromAddress;

	/**
	 * @param Defaults $themingDefaults
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $l10n
	 * @param IMailer $mailer
	 * @param ISecureRandom $secureRandom
	 * @param ITimeFactory $timeFactory
	 * @param IConfig $config
	 * @param ICrypto $crypto
	 * @param string $fromAddress
	 */
	public function __construct(Defaults $themingDefaults,
								IURLGenerator $urlGenerator,
								IL10N $l10n,
								IMailer $mailer,
								ISecureRandom $secureRandom,
								ITimeFactory $timeFactory,
								IConfig $config,
								ICrypto $crypto,
								$fromAddress) {
		$this->themingDefaults = $themingDefaults;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->mailer = $mailer;
		$this->secureRandom = $secureRandom;
		$this->timeFactory = $timeFactory;
		$this->config = $config;
		$this->crypto = $crypto;
		$this->fromAddress = $fromAddress;
	}

	/**
	 * Set the IL10N object
	 *
	 * @param IL10N $l10n
	 */
	public function setL10N(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	/**
	 * @param IUser $user
	 * @param bool $generatePasswordResetToken
	 * @return IEMailTemplate
	 */
	public function generateTemplate(IUser $user, $generatePasswordResetToken = false) {
		if ($generatePasswordResetToken) {
			$token = $this->secureRandom->generate(
				21,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			);
			$tokenValue = $this->timeFactory->getTime() . ':' . $token;
			$mailAddress = (null !== $user->getEMailAddress()) ? $user->getEMailAddress() : '';
			$encryptedValue = $this->crypto->encrypt($tokenValue, $mailAddress . $this->config->getSystemValue('secret'));
			$this->config->setUserValue($user->getUID(), 'core', 'lostpassword', $encryptedValue);
			$link = $this->urlGenerator->linkToRouteAbsolute('core.lost.resetform', ['userId' => $user->getUID(), 'token' => $token]);
		} else {
			$link = $this->urlGenerator->getAbsoluteURL('/');
		}
		$displayName = $user->getDisplayName();
		$userId = $user->getUID();

		$emailTemplate = $this->mailer->createEMailTemplate('settings.Welcome', [
			'link' => $link,
			'displayname' => $displayName,
			'userid' => $userId,
			'instancename' => $this->themingDefaults->getName(),
			'resetTokenGenerated' => $generatePasswordResetToken,
		]);

		$emailTemplate->setSubject($this->l10n->t('Your %s account was created', [$this->themingDefaults->getName()]));
		$emailTemplate->addHeader();
		if ($displayName === $userId) {
			$emailTemplate->addHeading($this->l10n->t('Welcome aboard'));
		} else {
			$emailTemplate->addHeading($this->l10n->t('Welcome aboard %s', [$displayName]));
		}
		$emailTemplate->addBodyText($this->l10n->t('Welcome to your %s account, you can add, protect, and share your data.', [$this->themingDefaults->getName()]));
		$emailTemplate->addBodyText($this->l10n->t('Your username is: %s', [$userId]));
		if ($generatePasswordResetToken) {
			$leftButtonText = $this->l10n->t('Set your password');
		} else {
			$leftButtonText = $this->l10n->t('Go to %s', [$this->themingDefaults->getName()]);
		}
		$emailTemplate->addBodyButtonGroup(
			$leftButtonText,
			$link,
			$this->l10n->t('Install Client'),
			'https://nextcloud.com/install/#install-clients'
		);
		$emailTemplate->addFooter();

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
							 IEMailTemplate $emailTemplate) {
		$message = $this->mailer->createMessage();
		$message->setTo([$user->getEMailAddress() => $user->getDisplayName()]);
		$message->setFrom([$this->fromAddress => $this->themingDefaults->getName()]);
		$message->useTemplate($emailTemplate);
		$this->mailer->send($message);
	}
}
