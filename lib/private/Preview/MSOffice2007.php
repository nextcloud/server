<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

//.docx, .dotx, .xlsx, .xltx, .pptx, .potx, .ppsx
class MSOffice2007 extends Office {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/application\/vnd.openxmlformats-officedocument.*/';
	}
}
