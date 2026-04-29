<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CardDAV\Activity;

use OCA\DAV\CalDAV\Activity\Setting\CalDAVSetting;

class Setting extends CalDAVSetting {
	/**
	 * @return string Lowercase a-z and underscore only identifier
	 */
	#[\Override]
	public function getIdentifier(): string {
		return 'contacts';
	}

	/**
	 * @return string A translated string
	 */
	#[\Override]
	public function getName(): string {
		return $this->l->t('A <strong>contact</strong> or <strong>address book</strong> was modified');
	}

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 *             the admin section. The filters are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 */
	#[\Override]
	public function getPriority(): int {
		return 50;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 */
	#[\Override]
	public function canChangeStream(): bool {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 */
	#[\Override]
	public function isDefaultEnabledStream(): bool {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the mail
	 */
	#[\Override]
	public function canChangeMail(): bool {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 */
	#[\Override]
	public function isDefaultEnabledMail(): bool {
		return false;
	}
}
