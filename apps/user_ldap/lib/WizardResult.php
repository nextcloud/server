<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\User_LDAP;

class WizardResult {
	/**
	 * @var array<string,int|string|int[]|string[]>
	 */
	protected array $changes = [];
	/**
	 * @var array<string,string[]>
	 */
	protected array $options = [];
	protected bool $markedChange = false;

	/**
	 * @param int|string|int[]|string[] $value
	 */
	public function addChange(string $key, int|string|array $value): void {
		$this->changes[$key] = $value;
	}


	public function markChange(): void {
		$this->markedChange = true;
	}

	/**
	 * @param string|string[] $values
	 */
	public function addOptions(string $key, string|array $values): void {
		if (!is_array($values)) {
			$values = [$values];
		}
		$this->options[$key] = $values;
	}

	public function hasChanges(): bool {
		return (count($this->changes) > 0 || $this->markedChange);
	}

	/**
	 * @return array{changes:array<string,int|string|int[]|string[]>,options?:array<string,string[]>}
	 */
	public function getResultArray(): array {
		$result = [];
		$result['changes'] = $this->changes;
		if (count($this->options) > 0) {
			$result['options'] = $this->options;
		}
		return $result;
	}
}
