<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\SMB;

interface ITimeZoneProvider {
	/**
	 * Get the timezone of the smb server
	 *
	 * @param string $host
	 * @return string
	 */
	public function get(string $host): string;
}
