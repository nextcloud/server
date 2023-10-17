<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Maxence Lange <maxence@artificial-owl.com>
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

namespace OC\FilesMetadata\Event;

use OCP\Files\Node;
use OCP\EventDispatcher\Event;
use OCP\FilesMetadata\Model\IFilesMetadata;

class MetadataEventBase extends Event {

	public function __construct(
		protected Node $node,
		protected IFilesMetadata $metadata
	) {
		parent::__construct();
	}

	/**
	 * returns id of the file
	 *
	 * @return Node
	 * @since 28.0.0
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * returns Metadata model linked to file id, with already known metadata from the database.
	 * If the object is modified using its setters, metadata are updated in database at the end of the event.
	 *
	 * @return IFilesMetadata
	 * @since 28.0.0
	 */
	public function getMetadata(): IFilesMetadata {
		return $this->metadata;
	}
}
