<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files;

use OCA\Files\Service\ChunkedUploadConfig;

class App {
	public static function extendJsConfig($settings): void {
		$appConfig = json_decode($settings['array']['oc_appconfig'], true);

		$appConfig['files'] = [
			'max_chunk_size' => ChunkedUploadConfig::getMaxChunkSize(),
		];

		$settings['array']['oc_appconfig'] = json_encode($appConfig);
	}
}
