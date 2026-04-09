<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Traits;

use OC\Mail\EmailValidator;
use OCP\IAppConfig;
use OCP\Mail\IEmailValidator;
use PHPUnit\Framework\TestCase;

trait EmailValidatorTrait {
	protected function getEmailValidatorWithStrictEmailCheck(): IEmailValidator {
		if (!($this instanceof TestCase)) {
			throw new \RuntimeException('This trait can only be used in a test case');
		}

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')
			->with('core', 'enforce_strict_email_check', 'yes')
			->willReturn('yes');

		return new EmailValidator($appConfig);
	}
}
