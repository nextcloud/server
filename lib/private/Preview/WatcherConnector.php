<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OC\SystemConfig;
use OCA\Files_Versions\Events\VersionRestoredEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Node;

class WatcherConnector {
	public function __construct(
		private IRootFolder $root,
		private SystemConfig $config,
		private IEventDispatcher $dispatcher,
	) {
	}

	private function getWatcher(): Watcher {
		return \OCP\Server::get(Watcher::class);
	}

	public function connectWatcher(): void {
		// Do not connect if we are not setup yet!
		if ($this->config->getValue('instanceid', null) !== null) {
			$this->root->listen('\OC\Files', 'postWrite', function (Node $node) {
				$this->getWatcher()->postWrite($node);
			});

			$this->dispatcher->addListener(VersionRestoredEvent::class, function (VersionRestoredEvent $event) {
				$this->getWatcher()->versionRollback(['node' => $event->getVersion()->getSourceFile()]);
			});
		}
	}
}
