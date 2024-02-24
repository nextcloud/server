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

use OCP\FilesMetadata\AMetadataEvent;

/**
 * MetadataLiveEvent is an event initiated when a file is created or updated.
 * The app contains the Node related to the created/updated file, and a FilesMetadata that already
 * contains the currently known metadata.
 *
 * Setting new metadata, or modifying already existing metadata with different value, will trigger
 * the save of the metadata in the database.
 *
 * @see AMetadataEvent::getMetadata()
 * @see AMetadataEvent::getNode()
 * @see MetadataLiveEvent::requestBackgroundJob()
 * @since 28.0.0
 */
class MetadataLiveEvent extends AMetadataEvent {
	private bool $runAsBackgroundJob = false;

	/**
	 * For heavy process, call this method if your app prefers to update metadata on a
	 * background/cron job, instead of the live process.
	 * A similar MetadataBackgroundEvent will be broadcast on next cron tick.
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
