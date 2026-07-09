<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail;

use OCA\OTPProviderEmail\AppInfo\Application;
use OCP\Defaults;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\OneTimePassword\Exceptions\OTPSendException;
use OCP\OneTimePassword\IOneTimePasswordProvider;
use OCP\Util;

readonly class EmailProvider implements IOneTimePasswordProvider {

	public function __construct(
		private IFactory $l10nFactory,
		private IMailer $mailer,
		private Defaults $defaults,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getProviderId(): string {
		return Application::OTP_PROVIDER_ID;
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Email');
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Sends the OTP to an email address');
	}

	/**
	 * @inheritDoc
	 */
	public function getRecipientPattern(): string {
		return '^[^@]+@[^@]+\.[^@]+$';
	}

	/**
	 * @inheritDoc
	 */
	public function maskRecipient(string $recipient): string {
		$atPos = strrpos($recipient, '@');
		$dotPos = strrpos($recipient, '.');
		if ($atPos < 5) {
			$userMasked = substr($recipient, 0, 1)
				. str_pad('', $atPos - 1, '*');
		} elseif ($atPos < 8) {
			$userMasked = substr($recipient, 0, 1)
				. str_pad('', $atPos - 2, '*')
				. substr($recipient, $atPos - 1, 1);
		} else {
			$userMasked = substr($recipient, 0, 2)
				. str_pad('', $atPos - 4, '*')
				. substr($recipient, $atPos - 2, 2);
		}
		if ($dotPos - $atPos < 6) {
			$hostMasked = substr($recipient, $atPos + 1, 1)
				. str_pad('', $dotPos - $atPos - 2, '*')
				. substr($recipient, $dotPos);
		} elseif ($dotPos - $atPos < 9) {
			$hostMasked = substr($recipient, $atPos + 1, 1)
				. str_pad('', $dotPos - $atPos - 3, '*')
				. substr($recipient, $dotPos - 1);
		} else {
			$hostMasked = substr($recipient, $atPos + 1, 2)
				. str_pad('', $dotPos - $atPos - 5, '*')
				. substr($recipient, $dotPos - 2);
		}
		return $userMasked . '@' . $hostMasked;
	}

	/**
	 * @inheritDoc
	 */
	public function sendOTP(string $recipient, string $password, ?string $message): void {
		try {
			$failedRecipients = $this->sendEmail($recipient, $password, $message);
		} catch (\Exception $e) {
			throw new OTPSendException('Failed to send OTP email: ', 0, $e);
		}
		if (!empty($failedRecipients)) {
			$errMsg = 'Could not send OTP to the following recipients: [' . join(', ', $failedRecipients) . ']';
			throw new OTPSendException($errMsg);
		}
	}

	/**
	 * @param string $recipient
	 * @param string $password
	 * @param string|null $recipientMsg
	 * @return array
	 * @throws \Exception
	 */
	protected function sendEmail(string $recipient, string $password, ?string $recipientMsg): array {
		$l = $this->l10nFactory->get(Application::APP_ID);
		$message = $this->mailer->createMessage();
		$emailTemplate = $this->mailer->createEMailTemplate(Application::APP_ID . '.OTPMessage', [
			'otp' => $password
		]);
		$emailTemplate->setSubject($l->t('Your One-time Password'));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($l->t('You received a one-time password'));
		$msgParts = [
			(htmlspecialchars($recipientMsg) ?? $l->t('A one-time password for a Nextcloud resource has been requested for your email address.')),
			$l->t('If you have not requested a one-time password, you can ignore this message.')
		];

		$emailTemplate->addBodyText(
			'<p>' . $msgParts[0] . '</p>'
			. '<br><br><p><pre>' . htmlspecialchars($password) . '</pre></p><br><br>'
			. '<p>' . $msgParts[1] . '</p>',
			$msgParts[0] . '\n\r\n\r' . htmlspecialchars($password) . '\n\r\n\r' . $msgParts[1],
		);

		$instanceName = $this->defaults->getName();
		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $instanceName]);
		$emailTemplate->addFooter();

		$message->setTo([$recipient]);
		$message->useTemplate($emailTemplate);
		return $this->mailer->send($message);
	}
}
