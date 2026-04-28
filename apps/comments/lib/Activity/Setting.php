<?php

declare(strict_types=1);

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

	#[\Override]
	public function getIdentifier(): string {
		return 'comments';
	}

	#[\Override]
	public function getName(): string {
		return $this->l->t('<strong>Comments</strong> for files');
	}

	#[\Override]
	public function getGroupIdentifier(): string {
		return 'files';
	}

	#[\Override]
	public function getGroupName(): string {
		return $this->l->t('Files');
	}

	#[\Override]
	public function getPriority(): int {
		return 50;
	}

	#[\Override]
	public function canChangeStream(): bool {
		return true;
	}

	#[\Override]
	public function isDefaultEnabledStream(): bool {
		return true;
	}

	#[\Override]
	public function canChangeMail(): bool {
		return true;
	}

	#[\Override]
	public function isDefaultEnabledMail(): bool {
		return false;
	}
}
