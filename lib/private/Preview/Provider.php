<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Preview\IProvider;

abstract class Provider implements IProvider {
	private $options;

	/**
	 * Constructor
	 *
	 * @param array $options
	 */
	public function __construct(array $options = []) {
		$this->options = $options;
	}

	abstract public function getMimeType();

	public function isAvailable(\OCP\Files\FileInfo $file) {
		return true;
	}

	abstract public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview);
}
