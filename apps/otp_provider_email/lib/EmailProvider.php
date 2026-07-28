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

class EmailProvider implements IOneTimePasswordProvider {
	private IL10N $l;

	public function __construct(
		IFactory $l10nFactory,
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
}
