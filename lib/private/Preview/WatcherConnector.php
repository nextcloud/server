<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OC\SystemConfig;
use OCP\Files\IRootFolder;
use OCP\Files\Node;

class WatcherConnector {
	/** @var IRootFolder */
	private $root;

	/** @var SystemConfig */
	private $config;

	/**
	 * WatcherConnector constructor.
	 *
	 * @param IRootFolder $root
	 * @param SystemConfig $config
	 */
	public function __construct(IRootFolder $root,
		SystemConfig $config) {
		$this->root = $root;
		$this->config = $config;
	}

	/**
	 * @return Watcher
	 */
	private function getWatcher(): Watcher {
		return \OCP\Server::get(Watcher::class);
	}

	public function connectWatcher() {
		// Do not connect if we are not setup yet!
		if ($this->config->getValue('instanceid', null) !== null) {
			$this->root->listen('\OC\Files', 'postWrite', function (Node $node) {
				$this->getWatcher()->postWrite($node);
			});

			\OC_Hook::connect('\OCP\Versions', 'rollback', $this->getWatcher(), 'versionRollback');
		}
	}
}
