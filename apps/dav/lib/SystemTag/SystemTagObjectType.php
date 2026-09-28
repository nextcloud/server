<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\SystemTag;

use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Sabre\DAV\Exception\MethodNotAllowed;

/**
 * SystemTagObjectType property
 * This property represent a type of object which tags are assigned to.
 */
class SystemTagObjectType implements \Sabre\DAV\IFile {
	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';

	/** @var string[] */
	private array $objectsIds = [];

	public function __construct(
		private ISystemTag $tag,
		private string $type,
		private ISystemTagManager $tagManager,
		private ISystemTagObjectMapper $tagMapper,
	) {
	}

	/**
	 * Get the list of object ids that have this tag assigned.
	 */
	public function getObjectsIds(): array {
		if (empty($this->objectsIds)) {
			$this->objectsIds = $this->tagMapper->getObjectIdsForTags($this->tag->getId(), $this->type);
		}

		return $this->objectsIds;
	}

	/**
	 * Returns the system tag represented by this node
	 *
	 * @return ISystemTag system tag
	 */
	public function getSystemTag() {
		return $this->tag;
	}

	public function getName() {
		return $this->type;
	}

	public function getLastModified() {
		return null;
	}

	public function getETag() {
		return '"' . $this->tag->getETag() . '"';
	}

	public function setName($name) {
		throw new MethodNotAllowed();
	}
	public function put($data) {
		throw new MethodNotAllowed();
	}
	public function get() {
		throw new MethodNotAllowed();
	}
	public function delete() {
		throw new MethodNotAllowed();
	}
	public function getContentType() {
		throw new MethodNotAllowed();
	}
	public function getSize() {
		throw new MethodNotAllowed();
	}
}
