<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Search\Result;

/**
 * A found image file
 * @deprecated 20.0.0
 */
class Image extends File {
	/**
	 * Type name; translated in templates
	 * @var string
	 * @deprecated 20.0.0
	 */
	public $type = 'image';

	/**
	 * @TODO add EXIF information
	 */
}
