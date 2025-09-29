<?php

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

	public function canChangeMail() {
		return false;
	}

	public function canChangeStream() {
		return false;
	}

	public function getIdentifier() {
		return 'security';
	}

	public function getName() {
		return $this->l10n->t('Security');
	}

	public function getPriority() {
		return 30;
	}

	public function isDefaultEnabledMail() {
		return true;
	}

	public function isDefaultEnabledStream() {
		return true;
	}
}
