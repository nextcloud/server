<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Activity;

use OCP\Activity\ISetting;
use OCP\IL10N;

class Setting implements ISetting {
	public function __construct(
		protected IL10N $l,
	) {
	}

	public function getIdentifier(): string {
		return 'comments';
	}

	public function getName(): string {
		return $this->l->t('<strong>Comments</strong> for files');
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
