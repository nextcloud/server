<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Files;

interface File extends Node {
	/**
	 * Get the content of the file as string
	 *
	 * @return string
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getContent();

	/**
	 * Write to the file from string data
	 *
	 * @param string $data
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function putContent($data);

	/**
	 * Get the mimetype of the file
	 *
	 * @return string
	 */
	public function getMimeType();

	/**
	 * Open the file as stream, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @param string $mode
	 * @return resource
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function fopen($mode);

	/**
	 * Compute the hash of the file
	 * Type of hash is set with $type and can be anything supported by php's hash_file
	 *
	 * @param string $type
	 * @param bool $raw
	 * @return string
	 */
	public function hash($type, $raw = false);
}
