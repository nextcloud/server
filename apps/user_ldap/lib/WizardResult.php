<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP;

class WizardResult {
	protected $changes = [];
	protected $options = [];
	protected $markedChange = false;

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function addChange($key, $value) {
		$this->changes[$key] = $value;
	}

	
	public function markChange() {
		$this->markedChange = true;
	}

	/**
	 * @param string $key
	 * @param array|string $values
	 */
	public function addOptions($key, $values) {
		if (!is_array($values)) {
			$values = [$values];
		}
		$this->options[$key] = $values;
	}

	/**
	 * @return bool
	 */
	public function hasChanges() {
		return (count($this->changes) > 0 || $this->markedChange);
	}

	/**
	 * @return array
	 */
	public function getResultArray() {
		$result = [];
		$result['changes'] = $this->changes;
		if (count($this->options) > 0) {
			$result['options'] = $this->options;
		}
		return $result;
	}
}
