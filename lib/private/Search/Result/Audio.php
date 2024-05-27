<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Search\Result;

/**
 * A found audio file
 * @deprecated 20.0.0
 */
class Audio extends File {
	/**
	 * Type name; translated in templates
	 * @var string
	 * @deprecated 20.0.0
	 */
	public $type = 'audio';

	/**
	 * @TODO add ID3 information
	 */
}
