<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Files\Node;

interface Node {
	/**
	 * @param string $targetPath
	 * @throws \OC\Files\NotPermittedException
	 * @return \OCP\Files\Node\Node
	 */
	public function move($targetPath);

	public function delete();

	/**
	 * @param string $targetPath
	 * @return \OCP\Files\Node\Node
	 */
	public function copy($targetPath);

	/**
	 * @param int $mtime
	 * @throws \OC\Files\NotPermittedException
	 */
	public function touch($mtime = null);

	/**
	 * @return \OC\Files\Storage\Storage
	 * @throws \OC\Files\NotFoundException
	 */
	public function getStorage();

	/**
	 * @return string
	 */
	public function getPath();

	/**
	 * @return string
	 */
	public function getInternalPath();

	/**
	 * @return int
	 */
	public function getId();

	/**
	 * @return array
	 */
	public function stat();

	/**
	 * @return int
	 */
	public function getMTime();

	/**
	 * @return int
	 */
	public function getSize();

	/**
	 * @return string
	 */
	public function getEtag();

	/**
	 * @return int
	 */
	public function getPermissions();

	/**
	 * @return bool
	 */
	public function isReadable();

	/**
	 * @return bool
	 */
	public function isUpdateable();

	/**
	 * @return bool
	 */
	public function isDeletable();

	/**
	 * @return bool
	 */
	public function isShareable();

	/**
	 * @return Node
	 */
	public function getParent();

	/**
	 * @return string
	 */
	public function getName();
}
