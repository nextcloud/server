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

namespace OCP\FilesMetadata\Event;

use OC\FilesMetadata\Event\MetadataEventBase;
use OCP\Files\Node;
use OCP\FilesMetadata\Model\IFilesMetadata;

class MetadataLiveEvent extends MetadataEventBase {
	private bool $runAsBackgroundJob = false;

	public function __construct(
		Node $node,
		IFilesMetadata $metadata
	) {
		parent::__construct($node, $metadata);
	}

	/**
	 * If an app prefer to update metadata on a background job, instead of
	 * live process, just call this method.
	 * A new event will be generated on next cron tick.
	 *
	 * @return void
	 * @since 28.0.0
	 */
	public function requestBackgroundJob(): void {
		$this->runAsBackgroundJob = true;
	}

	/**
	 * return true if any app that catch this event requested a re-run as background job
	 *
	 * @return bool
	 * @since 28.0.0
	 */
	public function isRunAsBackgroundJobRequested(): bool {
		return $this->runAsBackgroundJob;
	}
}
