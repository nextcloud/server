<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OC;

/**
 * Manages the ownCloud navigation
 */
class NavigationManager implements \OCP\INavigationManager {
	protected $entries = array();
	protected $activeEntry;

	/**
	 * Creates a new navigation entry
	 * @param array $entry containing: id, name, order, icon and href key
	 */
	public function add(array $entry) {
		$entry['active'] = false;
		if(!isset($entry['icon'])) {
			$entry['icon'] = '';
		}
		$this->entries[] = $entry;
	}

	/**
	 * returns all the added Menu entries
	 * @return array an array of the added entries
	 */
	public function getAll() {
		return $this->entries;
	}

	/**
	 * removes all the entries
	 */
	public function clear() {
		$this->entries = array();
	}

	/**
	 * Sets the current navigation entry of the currently running app
	 * @param string $id of the app entry to activate (from added $entry)
	 */
	public function setActiveEntry($id) {
		$this->activeEntry = $id;
	}

	/**
	 * gets the active Menu entry
	 * @return string id or empty string
	 *
	 * This function returns the id of the active navigation entry (set by
	 * setActiveEntry
	 */
	public function getActiveEntry() {
		return $this->activeEntry;
	}
}
