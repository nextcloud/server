<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata\Event;

use OCP\Files\Node;
use OCP\FilesMetadata\AMetadataEvent;
use OCP\FilesMetadata\Model\IFilesMetadata;

/**
 * MetadataNamedEvent is an event similar to MetadataBackgroundEvent completed with a target name,
 * used to limit the refresh of metadata only listeners capable of filtering themselves out.
 *
 * Meaning that when using this event, your app must implement a filter on the event's registered
 * name returned by getName()
 *
 * This event is mostly triggered when a registered name is added to the files scan
 *    i.e. ./occ files:scan --generate-metadata [name]
 *
 * @see AMetadataEvent::getMetadata()
 * @see AMetadataEvent::getNode()
 * @see MetadataNamedEvent::getName()
 * @since 28.0.0
 */
class MetadataNamedEvent extends AMetadataEvent {
	/**
	 * @param Node $node
	 * @param IFilesMetadata $metadata
	 * @param string $name name assigned to the event
	 *
	 * @since 28.0.0
	 */
	public function __construct(
		Node $node,
		IFilesMetadata $metadata,
		private string $name = '',
	) {
		parent::__construct($node, $metadata);
	}

	/**
	 * get the assigned name for the event.
	 * This is used to know if your app is the called one when running the
	 *    ./occ files:scan --generate-metadata [name]
	 *
	 * @return string
	 * @since 28.0.0
	 */
	public function getName(): string {
		return $this->name;
	}
}
