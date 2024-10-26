<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

use OCA\Files_External\Service\BackendService;

/**
 * Trait to implement priority mechanics for a configuration class
 */
trait PriorityTrait {

	/** @var int initial priority */
	protected $priority = BackendService::PRIORITY_DEFAULT;

	/**
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * @param int $priority
	 * @return self
	 */
	public function setPriority($priority) {
		$this->priority = $priority;
		return $this;
	}
}
