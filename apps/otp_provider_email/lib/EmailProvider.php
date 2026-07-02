<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail;

use OCA\OTPProviderEmail\AppInfo\Application;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\OneTimePassword\IOneTimePasswordProvider;
use Psr\Log\LoggerInterface;

class EmailProvider implements IOneTimePasswordProvider {
	private IL10N $l;

	public function __construct(
		IFactory $l10nFactory
	) {
		$this->l = $l10nFactory->get(Application::APP_ID);
	}

	public function getProviderId(): string {
		return Application::OTP_PROVIDER_ID;
	}

	public function getName(): string {
		return $this->l->t('Email');
	}

	public function getDescription(): string {
		return $this->l->t('Sends the OTP to an email address');
	}

	public function getRecipientPattern(): string {
		return '^[^@]+@[^@]+\.[^@]+$';
	}

	public function sendOTP(string $recipient, string $password): void {

	}

	public function maskRecipient(string $recipient): string {
		$atPos = strrpos($recipient, "@");
		if ($atPos < 5) {
			return substr($recipient, 0, 1)
				. str_pad('', $atPos - 2, '*', STR_PAD_RIGHT)
				.substr($recipient, -1);
		}
		return substr($recipient, 0, 2)
			. str_pad('', $atPos - 6, '*', STR_PAD_RIGHT)
			. substr($recipient, $atPos - 2);
	}
}

