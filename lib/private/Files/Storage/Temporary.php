<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Storage;

/**
 * local storage backend in temporary folder for testing purpose
 */
class Temporary extends Local {
	public function __construct(array $parameters = []) {
		parent::__construct(['datadir' => \OC::$server->getTempManager()->getTemporaryFolder()]);
	}

	public function cleanUp(): void {
		\OC_Helper::rmdirr($this->datadir);
	}

	public function __destruct() {
		parent::__destruct();
		$this->cleanUp();
	}

	public function getDataDir(): array|string {
		return $this->datadir;
	}
}
