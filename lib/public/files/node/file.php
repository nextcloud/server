<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Files\Node;

use OC\Files\NotPermittedException;

interface File extends Node {
	/**
	 * @return string
	 * @throws \OC\Files\NotPermittedException
	 */
	public function getContent();

	/**
	 * @param string $data
	 * @throws \OC\Files\NotPermittedException
	 */
	public function putContent($data);

	/**
	 * @return string
	 */
	public function getMimeType();

	/**
	 * @param string $mode
	 * @return resource
	 * @throws \OC\Files\NotPermittedException
	 */
	public function fopen($mode);

	/**
	 * @param string $type
	 * @param bool $raw
	 * @return string
	 */
	public function hash($type, $raw = false);
}
