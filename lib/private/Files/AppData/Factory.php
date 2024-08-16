<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\AppData;

use OC\SystemConfig;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;

class Factory implements IAppDataFactory {
	private IRootFolder $rootFolder;
	private SystemConfig $config;

	/** @var array<string, IAppData> */
	private array $folders = [];

	public function __construct(IRootFolder $rootFolder,
		SystemConfig $systemConfig) {
		$this->rootFolder = $rootFolder;
		$this->config = $systemConfig;
	}

	public function get(string $appId): IAppData {
		if (!isset($this->folders[$appId])) {
			$this->folders[$appId] = new AppData($this->rootFolder, $this->config, $appId);
		}
		return $this->folders[$appId];
	}
}
