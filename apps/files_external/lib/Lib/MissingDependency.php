<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

/**
 * External storage backend dependency
 */
class MissingDependency {

	/** @var string|null Custom message */
	private $message = null;

	/**
	 * @param string $dependency
	 */
	public function __construct(
		private $dependency,
	) {
	}

	public function getDependency(): string {
		return $this->dependency;
	}

	public function getMessage(): ?string {
		return $this->message;
	}

	/**
	 * @param string $message
	 * @return self
	 */
	public function setMessage($message) {
		$this->message = $message;
		return $this;
	}
}
