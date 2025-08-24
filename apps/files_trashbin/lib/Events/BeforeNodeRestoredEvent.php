<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Events;

use Exception;
use OCP\Files\Events\Node\AbstractNodesEvent;
use OCP\Files\Node;

/**
 * @since 28.0.0
 */
class BeforeNodeRestoredEvent extends AbstractNodesEvent {
	public function __construct(
		Node $source,
		Node $target,
		private bool &$run,
	) {
		parent::__construct($source, $target);
	}

	/**
	 * @return never
	 */
	public function abortOperation(?\Throwable $ex = null) {
		$this->stopPropagation();
		$this->run = false;
		if ($ex !== null) {
			throw $ex;
		} else {
			throw new Exception('Operation aborted');
		}
	}
}
