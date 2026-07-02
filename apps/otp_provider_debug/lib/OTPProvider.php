<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderDebug;

use OCA\OTPProviderDebug\AppInfo\Application;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\OneTimePassword\IOneTimePasswordProvider;

class OTPProvider implements IOneTimePasswordProvider {
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
		return $this->l->t('Nextcloud Logs');
	}

	public function getDescription(): string {
		return $this->l->t('Writes the OTP to the nextcloud logs');
	}

	public function getRecipientPattern(): string {
		return '.*';
	}

	public function maskRecipient(string $recipient): string {
		return $recipient;
	}

}
