<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Diagnostics;

interface IQuery {
	/**
	 * @return string
	 */
	public function getSql();

	/**
	 * @return array
	 */
	public function getParams();

	/**
	 * @return float
	 */
	public function getDuration();
}
