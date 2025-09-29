<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\AppInfo;

return [
	'routes' => [
		[
			'name' => 'Preview#getPreview',
			'url' => '/preview',
			'verb' => 'GET',
		],
	],
];
