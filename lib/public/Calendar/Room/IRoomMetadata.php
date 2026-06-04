<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar\Room;

/**
 * Interface IRoomMetadata
 *
 * This interface provides keys for common metadata.
 * Room Backends are not limited to this list and can provide
 * any metadata they want.
 *
 * @since 17.0.0
 */
interface IRoomMetadata {
	/**
	 * Type of room
	 *
	 * Allowed values for this key are:
	 * - meeting-room
	 * - lecture-hall
	 * - seminar-room
	 * - other
	 *
	 * @since 17.0.0
	 */
	public const ROOM_TYPE = '{http://nextcloud.com/ns}room-type';

	/**
	 * Seating capacity of the room
	 *
	 * @since 17.0.0
	 */
	public const CAPACITY = '{http://nextcloud.com/ns}room-seating-capacity';

	/**
	 * The physical address of the building this room is located in
	 *
	 * @since 17.0.0
	 */
	public const BUILDING_ADDRESS = '{http://nextcloud.com/ns}room-building-address';

	/**
	 * The story of the building this rooms is located in
	 *
	 * @since 17.0.0
	 */
	public const BUILDING_STORY = '{http://nextcloud.com/ns}room-building-story';

	/**
	 * The room-number
	 *
	 * @since 17.0.0
	 */
	public const BUILDING_ROOM_NUMBER = '{http://nextcloud.com/ns}room-building-room-number';

	/**
	 * Features provided by the room.
	 * This is a stringified list of features.
	 * Example: "PHONE,VIDEO-CONFERENCING"
	 *
	 * Standard features include:
	 * - PHONE: This room is fitted with a phone
	 * - VIDEO-CONFERENCING: This room is fitted with a video-conferencing system
	 * - TV: This room is fitted with a TV
	 * - PROJECTOR: This room is fitted with a projector
	 * - WHITEBOARD: This room is fitted with a whiteboard
	 * - WHEELCHAIR-ACCESSIBLE: This room is wheelchair-accessible
	 *
	 * @since 17.0.0
	 */
	public const FEATURES = '{http://nextcloud.com/ns}room-features';
}
