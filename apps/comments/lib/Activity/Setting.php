<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Activity;

use OCP\Activity\ActivitySettings;
use OCP\IL10N;

class Setting extends ActivitySettings {
	public function __construct(
		protected readonly IL10N $l,
	) {
	}

	public function getIdentifier(): string {
		return 'comments';
	}

	public function getName(): string {
		return $this->l->t('<strong>Comments</strong> for files');
	}

	public function getGroupIdentifier(): string {
		return 'files';
	}

	public function getGroupName(): string {
		return $this->l->t('Files');
	}

	public function getPriority(): int {
		return 50;
	}

	public function canChangeStream(): bool {
		return true;
	}

	public function isDefaultEnabledStream(): bool {
		return true;
	}

	public function canChangeMail(): bool {
		return true;
	}

	public function isDefaultEnabledMail(): bool {
		return false;
	}
}
