<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata\Event;

use OCP\FilesMetadata\AMetadataEvent;

/**
 * MetadataBackgroundEvent is an event similar to MetadataLiveEvent but dispatched
 * on a background thread instead of live thread. Meaning there is no limit to
 * the time required for the generation of your metadata.
 *
 * @see AMetadataEvent::getMetadata()
 * @see AMetadataEvent::getNode()
 * @since 28.0.0
 */
class MetadataBackgroundEvent extends AMetadataEvent {
}
