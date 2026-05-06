<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Activity\Settings;

class FileChanged extends FileActivitySettings {
	/**
	 * @return string Lowercase a-z and underscore only identifier
	 * @since 11.0.0
	 */
	#[\Override]
	public function getIdentifier() {
		return 'file_changed';
	}

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	#[\Override]
	public function getName() {
		return $this->l->t('A file or folder has been <strong>changed</strong>');
	}

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 *             the admin section. The filters are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 * @since 11.0.0
	 */
	#[\Override]
	public function getPriority() {
		return 2;
	}

	#[\Override]
	public function canChangeMail() {
		return true;
	}

	#[\Override]
	public function isDefaultEnabledMail() {
		return false;
	}

	#[\Override]
	public function canChangeNotification() {
		return true;
	}

	#[\Override]
	public function isDefaultEnabledNotification() {
		return false;
	}
}
