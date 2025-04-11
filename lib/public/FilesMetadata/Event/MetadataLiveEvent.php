<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
