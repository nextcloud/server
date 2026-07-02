<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail\AppInfo;

use OCA\OTPProviderEmail\Listener\GetOneTimePasswordProvidersEventListener;
use OCA\OTPProviderEmail\Listener\SendOneTimePasswordEventListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\OneTimePassword\Events\GetOneTimePasswordProvidersEvent;
use OCP\OneTimePassword\Events\SendOneTimePasswordEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'otp_provider_email';
	public const OTP_PROVIDER_ID = 'email';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {

		$context->registerEventListener(GetOneTimePasswordProvidersEvent::class, GetOneTimePasswordProvidersEventListener::class);
		$context->registerEventListener(SendOneTimePasswordEvent::class, SendOneTimePasswordEventListener::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
	}
}
