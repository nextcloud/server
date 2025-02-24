<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\Files_Versions\Events\VersionRestoredEvent;

/**
 * This listener is designed to be compatible with third-party code
 * that can still use a hook. This listener will be removed in
 * the next version and the rollback hook will stop working.
 *
 * @template-implements IEventListener<VersionRestoredEvent>
 */
class LegacyRollbackListener implements IEventListener {
	public function handle(Event $event): void {
		if (!($event instanceof VersionRestoredEvent)) {
			return;
		}

        $version = $event->getVersion();
        \OC_Hook::emit('\OCP\Versions', 'rollback', [
            'path' => $version->getVersionPath(),
            'revision' => $version->getRevisionId(),
            'node' => $version->getSourceFile(),
        ]);
	}
}
