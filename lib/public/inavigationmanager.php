<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * Navigation manager interface
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
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
