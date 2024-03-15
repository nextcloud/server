<?php

declare(strict_types=1);
/**
 * @copyright 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		private string $name = ''
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
