<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Bundles;

use OCP\IL10N;

abstract class Bundle {
	/**
	 * @param IL10N $l10n
	 */
	public function __construct(
		protected IL10N $l10n,
	) {
	}

	/**
	 * Get the identifier of the bundle
	 *
	 * @return string
	 */
	final public function getIdentifier() {
		return substr(strrchr(get_class($this), '\\'), 1);
	}

	/**
	 * Get the name of the bundle
	 *
	 * @return string
	 */
	abstract public function getName();

	/**
	 * Get the list of app identifiers in the bundle
	 *
	 * @return array
	 */
	abstract public function getAppIdentifiers();
}
