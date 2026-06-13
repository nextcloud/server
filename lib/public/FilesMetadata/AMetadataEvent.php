<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata;

use OCP\EventDispatcher\Event;
use OCP\Files\Node;
use OCP\FilesMetadata\Model\IFilesMetadata;

/**
 * @since 28.0.0
 */
abstract class AMetadataEvent extends Event {
	/**
	 * @param Node $node
	 * @param IFilesMetadata $metadata
	 * @since 28.0.0
	 */
	public function __construct(
		protected Node $node,
		protected IFilesMetadata $metadata,
	) {
		parent::__construct();
	}

	/**
	 * returns related node
	 *
	 * @return Node
	 * @since 28.0.0
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * returns metadata. if known, it already contains data from the database.
	 * If the object is modified using its setters, changes are stored in database at the end of the event.
	 *
	 * @return IFilesMetadata
	 * @since 28.0.0
	 */
	public function getMetadata(): IFilesMetadata {
		return $this->metadata;
	}
}
