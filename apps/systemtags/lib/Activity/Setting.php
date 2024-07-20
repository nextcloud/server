<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Activity;

use OCP\Activity\ISetting;
use OCP\IL10N;

class Setting implements ISetting {

	/** @var IL10N */
	protected $l;

	/**
	 * @param IL10N $l
	 */
	public function __construct(IL10N $l) {
		$this->l = $l;
	}

	/**
	 * @return string Lowercase a-z and underscore only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier() {
		return 'systemtags';
	}

	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName() {
		return $this->l->t('<strong>System tags</strong> for a file have been modified');
	}

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 * the admin section. The filters are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 * @since 11.0.0
	 */
	public function getPriority() {
		return 50;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 * @since 11.0.0
	 */
	public function canChangeStream() {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 * @since 11.0.0
	 */
	public function isDefaultEnabledStream() {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the mail
	 * @since 11.0.0
	 */
	public function canChangeMail() {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the stream
	 * @since 11.0.0
	 */
	public function isDefaultEnabledMail() {
		return false;
	}
}
