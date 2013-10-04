<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 * 
 */

namespace OCP;

/**
 * Manages the ownCloud navigation
 */
interface INavigationManager {
	/**
	 * Creates a new navigation entry
	 * @param array $entry containing: id, name, order, icon and href key
	 */
	public function add(array $entry);

	/**
	 * Sets the current navigation entry of the currently running app
	 * @param string $appId id of the app entry to activate (from added $entry)
	 */
	public function setActiveEntry($appId);
}
