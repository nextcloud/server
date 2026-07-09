<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail\AppInfo;

use OCA\OTPProviderEmail\EmailProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Defaults;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\OneTimePassword\IManager as IOTPManager;

class Application extends App implements IBootstrap {
	public const APP_ID = 'otp_provider_email';
	public const OTP_PROVIDER_ID = 'email';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
		$context->injectFn(function (
			IOTPManager $otpManager,
			IFactory $l10nFactory,
			IMailer $mailer,
			Defaults $defaults,
		) {
			$otpManager->registerOTPProvider(new EmailProvider($l10nFactory, $mailer, $defaults));
		});
	}

	public function register(IRegistrationContext $context): void {
	}
}
