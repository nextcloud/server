<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

//.docm, .dotm, .xls(m), .xlt(m), .xla(m), .ppt(m), .pot(m), .pps(m), .ppa(m)
class MSOffice2003 extends Office {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/application\/vnd.ms-.*/';
	}
}
