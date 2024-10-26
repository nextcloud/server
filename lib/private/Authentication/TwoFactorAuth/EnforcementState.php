<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\TwoFactorAuth;

use JsonSerializable;

class EnforcementState implements JsonSerializable {
	/** @var bool */
	private $enforced;

	/** @var array */
	private $enforcedGroups;

	/** @var array */
	private $excludedGroups;

	/**
	 * EnforcementState constructor.
	 *
	 * @param bool $enforced
	 * @param string[] $enforcedGroups
	 * @param string[] $excludedGroups
	 */
	public function __construct(bool $enforced,
		array $enforcedGroups = [],
		array $excludedGroups = []) {
		$this->enforced = $enforced;
		$this->enforcedGroups = $enforcedGroups;
		$this->excludedGroups = $excludedGroups;
	}

	/**
	 * @return bool
	 */
	public function isEnforced(): bool {
		return $this->enforced;
	}

	/**
	 * @return string[]
	 */
	public function getEnforcedGroups(): array {
		return $this->enforcedGroups;
	}

	/**
	 * @return string[]
	 */
	public function getExcludedGroups(): array {
		return $this->excludedGroups;
	}

	public function jsonSerialize(): array {
		return [
			'enforced' => $this->enforced,
			'enforcedGroups' => $this->enforcedGroups,
			'excludedGroups' => $this->excludedGroups,
		];
	}
}
