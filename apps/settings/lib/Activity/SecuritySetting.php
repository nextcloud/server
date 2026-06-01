<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Activity;

use OCP\Activity\ISetting;
use OCP\IL10N;

class SecuritySetting implements ISetting {

	public function __construct(
		private IL10N $l10n,
	) {
	}

	#[\Override]
	public function canChangeMail() {
		return false;
	}

	#[\Override]
	public function canChangeStream() {
		return false;
	}

	#[\Override]
	public function getIdentifier() {
		return 'security';
	}

	#[\Override]
	public function getName() {
		return $this->l10n->t('Security');
	}

	#[\Override]
	public function getPriority() {
		return 30;
	}

	#[\Override]
	public function isDefaultEnabledMail() {
		return true;
	}

	#[\Override]
	public function isDefaultEnabledStream() {
		return true;
	}
}
