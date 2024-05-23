<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\GlobalScale;

use OCP\IConfig;

class Config implements \OCP\GlobalScale\IConfig {
	/** @var IConfig */
	private $config;

	/**
	 * Config constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * check if global scale is enabled
	 *
	 * @since 12.0.1
	 * @return bool
	 */
	public function isGlobalScaleEnabled() {
		return $this->config->getSystemValueBool('gs.enabled', false);
	}

	/**
	 * check if federation should only be used internally in a global scale setup
	 *
	 * @since 12.0.1
	 * @return bool
	 */
	public function onlyInternalFederation() {
		// if global scale is disabled federation works always globally
		$gsEnabled = $this->isGlobalScaleEnabled();
		if ($gsEnabled === false) {
			return false;
		}

		$enabled = $this->config->getSystemValueString('gs.federation', 'internal');

		return $enabled === 'internal';
	}
}
