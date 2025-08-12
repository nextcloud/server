<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

use OCA\Files_Trashbin\Events\BeforeNodeDeletedEvent;
use OCA\Files_Trashbin\Events\NodeRestoredEvent;

class Trashbin extends Action {
	public function delete(BeforeNodeDeletedEvent $beforeNodeDeletedEvent): void {
		$this->log('File "%s" deleted from trash bin.',
			['path' => $beforeNodeDeletedEvent->getSource()->getPath()], ['path']
		);
	}

	public function restore(NodeRestoredEvent $nodeRestoredEvent): void {
		$this->log('File "%s" restored from trash bin.',
			['path' => $nodeRestoredEvent->getTarget()], ['path']
		);
	}
}
