<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Activity;

use OCP\Activity\ActivitySettings;
use OCP\Activity\ISetting;
use OCP\IL10N;

/**
 * Adapt the old interface based settings into the new abstract
 * class based one
 */
class ActivitySettingsAdapter extends ActivitySettings {
	public function __construct(
		private ISetting $oldSettings,
		private IL10N $l10n,
	) {
	}

	#[\Override]
	public function getIdentifier() {
		return $this->oldSettings->getIdentifier();
	}

	#[\Override]
	public function getName() {
		return $this->oldSettings->getName();
	}

	#[\Override]
	public function getGroupIdentifier() {
		return 'other';
	}

	#[\Override]
	public function getGroupName() {
		return $this->l10n->t('Other activities');
	}

	#[\Override]
	public function getPriority() {
		return $this->oldSettings->getPriority();
	}

	#[\Override]
	public function canChangeMail() {
		return $this->oldSettings->canChangeMail();
	}

	#[\Override]
	public function isDefaultEnabledMail() {
		return $this->oldSettings->isDefaultEnabledMail();
	}
}
