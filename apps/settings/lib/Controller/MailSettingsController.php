<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Settings\Controller;

use OCA\Settings\Settings\Admin\Overview;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Mail\IMailer;

class MailSettingsController extends Controller {

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param IUserSession $userSession
	 * @param IURLGenerator $urlGenerator,
	 * @param IMailer $mailer
	 */
	public function __construct(
		$appName,
		IRequest $request,
		private IL10N $l10n,
		private IConfig $config,
		private IUserSession $userSession,
		private IURLGenerator $urlGenerator,
		private IMailer $mailer,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Sets the email settings
	 */
	#[AuthorizedAdminSetting(settings: Overview::class)]
	#[PasswordConfirmationRequired]
	public function setMailSettings(
		string $mail_domain,
		string $mail_from_address,
		string $mail_smtpmode,
		string $mail_smtpsecure,
		string $mail_smtphost,
		?string $mail_smtpauth,
		string $mail_smtpport,
		string $mail_sendmailmode,
	): DataResponse {
		$mail_smtpauth = $mail_smtpauth == '1';

		$configs = [
			'mail_domain' => $mail_domain,
			'mail_from_address' => $mail_from_address,
			'mail_smtpmode' => $mail_smtpmode,
			'mail_smtpsecure' => $mail_smtpsecure,
			'mail_smtphost' => $mail_smtphost,
			'mail_smtpauth' => $mail_smtpauth,
			'mail_smtpport' => $mail_smtpport,
			'mail_sendmailmode' => $mail_sendmailmode,
		];
		foreach ($configs as $key => $value) {
			$configs[$key] = empty($value) ? null : $value;
		}

		// Delete passwords from config in case no auth is specified
		if (!$mail_smtpauth) {
			$configs['mail_smtpname'] = null;
			$configs['mail_smtppassword'] = null;
		}

		$this->config->setSystemValues($configs);

		$this->config->setAppValue('core', 'emailTestSuccessful', '0');

		return new DataResponse();
	}

	/**
	 * Store the credentials used for SMTP in the config
	 *
	 * @param string $mail_smtpname
	 * @param string $mail_smtppassword
	 * @return DataResponse
	 */
	#[AuthorizedAdminSetting(settings: Overview::class)]
	#[PasswordConfirmationRequired]
	public function storeCredentials($mail_smtpname, $mail_smtppassword) {
		if ($mail_smtppassword === '********') {
			return new DataResponse($this->l10n->t('Invalid SMTP password.'), Http::STATUS_BAD_REQUEST);
		}

		$this->config->setSystemValues([
			'mail_smtpname' => $mail_smtpname,
			'mail_smtppassword' => $mail_smtppassword,
		]);

		$this->config->setAppValue('core', 'emailTestSuccessful', '0');

		return new DataResponse();
	}

	/**
	 * Send a mail to test the settings
	 * @return DataResponse
	 */
	#[AuthorizedAdminSetting(settings: Overview::class)]
	public function sendTestMail() {
		$email = $this->config->getUserValue($this->userSession->getUser()->getUID(), $this->appName, 'email', '');
		if (!empty($email)) {
			try {
				$displayName = $this->userSession->getUser()->getDisplayName();

				$template = $this->mailer->createEMailTemplate('settings.TestEmail', [
					'displayname' => $displayName,
				]);

				$template->setSubject($this->l10n->t('Email setting test'));
				$template->addHeader();
				$template->addHeading($this->l10n->t('Well done, %s!', [$displayName]));
				$template->addBodyText($this->l10n->t('If you received this email, the email configuration seems to be correct.'));
				$template->addFooter();

				$message = $this->mailer->createMessage();
				$message->setTo([$email => $displayName]);
				$message->useTemplate($template);
				$errors = $this->mailer->send($message);
				if (!empty($errors)) {
					$this->config->setAppValue('core', 'emailTestSuccessful', '0');
					throw new \RuntimeException($this->l10n->t('Email could not be sent. Check your mail server log'));
				}
				// Store the successful config in the app config
				$this->config->setAppValue('core', 'emailTestSuccessful', '1');
				return new DataResponse();
			} catch (\Exception $e) {
				$this->config->setAppValue('core', 'emailTestSuccessful', '0');
				return new DataResponse($this->l10n->t('A problem occurred while sending the email. Please revise your settings. (Error: %s)', [$e->getMessage()]), Http::STATUS_BAD_REQUEST);
			}
		}

		$this->config->setAppValue('core', 'emailTestSuccessful', '0');
		return new DataResponse($this->l10n->t('You need to set your account email before being able to send test emails. Go to %s for that.', [$this->urlGenerator->linkToRouteAbsolute('settings.PersonalSettings.index')]), Http::STATUS_BAD_REQUEST);
	}
}
