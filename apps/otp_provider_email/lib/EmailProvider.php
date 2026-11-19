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
		$atPos = mb_strrpos($recipient, '@');
		if ($atPos === false) {
			return $this->maskPart($recipient);
		}
		$domain = mb_substr($recipient, $atPos + 1);
		$dotPos = mb_strrpos($domain, '.');
		$host = $dotPos === false ? $domain : mb_substr($domain, 0, $dotPos);
		$topLevelDomain = $dotPos === false ? '' : mb_substr($domain, $dotPos);
		return $this->maskPart(mb_substr($recipient, 0, $atPos)) . '@' . $this->maskPart($host) . $topLevelDomain;
	}

	/**
	 * Mask all but the first and last characters of a part of an email address
	 */
	private function maskPart(string $part): string {
		$length = mb_strlen($part);
		if ($length < 5) {
			return mb_substr($part, 0, 1) . str_repeat('*', max($length - 1, 0));
		}
		if ($length < 8) {
			return mb_substr($part, 0, 1) . str_repeat('*', $length - 2) . mb_substr($part, -1);
		}
		return mb_substr($part, 0, 2) . str_repeat('*', $length - 4) . mb_substr($part, -2);
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
		$emailTemplate->addBodyText($recipientMsg ?? $l->t('A one-time password for a Nextcloud resource has been requested for your email address.'));
		$emailTemplate->addBodyText('<strong>' . htmlspecialchars($password) . '</strong>', $password);
		$emailTemplate->addBodyText($l->t('If you have not requested a one-time password, you can ignore this message.'));

		$instanceName = $this->defaults->getName();
		$message->setFrom([Util::getDefaultEmailAddress($instanceName) => $instanceName]);
		$emailTemplate->addFooter();

		$message->setTo([$recipient]);
		$message->useTemplate($emailTemplate);
		return $this->mailer->send($message);
	}
}
