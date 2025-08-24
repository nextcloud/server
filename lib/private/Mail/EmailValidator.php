<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Mail;

use Egulias\EmailValidator\EmailValidator as EquliasEmailValidator;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use OCP\IAppConfig;
use OCP\Mail\IEmailValidator;

class EmailValidator implements IEmailValidator {
	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	public function isValid(string $email): bool {
		if ($email === '') {
			// Shortcut: empty addresses are never valid
			return false;
		}

		$strictMailCheck = $this->appConfig->getValueString('core', 'enforce_strict_email_check', 'yes') === 'yes';

		$validator = new EquliasEmailValidator();
		$validation = $strictMailCheck ? new NoRFCWarningsValidation() : new RFCValidation();

		return $validator->isValid($email, $validation);
	}
}
